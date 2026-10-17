<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SponsorshipStatus;
use App\Exceptions\BookingException;
use App\Models\Booth;
use App\Models\SponsorshipRequest;
use App\Services\SponsorshipService;
use Illuminate\Http\Request;

class SponsorshipController extends AdminController
{
    public function index(Request $request)
    {
        $entries = SponsorshipRequest::query()
            ->with(['category', 'booth'])
            ->where('event_id', $this->event()->id)
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('q'), function ($query) use ($request) {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim($request->string('q'))) .'%';
                $query->where(function ($query) use ($like) {
                    $query->where('code', 'like', $like)
                        ->orWhere('company_name', 'like', $like)
                        ->orWhere('pic_name', 'like', $like)
                        ->orWhere('whatsapp', 'like', $like);
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.sponsorships.index', [
            'entries' => $entries,
            'statuses' => SponsorshipStatus::cases(),
        ]);
    }

    public function show(SponsorshipRequest $sponsorship)
    {
        abort_unless($sponsorship->event_id === $this->event()->id, 404);
        $sponsorship->load(['category', 'booth', 'offers.user', 'event']);

        $booths = Booth::query()
            ->with(['reservation.sponsorshipRequest', 'zone'])
            ->where('event_id', $sponsorship->event_id)
            ->where('type', 'sponsorship')
            ->orderBy('code')
            ->get();

        return view('admin.sponsorships.show', [
            'entry' => $sponsorship,
            'booths' => $booths,
            'statuses' => SponsorshipStatus::cases(),
        ]);
    }

    public function offer(Request $request, SponsorshipRequest $sponsorship, SponsorshipService $service)
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:0'],
            'location_note' => ['nullable', 'string', 'max:160'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $service->addOffer($sponsorship, $request->user(), $data['amount'], $data['location_note'] ?? null, $data['notes'] ?? null);

        return back()->with('status', 'Penawaran harga tersimpan.');
    }

    public function status(Request $request, SponsorshipRequest $sponsorship, SponsorshipService $service)
    {
        $data = $request->validate([
            'status' => ['required', 'in:new,contacted,negotiation,rejected,completed'],
        ]);

        $service->markStatus($sponsorship, SponsorshipStatus::from($data['status']));

        return back()->with('status', 'Status sponsorship diperbarui.');
    }

    public function approve(Request $request, SponsorshipRequest $sponsorship, SponsorshipService $service)
    {
        $data = $request->validate(['booth_id' => ['required', 'integer']]);
        $booth = Booth::query()->findOrFail($data['booth_id']);

        try {
            $service->approve($sponsorship, $booth);
        } catch (BookingException $exception) {
            return back()->withErrors(['booth_id' => $exception->getMessage()]);
        }

        return back()->with('status', 'Kerja sama disetujui dan lokasi ditetapkan.');
    }
}
