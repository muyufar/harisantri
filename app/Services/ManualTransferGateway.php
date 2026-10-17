<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Models\Registration;

class ManualTransferGateway implements PaymentGateway
{
    public function code(): string
    {
        return 'manual';
    }

    public function instructions(Registration $registration): array
    {
        $config = $registration->event->config();

        return [
            'gateway' => $this->code(),
            'bank' => $config->get('bank_name'),
            'account_number' => $config->get('bank_account_number'),
            'account_holder' => $config->get('bank_account_holder'),
            'amount' => $registration->grand_total,
            'code' => $registration->code,
            'note' => 'Cantumkan kode '.$registration->code.' pada berita transfer.',
        ];
    }
}
