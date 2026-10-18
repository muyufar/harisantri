<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RegistrationStatus;
use App\Exceptions\BookingException;
use App\Models\Registration;
use App\Services\PaymentService;
use App\Support\Phone;
use Illuminate\Http\Request;

class RegistrationController extends AdminController
{
    public function index(Request $request)
    {
        $event = $this->event();
        $term = trim($request->string('q')->toString());
        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';

        $registrations = Registration::query()
            ->with(['applicant', 'items', 'payment'])
            ->where('event_id', $event->id)
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
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
            ->paginate(15)
            ->withQueryString();

        return view('admin.registrations.index', [
            'registrations' => $registrations,
            'statuses' => RegistrationStatus::cases(),
        ]);
    }

    public function show(Registration $registration)
    {
        abort_unless($registration->event_id === $this->event()->id, 404);
        $registration->load(['applicant', 'items.booth', 'payment.verifications.user', 'event']);

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
