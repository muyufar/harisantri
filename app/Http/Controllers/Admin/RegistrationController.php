<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RegistrationStatus;
use App\Exceptions\BookingException;
use App\Models\Registration;
use App\Services\BookingService;
use App\Services\PaymentService;
use App\Support\Phone;
use App\Support\TentLayout;
use Illuminate\Http\Request;

class RegistrationController extends AdminController
{
    public function index(Request $request)
    {
        $event = $this->event();
        $perPage = (int) $request->integer('per_page', 10);
        if (! in_array($perPage, [10, 50, 100, 200], true)) {
            $perPage = 10;
        }
        $term = trim($request->string('q')->toString());
        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';
        $kotak = (int) $request->integer('kotak');
        $area = TentLayout::area($kotak);
        $tenda = $request->string('tenda')->toString();

        $registrations = Registration::query()
            ->with(['applicant', 'items', 'payment'])
            ->where('event_id', $event->id)
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($area, fn ($query) => $query->whereHas('items', fn ($items) => $items->where('zone_name', $area['name'])))
            ->when(in_array($tenda, ['0', '1'], true), fn ($query) => $query->whereHas('items', fn ($items) => $items->where('with_tent', $tenda === '1')))
            ->when($term !== '', function ($query) use ($like, $term) {
                $digits = Phone::digits($term);
                $query->where(function ($query) use ($like, $digits) {
                    $query->where('code', 'like', $like)
                        ->orWhere('whatsapp', 'like', '%'.$digits.'%')
                        ->orWhereHas('applicant', fn ($applicant) => $applicant
                            ->where('business_name', 'like', $like)
                            ->orWhere('full_name', 'like', $like)
                            ->orWhere('nik', 'like', $like))
                        ->orWhereHas('items', fn ($items) => $items->where('booth_code', 'like', $like));
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.registrations.index', [
            'registrations' => $registrations,
            'statuses' => RegistrationStatus::cases(),
            'areas' => TentLayout::areas(),
        ]);
    }

    public function show(Registration $registration)
    {
        abort_unless($registration->event_id === $this->event()->id, 404);
        $registration->load(['applicant', 'items.booth.zone', 'payment.verifications.user', 'event']);

        return view('admin.registrations.show', ['registration' => $registration]);
    }

    public function approve(Registration $registration, PaymentService $payments)
    {
        return $this->decide($registration, function () use ($registration, $payments) {
            $payments->approve($registration, request()->user());
        }, 'Pembayaran diterima. Lapak terkonfirmasi.');
    }

    public function reject(Request $request, Registration $registration, PaymentService $payments)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']]);

        return $this->decide($registration, function () use ($registration, $payments, $data) {
            $payments->reject($registration, request()->user(), $data['reason']);
        }, 'Pembayaran ditolak. Pendaftar dapat mengunggah ulang bukti.');
    }

    public function move(Request $request, Registration $registration, BookingService $booking)
    {
        $data = $request->validate([
            'item_id' => ['required', 'integer'],
            'area' => ['required', 'integer', 'between:1,8'],
        ]);

        $item = $registration->items()->whereKey($data['item_id'])->first();
        if (! $item) {
            return back()->withErrors(['area' => 'Slot yang dipilih tidak ditemukan.']);
        }

        return $this->decide($registration, function () use ($registration, $booking, $item, $data) {
            $booking->relocate($registration, $item, (int) $data['area']);
        }, 'Lapak dipindah ke '.TentLayout::area((int) $data['area'])['name'].'. Harga tidak berubah.');
    }

    public function restore(Request $request, Registration $registration, BookingService $booking)
    {
        $data = $request->validate([
            'area' => ['nullable', 'integer', 'between:1,8'],
        ]);
        $until = now()->addHours($this->event()->config()->holdHours())->timezone('Asia/Jakarta')->format('d/m/Y H.i');
        $area = TentLayout::area((int) ($data['area'] ?? 0));
        $place = $area ? ' di '.$area['name'] : '';

        return $this->decide($registration, function () use ($registration, $booking, $data) {
            $booking->restore($registration, isset($data['area']) ? (int) $data['area'] : null);
        }, 'Pendaftaran diaktifkan kembali'.$place.' sampai '.$until.' WIB. Pendaftar bisa mengunggah bukti lewat cek status. Harga tidak berubah.');
    }

    public function cancel(Request $request, Registration $registration, PaymentService $payments)
    {
        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
            'refund' => ['nullable', 'boolean'],
        ]);

        return $this->decide($registration, function () use ($registration, $payments, $data, $request) {
            $payments->cancel($registration, $request->user(), $data['note'] ?? null, $request->boolean('refund'));
        }, 'Pendaftaran dibatalkan dan slot dilepas.');
    }

    private function decide(Registration $registration, callable $action, string $message)
    {
        abort_unless($registration->event_id === $this->event()->id, 404);

        try {
            $action();
        } catch (BookingException $exception) {
            return back()->withErrors(['status' => $exception->getMessage()]);
        }

        return back()->with('status', $message);
    }
}
