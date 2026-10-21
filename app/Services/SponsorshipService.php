<?php

namespace App\Services;

use App\Enums\SponsorshipStatus;
use App\Exceptions\BookingException;
use App\Models\Booth;
use App\Models\BoothReservation;
use App\Models\Event;
use App\Models\SponsorshipOffer;
use App\Models\SponsorshipRequest;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class SponsorshipService
{
    public function __construct(
        private CodeGenerator $codes,
        private AuditLogger $audit,
    ) {}

    /** @param  array<string, mixed>  $data */
    public function submit(Event $event, array $data): SponsorshipRequest
    {
        if (! $event->registrationIsOpen()) {
            throw new BookingException($event->config()->registrationClosedMessage());
        }

        $request = SponsorshipRequest::create([
            'event_id' => $event->id,
            'sponsorship_category_id' => $data['sponsorship_category_id'],
            'code' => $this->codes->make(),
            'company_name' => $data['company_name'],
            'pic_name' => $data['pic_name'],
            'whatsapp' => Phone::normalize($data['whatsapp']),
            'business_type' => $data['business_type'],
            'area_requirement' => $data['area_requirement'],
            'facility_needs' => $data['facility_needs'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => SponsorshipStatus::NewRequest,
        ]);

        $this->audit->log('sponsorship.submitted', $request, ['code' => $request->code]);

        return $request;
    }

    public function addOffer(SponsorshipRequest $request, User $admin, int $amount, ?string $location, ?string $notes): SponsorshipOffer
    {
        $offer = $request->offers()->create([
            'user_id' => $admin->id,
            'amount' => $amount,
            'location_note' => $location,
            'notes' => $notes,
        ]);

        if (in_array($request->status, [SponsorshipStatus::NewRequest, SponsorshipStatus::Contacted], true)) {
            $request->update(['status' => SponsorshipStatus::Negotiation]);
        }

        $this->audit->log('sponsorship.offer', $request, [
            'code' => $request->code,
            'amount' => $amount,
        ]);

        return $offer;
    }

    public function markStatus(SponsorshipRequest $request, SponsorshipStatus $status): SponsorshipRequest
    {
        if ($status === SponsorshipStatus::Rejected) {
            $this->releaseBooth($request);
            $request->update([
                'status' => $status,
                'booth_id' => null,
            ]);
        } else {
            $request->update(['status' => $status]);
        }

        $this->audit->log('sponsorship.status', $request, [
            'code' => $request->code,
            'status' => $status->value,
        ]);

        return $request->fresh();
    }

    public function approve(SponsorshipRequest $request, Booth $booth): SponsorshipRequest
    {
        $offer = $request->offers()->latest()->first();
        if (! $offer) {
            throw new BookingException('Masukkan penawaran harga sebelum menyetujui kerja sama.');
        }

        if ($booth->event_id !== $request->event_id || $booth->type !== 'sponsorship') {
            throw new BookingException('Lokasi harus berupa slot sponsorship pada event yang sama.');
        }

        try {
            return DB::transaction(function () use ($request, $booth, $offer) {
                $locked = Booth::query()
                    ->with('zone')
                    ->when(in_array(DB::getDriverName(), ['mysql', 'pgsql'], true), fn ($query) => $query->lockForUpdate())
                    ->findOrFail($booth->id);

                $locked->load(['reservation.registrationItem.registration', 'reservation.sponsorshipRequest']);

                $currentReservation = $locked->reservation;
                $alreadyOurs = $currentReservation && (int) $currentReservation->sponsorship_request_id === (int) $request->id;

                if (! $alreadyOurs && ($locked->state !== 'available' || $locked->displayStatus() !== 'available')) {
                    throw new BookingException('Slot sponsorship itu sedang tidak tersedia.');
                }

                $this->releaseBooth($request);

                BoothReservation::create([
                    'booth_id' => $locked->id,
                    'sponsorship_request_id' => $request->id,
                    'expires_at' => null,
                ]);

                $request->update([
                    'booth_id' => $locked->id,
                    'status' => SponsorshipStatus::Approved,
                    'agreed_price' => $offer->amount,
                ]);

                $this->audit->log('sponsorship.approved', $request, [
                    'code' => $request->code,
                    'booth' => $locked->code,
                    'amount' => $offer->amount,
                ]);

                return $request->fresh(['booth', 'offers', 'category']);
            });
        } catch (UniqueConstraintViolationException) {
            throw new BookingException('Slot sponsorship itu baru saja terisi.');
        }
    }

    private function releaseBooth(SponsorshipRequest $request): void
    {
        BoothReservation::query()->where('sponsorship_request_id', $request->id)->delete();
    }
}
