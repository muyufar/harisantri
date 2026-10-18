<?php

namespace App\Http\Controllers;

use App\Exceptions\BookingException;
use App\Http\Requests\ConfirmRegistrationRequest;
use App\Http\Requests\StoreApplicantRequest;
use App\Http\Requests\StoreBoothSelectionRequest;
use App\Models\Event;
use App\Services\BookingService;
use App\Services\BoothMapService;
use App\Support\Phone;
use Illuminate\Support\Facades\Storage;

class UmkmRegistrationController extends Controller
{
    public function create()
    {
        $event = $this->event();

        return view('registration.applicant', [
            'event' => $event,
            'draft' => session('umkm_draft.applicant', []),
            'open' => $event->registrationIsOpen(),
        ]);
    }

    public function storeApplicant(StoreApplicantRequest $request)
    {
        $event = $this->event();
        abort_unless($event->registrationIsOpen(), 403, 'Pendaftaran sudah ditutup.');

        $data = $request->safe()->except('logo');
        $data['whatsapp'] = Phone::normalize($data['whatsapp']);
        $data['logo_path'] = session('umkm_draft.applicant.logo_path');

        if ($request->hasFile('logo')) {
            if ($data['logo_path']) {
                Storage::disk('local')->delete($data['logo_path']);
            }
            $data['logo_path'] = $request->file('logo')->store('applicant-logos', 'local');
        }

        $draft = session('umkm_draft', []);
        $draft['applicant'] = $data;
        session(['umkm_draft' => $draft]);

        return redirect()->route('register.booths');
    }

    public function booths(BookingService $booking, BoothMapService $maps)
    {
        $event = $this->event();
        $applicant = session('umkm_draft.applicant');
        if (! $applicant) {
            return redirect()->route('register.create');
        }

        $active = $booking->activeSlotCount($event, $applicant['whatsapp']);
        $remaining = max(0, $event->config()->maxSlots() - $active);
        $items = collect(session('umkm_draft.items', []));
        $selected = $items->pluck('area')->filter()->map(fn ($n) => (int) $n)->values()->all();
        $tents = $items
            ->filter(fn ($item) => isset($item['area']))
            ->mapWithKeys(fn ($item) => [(int) $item['area'] => (bool) ($item['with_tent'] ?? false)])
            ->all();

        return view('registration.booths', [
            'event' => $event,
            'applicant' => $applicant,
            'active' => $active,
            'remaining' => $remaining,
            'map' => $maps->payload($event, 'select', $selected, $remaining, $tents),
            'open' => $event->registrationIsOpen(),
        ]);
    }

    public function storeBooths(StoreBoothSelectionRequest $request, BookingService $booking, BoothMapService $maps)
    {
        $event = $this->event();
        $applicant = session('umkm_draft.applicant');
        abort_unless($applicant, 403);

        $areas = $request->areaNumbers();
        $tents = $request->tentChoices();
        $remaining = max(0, $event->config()->maxSlots() - $booking->activeSlotCount($event, $applicant['whatsapp']));

        if (count($areas) > $remaining) {
            return back()->withErrors([
                'area_numbers' => "Sisa kuota nomor WhatsApp ini {$remaining} slot.",
            ])->withInput();
        }

        $selections = [];
        $usedBoothIds = [];

        try {
            foreach ($areas as $area) {
                $booth = $maps->allocateBooth($event, $area, $usedBoothIds);
                $usedBoothIds[] = $booth->id;
                $selections[] = [
                    'booth_id' => $booth->id,
                    'area' => $area,
                    'with_tent' => $tents[$area],
                ];
            }
            $booking->quote($event, $selections);
        } catch (BookingException $exception) {
            return back()->withErrors(['area_numbers' => $exception->getMessage()])->withInput();
        }

        $draft = session('umkm_draft', []);
        $draft['items'] = $selections;
        session(['umkm_draft' => $draft]);

        return redirect()->route('register.confirm');
    }

    public function confirm(BookingService $booking)
    {
        [$event, $applicant, $items] = $this->draftOrRedirect();
        if ($items instanceof \Illuminate\Http\RedirectResponse) {
            return $items;
        }

        try {
            $quote = $booking->quote($event, $items);
        } catch (BookingException $exception) {
            return redirect()->route('register.booths')->withErrors(['area_numbers' => $exception->getMessage()]);
        }

        return view('registration.confirm', [
            'event' => $event,
            'applicant' => $applicant,
            'quote' => $quote,
        ]);
    }

    public function store(ConfirmRegistrationRequest $request, BookingService $booking)
    {
        $event = $this->event();
        $applicant = session('umkm_draft.applicant');
        $items = session('umkm_draft.items', []);
        abort_unless($applicant && $items, 403);

        try {
            $registration = $booking->create($event, $applicant, $items);
        } catch (BookingException $exception) {
            return redirect()->route('register.booths')->withErrors(['area_numbers' => $exception->getMessage()]);
        }

        session()->forget('umkm_draft');

        return redirect()->temporarySignedRoute('register.payment', now()->addDays(7), $registration);
    }

    private function event(): Event
    {
        return Event::current() ?? abort(404, 'Event belum dikonfigurasi.');
    }

    /** @return array{0: Event, 1: array<string, mixed>, 2: array<int, mixed>|\Illuminate\Http\RedirectResponse} */
    private function draftOrRedirect(): array
    {
        $event = $this->event();
        $applicant = session('umkm_draft.applicant');
        $items = session('umkm_draft.items', []);

        if (! $applicant) {
            return [$event, [], redirect()->route('register.create')];
        }

        if ($items === []) {
            return [$event, $applicant, redirect()->route('register.booths')];
        }

        return [$event, $applicant, $items];
    }
}
