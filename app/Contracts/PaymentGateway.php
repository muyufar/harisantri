<?php

namespace App\Contracts;

use App\Models\Registration;

interface PaymentGateway
{
    public function code(): string;

    /** @return array<string, mixed> */
    public function instructions(Registration $registration): array;
}
