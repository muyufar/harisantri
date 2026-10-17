<?php

namespace App\Http\Controllers;

use App\Http\Requests\LookupStatusRequest;
use App\Models\Registration;
use App\Models\SponsorshipRequest;
use App\Support\Phone;

class StatusLookupController extends Controller
{
    public function create()
    {
        return view('status.lookup');
    }

    public function store(LookupStatusRequest $request)
    {
        $code = strtoupper(trim($request->string('code')->toString()));
        $phone = Phone::normalize($request->string('whatsapp')->toString());

        $registration = Registration::query()->where('code', $code)->first();
        if ($registration && Phone::matches($registration->whatsapp, $phone)) {
            return redirect()->temporarySignedRoute('register.payment', now()->addHours(6), $registration);
        }

        $sponsorship = SponsorshipRequest::query()->where('code', $code)->first();
        if ($sponsorship && Phone::matches($sponsorship->whatsapp, $phone)) {
            return redirect()->temporarySignedRoute('sponsorship.status', now()->addHours(6), $sponsorship);
        }

        return back()
            ->withInput($request->only('code'))
            ->withErrors(['code' => 'Kode atau nomor WhatsApp tidak sesuai.']);
    }
}
