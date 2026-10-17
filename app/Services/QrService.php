<?php

namespace App\Services;

use App\Enums\RegistrationStatus;
use App\Exceptions\BookingException;
use App\Models\RegistrationItem;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class QrService
{
    public function token(RegistrationItem $item): string
    {
        $item->loadMissing('registration');
        $payload = $item->registration->code.'|'.$item->id;
        $signature = substr(hash_hmac('sha256', $payload, (string) config('app.key')), 0, 24);

        return $payload.'|'.$signature;
    }

    public function dataUri(string $payload): string
    {
        $options = new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'outputBase64' => true,
            'scale' => 6,
        ]);

        return (new QRCode($options))->render($payload);
    }

    public function resolve(string $token): RegistrationItem
    {
        $parts = explode('|', trim($token));
        if (count($parts) !== 3) {
            throw new BookingException('Kode QR tidak dikenali.');
        }

        [$code, $itemId, $signature] = $parts;
        $expected = substr(hash_hmac('sha256', $code.'|'.$itemId, (string) config('app.key')), 0, 24);

        if (! hash_equals($expected, $signature)) {
            throw new BookingException('Kode QR tidak sah.');
        }

        $item = RegistrationItem::query()->with(['registration.applicant', 'checkin', 'booth'])->find($itemId);

        if (! $item || $item->registration->code !== $code) {
            throw new BookingException('Data lapak tidak ditemukan.');
        }

        if ($item->registration->status !== RegistrationStatus::Confirmed) {
            throw new BookingException('Lapak belum terkonfirmasi. Check-in hanya untuk pembayaran yang sudah disetujui.');
        }

        return $item;
    }
}
