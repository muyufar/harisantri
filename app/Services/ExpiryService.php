<?php

namespace App\Services;

use App\Enums\RegistrationStatus;
use App\Models\Registration;

class ExpiryService
{
    public function __construct(private AuditLogger $audit) {}

    public function releaseExpired(int $limit = 100): int
    {
        $released = 0;

        $rows = Registration::query()
            ->with('items.reservation')
            ->whereIn('status', [
                RegistrationStatus::AwaitingPayment->value,
                RegistrationStatus::PaymentRejected->value,
            ])
            ->whereNotNull('hold_expires_at')
            ->where('hold_expires_at', '<=', now())
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($rows as $registration) {
            foreach ($registration->items as $item) {
                $item->reservation?->delete();
            }

            $registration->update(['status' => RegistrationStatus::Expired]);
            $this->audit->log('registration.expired', $registration, [
                'code' => $registration->code,
            ]);
            $released++;
        }

        return $released;
    }
}
