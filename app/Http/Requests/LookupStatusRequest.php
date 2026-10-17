<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesWhatsapp;
use Illuminate\Foundation\Http\FormRequest;

class LookupStatusRequest extends FormRequest
{
    use NormalizesWhatsapp;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:40'],
        ] + $this->whatsappRule();
    }
}
