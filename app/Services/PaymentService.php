<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Exceptions\BookingException;
use App\Models\PaymentVerification;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(private AuditLogger $audit) {}

    public function submitProof(Registration $registration, UploadedFile $file): Registration
    {
        return DB::transaction(function () use ($registration, $file) {
            $registration = Registration::query()->lockForUpdate()->findOrFail($registration->id);

            if (! $registration->canUploadProof()) {
                throw new BookingException('Bukti pembayaran tidak bisa diunggah untuk status ini, atau batas waktu hold sudah lewat.');
            }

            $path = $file->store('payment-proofs', 'local');
            $payment = $registration->payment()->lockForUpdate()->firstOrFail();
            $previous = $payment->proof_path;

            $payment->update([
                'status' => PaymentStatus::Submitted,
                'proof_path' => $path,
                'submitted_at' => now(),
                'amount' => $registration->grand_total,
            ]);

            $registration->update(['status' => RegistrationStatus::AwaitingVerification]);

            $registration->items()->with('reservation')->get()->each(function ($item) {
                $item->reservation?->update(['expires_at' => null]);
            });

            if ($previous) {
                \Illuminate\Support\Facades\Storage::disk('local')->delete($previous);
            }

            $this->audit->log('payment.proof_submitted', $registration, [
                'code' => $registration->code,
            ]);

            return $registration->fresh(['payment', 'items']);
        });
    }

    public function recordOffline(Registration $registration, User $admin): Registration
    {
        return DB::transaction(function () use ($registration, $admin) {
            $registration = Registration::query()->lockForUpdate()->findOrFail($registration->id);

            if ($registration->status !== RegistrationStatus::AwaitingPayment) {
                throw new BookingException('Pembayaran offline hanya bisa dicatat untuk pendaftaran yang baru menunggu pembayaran.');
            }

            $payment = $registration->payment()->lockForUpdate()->firstOrFail();
            $payment->update([
                'status' => PaymentStatus::Verified,
                'amount' => $registration->grand_total,
                'submitted_at' => now(),
                'verified_at' => now(),
            ]);

            $registration->update([
                'status' => RegistrationStatus::Confirmed,
                'confirmed_at' => now(),
                'hold_expires_at' => null,
            ]);

            $registration->items()->with('reservation')->get()->each(function ($item) {
                $item->reservation?->update(['expires_at' => null]);
            });

            PaymentVerification::create([
                'payment_id' => $payment->id,
                'user_id' => $admin->id,
                'decision' => 'approved',
                'reason' => 'Didaftarkan panitia dan dicatat sudah bayar.',
            ]);

            $this->audit->log('payment.approved', $registration, [
                'code' => $registration->code,
                'amount' => $payment->amount,
                'source' => 'backoffice',
            ]);

            return $registration->fresh(['payment', 'items']);
        });
    }

    public function approve(Registration $registration, User $admin): Registration
    {
        return DB::transaction(function () use ($registration, $admin) {
            $registration = Registration::query()->lockForUpdate()->findOrFail($registration->id);

            if ($registration->status !== RegistrationStatus::AwaitingVerification) {
                throw new BookingException('Hanya pembayaran yang menunggu verifikasi yang dapat diterima.');
            }

            $payment = $registration->payment()->lockForUpdate()->firstOrFail();
            $payment->update([
                'status' => PaymentStatus::Verified,
                'verified_at' => now(),
            ]);

            $registration->update([
                'status' => RegistrationStatus::Confirmed,
                'confirmed_at' => now(),
            ]);

            $registration->items()->with('reservation')->get()->each(function ($item) {
                $item->reservation?->update(['expires_at' => null]);
            });

            PaymentVerification::create([
                'payment_id' => $payment->id,
                'user_id' => $admin->id,
                'decision' => 'approved',
            ]);

            $this->audit->log('payment.approved', $registration, [
                'code' => $registration->code,
                'amount' => $payment->amount,
            ]);

            return $registration->fresh(['payment', 'items']);
        });
    }

    public function reject(Registration $registration, User $admin, string $reason): Registration
    {
        return DB::transaction(function () use ($registration, $admin, $reason) {
            $registration = Registration::query()->with('event')->lockForUpdate()->findOrFail($registration->id);

            if ($registration->status !== RegistrationStatus::AwaitingVerification) {
                throw new BookingException('Hanya pembayaran yang menunggu verifikasi yang dapat ditolak.');
            }

            $holdUntil = now()->addHours($registration->event->config()->holdHours());
            $payment = $registration->payment()->lockForUpdate()->firstOrFail();

            $payment->update(['status' => PaymentStatus::Rejected]);
            $registration->update([
                'status' => RegistrationStatus::PaymentRejected,
                'hold_expires_at' => $holdUntil,
            ]);

            $registration->items()->with('reservation')->get()->each(function ($item) use ($holdUntil) {
                $item->reservation?->update(['expires_at' => $holdUntil]);
            });

            PaymentVerification::create([
                'payment_id' => $payment->id,
                'user_id' => $admin->id,
                'decision' => 'rejected',
                'reason' => $reason,
            ]);

            $this->audit->log('payment.rejected', $registration, [
                'code' => $registration->code,
                'reason' => $reason,
            ]);

            return $registration->fresh(['payment']);
        });
    }

    public function cancel(Registration $registration, User $admin, ?string $note, bool $refund): Registration
    {
        return DB::transaction(function () use ($registration, $admin, $note, $refund) {
            $registration = Registration::query()->lockForUpdate()->findOrFail($registration->id);

            if (in_array($registration->status, [RegistrationStatus::Cancelled, RegistrationStatus::Expired], true)) {
                throw new BookingException('Pendaftaran ini sudah tidak aktif.');
            }

            $registration->items()->with('reservation')->get()->each(function ($item) {
                $item->reservation?->delete();
            });

            $payment = $registration->payment;
            if ($refund && $payment && $payment->status === PaymentStatus::Verified) {
                $payment->update(['status' => PaymentStatus::Refunded]);
            }

            $registration->update([
                'status' => RegistrationStatus::Cancelled,
                'cancelled_at' => now(),
                'cancellation_note' => $note,
            ]);

            $this->audit->log('registration.cancelled', $registration, [
                'code' => $registration->code,
                'refund' => $refund,
                'by' => $admin->id,
            ]);

            return $registration->fresh();
        });
    }
}
