<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Models\Registration;

class ManualTransferGateway implements PaymentGateway
{
    public function code(): string
    {
        return 'qris';
    }

    public function instructions(Registration $registration): array
    {
        $config = $registration->event->config();

        return [
            'gateway' => $this->code(),
            'merchant_name' => $config->get('qris_merchant_name', $config->get('bank_account_holder', 'PCNU Kabupaten Magelang')),
            'nmid' => $config->get('qris_nmid', ''),
            'terminal_id' => $config->get('qris_terminal_id', ''),
            'image_url' => $config->qrisUrl(),
            'amount' => $registration->grand_total,
            'code' => $registration->code,
            'note' => 'Scan QRIS di aplikasi berlogo QRIS, bayar sebesar '.rupiah($registration->grand_total).', lalu unggah bukti. Cantumkan kode '.$registration->code.' pada catatan pembayaran jika tersedia.',
        ];
    }
}
