<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesWhatsapp;
use Illuminate\Foundation\Http\FormRequest;

class StoreApplicantRequest extends FormRequest
{
    use NormalizesWhatsapp;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:120'],
            'business_name' => ['required', 'string', 'max:160'],
            'email' => ['nullable', 'email', 'max:160'],
            'address' => ['required', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:120'],
            'regency' => ['required', 'string', 'max:120'],
            'product_type' => ['required', 'string', 'max:80'],
            'product_description' => ['required', 'string', 'min:10', 'max:1000'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'special_notes' => ['nullable', 'string', 'max:500'],
        ] + $this->whatsappRule();
    }

    public function messages(): array
    {
        return [
            'whatsapp.regex' => 'Masukkan nomor WhatsApp Indonesia yang aktif.',
            'product_description.min' => 'Jelaskan produk yang dijual minimal 10 karakter.',
        ];
    }
}
