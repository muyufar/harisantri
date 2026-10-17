<?php

namespace App\Http\Requests\Concerns;

trait NormalizesWhatsapp
{
    protected function prepareForValidation(): void
    {
        if ($this->has('whatsapp')) {
            $this->merge([
                'whatsapp' => preg_replace('/\D+/', '', (string) $this->input('whatsapp')) ?? '',
            ]);
        }
    }

    /** @return array<string, string> */
    protected function whatsappRule(): array
    {
        return ['whatsapp' => ['required', 'regex:/^(62|0)8[1-9][0-9]{7,11}$/']];
    }
}
