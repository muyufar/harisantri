<?php

namespace App\Services;

use App\Enums\RegistrationStatus;
use App\Models\Registration;

class ExpiryService
{
    public function __construct(private AuditLogger $audit) {}

    public function releaseExpired(): int
    {
        $released = 0;

        Registration::query()
            ->whereIn('status', [
                RegistrationStatus::AwaitingPayment->value,
                RegistrationStatus::PaymentRejected->value,
            ])
            ->whereNotNull('hold_expires_at')
            ->where('hold_expires_at', '<=', now())
            ->orderBy('id')
            ->each(function (Registration $registration) use (&$released) {
                $registration->items()->with('reservation')->get()->each(function ($item) {
                    $item->reservation?->delete();
                });

                $registration->update(['status' => RegistrationStatus::Expired]);
                $this->audit->log('registration.expired', $registration, [
                    'code' => $registration->code,
                ]);
                $released++;
            });

        return $released;
    }
}
