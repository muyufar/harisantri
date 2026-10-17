<?php

namespace App\Http\Controllers;

use App\Enums\RegistrationStatus;
use App\Exceptions\BookingException;
use App\Http\Requests\UploadPaymentProofRequest;
use App\Models\Registration;
use App\Services\ManualTransferGateway;
use App\Services\PaymentService;
use App\Services\QrService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class RegistrationPaymentController extends Controller
{
    public function show(Registration $registration, ManualTransferGateway $gateway, QrService $qr)
    {
        $registration->load(['applicant', 'items.reservation', 'payment.verifications.user', 'event']);

        $qrImages = [];
        if ($registration->status === RegistrationStatus::Confirmed) {
            foreach ($registration->items as $item) {
                $token = $qr->token($item);
                $qrImages[$item->id] = [
                    'token' => $token,
                    'image' => $qr->dataUri($token),
                ];
            }
        }

        return view('registration.payment', [
            'registration' => $registration,
            'instructions' => $gateway->instructions($registration),
            'rejection' => $registration->payment?->verifications->where('decision', 'rejected')->last(),
            'qrImages' => $qrImages,
        ]);
    }

    public function storeProof(UploadPaymentProofRequest $request, Registration $registration, PaymentService $payments)
    {
        try {
            $payments->submitProof($registration, $request->file('proof'));
        } catch (BookingException $exception) {
            return back()->withErrors(['proof' => $exception->getMessage()]);
        }

        return redirect()
            ->temporarySignedRoute('register.payment', now()->addDays(7), $registration)
            ->with('status', 'Bukti pembayaran terkirim. Panitia akan memverifikasi.');
    }

    public function pdf(Registration $registration, string $type, QrService $qr)
    {
        abort_unless(in_array($type, ['pengajuan', 'konfirmasi'], true), 404);

        if ($type === 'konfirmasi' && $registration->status !== RegistrationStatus::Confirmed) {
            abort(403, 'Bukti konfirmasi hanya terbit setelah pembayaran disetujui.');
        }

        $registration->load(['applicant', 'items', 'event', 'payment']);
        $codes = [];
        if ($type === 'konfirmasi') {
            foreach ($registration->items as $item) {
                $token = $qr->token($item);
                $codes[] = [
                    'code' => $item->booth_code,
                    'image' => $qr->dataUri($token),
                ];
            }
        }

        return Pdf::loadView('pdf.registration', [
            'registration' => $registration,
            'type' => $type,
            'codes' => $codes,
        ])->download(($type === 'konfirmasi' ? 'konfirmasi-' : 'pengajuan-').$registration->code.'.pdf');
    }

    public function proof(Registration $registration)
    {
        abort_unless(request()->user()?->canPermission('payments.verify') || request()->user()?->canPermission('registrations.view'), 403);
        $path = $registration->payment?->proof_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }

    public function logo(Registration $registration)
    {
        abort_unless(request()->user()?->canPermission('registrations.view'), 403);
        $path = $registration->applicant?->logo_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }
}
