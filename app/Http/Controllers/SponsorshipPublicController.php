<?php

namespace App\Http\Controllers;

use App\Exceptions\BookingException;
use App\Http\Requests\StoreSponsorshipRequest;
use App\Models\Event;
use App\Models\SponsorshipRequest;
use App\Services\SponsorshipService;
use App\Support\Phone;

class SponsorshipPublicController extends Controller
{
    public function create()
    {
        $event = Event::current() ?? abort(404);

        return view('sponsorship.create', [
            'event' => $event,
            'categories' => $event->sponsorshipCategories()->where('is_active', true)->orderBy('sort_order')->get(),
            'open' => $event->registrationIsOpen(),
        ]);
    }

    public function store(StoreSponsorshipRequest $request, SponsorshipService $sponsorships)
    {
        $event = Event::current() ?? abort(404);

        try {
            $entry = $sponsorships->submit($event, $request->validated());
        } catch (BookingException $exception) {
            return back()->withInput()->withErrors(['company_name' => $exception->getMessage()]);
        }

        return redirect()->temporarySignedRoute('sponsorship.status', now()->addDays(30), $entry);
    }

    public function status(SponsorshipRequest $sponsorship)
    {
        $sponsorship->load(['category', 'booth', 'offers' => fn ($query) => $query->latest(), 'event']);

        return view('sponsorship.status', [
            'entry' => $sponsorship,
            'whatsapp' => Phone::whatsappUrl(
                (string) $sponsorship->event->config()->get('contact_whatsapp'),
                'Halo panitia HSN 2026, saya ingin menanyakan sponsorship '.$sponsorship->code
            ),
        ]);
    }
}
