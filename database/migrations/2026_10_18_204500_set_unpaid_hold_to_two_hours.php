<?php

use App\Enums\RegistrationStatus;
use App\Models\PaymentVerification;
use App\Models\Registration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('event_settings')->where('key', 'hold_hours')->update([
            'value' => '2',
            'updated_at' => now(),
        ]);

        Registration::query()
            ->with(['items.reservation', 'payment.verifications'])
            ->whereIn('status', [
                RegistrationStatus::AwaitingPayment->value,
                RegistrationStatus::PaymentRejected->value,
            ])
            ->whereNotNull('hold_expires_at')
            ->orderBy('id')
            ->each(function (Registration $registration): void {
                $anchor = $registration->created_at ?? now();

                if ($registration->status === RegistrationStatus::PaymentRejected) {
                    $rejectedAt = $registration->payment?->verifications
                        ->where('decision', 'rejected')
                        ->sortByDesc(fn (PaymentVerification $verification) => $verification->created_at)
                        ->first()
                        ?->created_at;
                    $anchor = $rejectedAt ?? $registration->updated_at ?? $anchor;
                }

                $limit = Carbon::parse($anchor)->addHours(2);
                $next = $registration->hold_expires_at->lt($limit) ? $registration->hold_expires_at : $limit;

                $registration->update(['hold_expires_at' => $next]);
                $registration->items->each(function ($item) use ($next): void {
                    $item->reservation?->update(['expires_at' => $next]);
                });
            });
    }

    public function down(): void
    {
        DB::table('event_settings')->where('key', 'hold_hours')->where('value', '2')->update([
            'value' => '24',
            'updated_at' => now(),
        ]);
    }
};
