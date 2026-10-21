<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\RegistrationItem;
use App\Models\SponsorshipRequest;
use App\Services\BoothMapService;
use Illuminate\Support\Facades\Hash;

class DashboardController extends AdminController
{
    public function __invoke(BoothMapService $maps)
    {
        $event = $this->event();
        $stats = $maps->stats($event, activeZonesOnly: false);
        $base = Registration::query()->where('event_id', $event->id)->where('type', 'umkm');

        $activeStatuses = [
            RegistrationStatus::AwaitingPayment->value,
            RegistrationStatus::AwaitingVerification->value,
            RegistrationStatus::PaymentRejected->value,
            RegistrationStatus::Confirmed->value,
        ];
        $byStatus = [];
        foreach (RegistrationStatus::cases() as $status) {
            $byStatus[$status->value] = 0;
        }
        $statusRows = (clone $base)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->get();
        foreach ($statusRows as $row) {
            $key = $row->status instanceof RegistrationStatus ? $row->status->value : (string) $row->status;
            $byStatus[$key] = (int) $row->aggregate;
        }

        return view('admin.dashboard', [
            'event' => $event,
            'stats' => $stats,
            'areas' => $maps->areaCards($event, activeZonesOnly: false),
            'byStatus' => $byStatus,
            'registrations' => (clone $base)->count(),
            'sponsorships' => SponsorshipRequest::query()->where('event_id', $event->id)->count(),
            'pendingPayments' => (clone $base)->where('status', RegistrationStatus::AwaitingVerification)->count(),
            'income' => (int) Payment::query()
                ->where('status', PaymentStatus::Verified)
                ->whereHas('registration', fn ($query) => $query->where('event_id', $event->id))
                ->sum('amount'),
            'outstanding' => (int) (clone $base)
                ->whereIn('status', [
                    RegistrationStatus::AwaitingPayment,
                    RegistrationStatus::AwaitingVerification,
                    RegistrationStatus::PaymentRejected,
                ])->sum('grand_total'),
            'tents' => RegistrationItem::query()
                ->where('with_tent', true)
                ->whereHas('registration', fn ($query) => $query->where('event_id', $event->id)->whereIn('status', $activeStatuses))
                ->count(),
            'defaultPassword' => Hash::check('ChangeMe!HSN2026', (string) auth()->user()->password),
        ]);
    }
}
