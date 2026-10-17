<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RegistrationStatus;
use App\Exceptions\BookingException;
use App\Models\BoothCheckin;
use App\Models\RegistrationItem;
use App\Services\QrService;
use Illuminate\Http\Request;

class CheckinController extends AdminController
{
    public function index()
    {
        return view('admin.checkin.index', ['result' => null]);
    }

    public function store(Request $request, QrService $qr)
    {
        $data = $request->validate([
            'token' => ['nullable', 'string'],
            'code' => ['nullable', 'string', 'max:40'],
            'booth_code' => ['nullable', 'string', 'max:20'],
        ]);

        try {
            $item = $this->findItem($qr, $data);
        } catch (BookingException $exception) {
            return back()->withErrors(['token' => $exception->getMessage()])->withInput();
        }

        $existing = $item->checkin;
        if (! $existing) {
            $existing = BoothCheckin::create([
                'registration_item_id' => $item->id,
                'user_id' => $request->user()->id,
                'checked_in_at' => now(),
            ]);
            $created = true;
        } else {
            $created = false;
        }

        $existing->load('user');

        return view('admin.checkin.index', [
            'result' => $item->load('registration.applicant'),
            'checkin' => $existing,
            'created' => $created,
        ]);
    }

    /** @param  array<string, mixed>  $data */
    private function findItem(QrService $qr, array $data): RegistrationItem
    {
        $token = trim((string) ($data['token'] ?? ''));
        if (str_contains($token, '|')) {
            return $qr->resolve($token);
        }

        $code = strtoupper(trim((string) ($data['code'] ?: $token)));
        $boothCode = strtoupper(trim((string) ($data['booth_code'] ?? '')));
        if ($code === '' || $boothCode === '') {
            throw new BookingException('Pindai QR atau isi kode pendaftaran beserta nomor lapak.');
        }

        $item = RegistrationItem::query()
            ->with(['registration.applicant', 'checkin'])
            ->where('booth_code', $boothCode)
            ->whereHas('registration', fn ($query) => $query
                ->where('code', $code)
                ->where('status', RegistrationStatus::Confirmed))
            ->first();

        if (! $item) {
            throw new BookingException('Lapak terkonfirmasi dengan kode itu tidak ditemukan.');
        }

        return $item;
    }
}
