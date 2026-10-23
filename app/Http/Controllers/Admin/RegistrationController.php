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
use Illuminate\Support\Facades\DB;

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
        $multi = $request->string('multi')->toString() === '1';

        $activeStatuses = [
            RegistrationStatus::AwaitingPayment,
            RegistrationStatus::AwaitingVerification,
            RegistrationStatus::PaymentRejected,
            RegistrationStatus::Confirmed,
        ];

        $slotTotals = DB::table('registrations')
            ->join('registration_items', 'registration_items.registration_id', '=', 'registrations.id')
            ->where('registrations.event_id', $event->id)
            ->where('registrations.type', 'umkm')
            ->whereIn('registrations.status', array_map(fn (RegistrationStatus $status) => $status->value, $activeStatuses))
            ->groupBy('registrations.whatsapp')
            ->select('registrations.whatsapp', DB::raw('COUNT(registration_items.id) as slot_total'))
            ->pluck('slot_total', 'whatsapp');

        $multiWhatsapps = $slotTotals->filter(fn ($total) => (int) $total > 1)->keys();

        $registrations = Registration::query()
            ->with(['applicant', 'items', 'payment'])
            ->withCount('items')
            ->where('event_id', $event->id)
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($area, fn ($query) => $query->whereHas('items', fn ($items) => $items->where('zone_name', $area['name'])))
            ->when(in_array($tenda, ['0', '1'], true), fn ($query) => $query->whereHas('items', fn ($items) => $items->where('with_tent', $tenda === '1')))
            ->when($multi, function ($query) use ($multiWhatsapps, $activeStatuses) {
                $query->whereIn('status', $activeStatuses)
                    ->whereIn('whatsapp', $multiWhatsapps->all());
            })
            ->when($term !== '', function ($query) use ($like, $term) {
                $digits = Phone::digits($term);
                $phoneSearch = strlen($digits) >= 8 && preg_match('/^\+?[\d\s\-()]+$/', $term) === 1;
                $query->where(function ($query) use ($like, $digits, $phoneSearch) {
                    $query->where('code', 'like', $like)
                        ->when($phoneSearch, fn ($query) => $query->orWhere('whatsapp', 'like', '%'.$digits.'%'))
                        ->orWhereHas('applicant', fn ($applicant) => $applicant
                            ->where('business_name', 'like', $like)
                            ->orWhere('full_name', 'like', $like)
                            ->orWhere('nik', 'like', $like)
                            ->orWhere('email', 'like', $like)
                            ->orWhere('address', 'like', $like)
                            ->orWhere('product_type', 'like', $like)
                            ->orWhere('product_description', 'like', $like))
                        ->orWhereHas('items', function ($items) use ($like) {
                            $items->where(function ($items) use ($like) {
                                $items->where('booth_code', 'like', $like)
                                    ->orWhere('zone_name', 'like', $like);
                            });
                        });
                });
            })
            ->when($multi, fn ($query) => $query->orderBy('whatsapp')->latest('id'), fn ($query) => $query->latest())
            ->paginate($perPage)
            ->withQueryString()
            ->through(function (Registration $registration) use ($slotTotals) {
                $registration->setAttribute('wa_slot_total', (int) ($slotTotals[$registration->whatsapp] ?? $registration->items_count));
                $registration->setAttribute('is_multi_lapak', $registration->wa_slot_total > 1);

                return $registration;
            });

        return view('admin.registrations.index', [
            'registrations' => $registrations,
            'statuses' => RegistrationStatus::cases(),
            'areas' => TentLayout::areas(),
            'multiWhatsappCount' => $multiWhatsapps->count(),
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

        try {
            abort_unless($registration->event_id === $this->event()->id, 404);
            $booking->relocate($registration, $item, (int) $data['area']);
        } catch (BookingException $exception) {
            return back()->withErrors(['status' => $exception->getMessage()]);
        }

        $area = TentLayout::area((int) $data['area']);

        return back()->with('status', 'Lapak dipindah ke '.$area['name'].'. Total pendaftaran '.rupiah($registration->fresh()->grand_total).'.');
    }

    public function drop(Request $request, Registration $registration, BookingService $booking)
    {
        $data = $request->validate([
            'item_id' => ['required', 'integer'],
        ]);

        $item = $registration->items()->whereKey($data['item_id'])->first();
        if (! $item) {
            return back()->withErrors(['status' => 'Slot yang dipilih tidak ditemukan.']);
        }

        try {
            abort_unless($registration->event_id === $this->event()->id, 404);
            $booking->dropItem($registration, $item);
        } catch (BookingException $exception) {
            return back()->withErrors(['status' => $exception->getMessage()]);
        }

        $fresh = $registration->fresh();

        return back()->with('status', 'Slot '.$item->booth_code.' dilepas. Total pendaftaran sekarang '.rupiah($fresh->grand_total).'. Jika pembayaran sudah masuk lebih besar, refund dilakukan manual.');
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
        }, 'Pendaftaran diaktifkan kembali'.$place.' sampai '.$until.' WIB. Pendaftar bisa mengunggah bukti lewat cek status. Harga mengikuti ukuran kotak yang dipilih.');
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
