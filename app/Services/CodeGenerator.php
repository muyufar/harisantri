<?php

namespace App\Services;

use App\Models\Registration;
use App\Models\SponsorshipRequest;

class CodeGenerator
{
    public function make(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $suffix = '';
            for ($i = 0; $i < 6; $i++) {
                $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $code = 'HSN26-'.$suffix;
        } while (
            Registration::query()->where('code', $code)->exists()
            || SponsorshipRequest::query()->where('code', $code)->exists()
        );

        return $code;
    }
}
