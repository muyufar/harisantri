<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesWhatsapp;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSponsorshipRequest extends FormRequest
{
    use NormalizesWhatsapp;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sponsorship_category_id' => ['required', 'integer', Rule::exists('sponsorship_categories', 'id')->where('is_active', true)],
            'company_name' => ['required', 'string', 'max:160'],
            'pic_name' => ['required', 'string', 'max:120'],
            'business_type' => ['required', 'string', 'max:120'],
            'area_requirement' => ['required', 'string', 'max:160'],
            'facility_needs' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ] + $this->whatsappRule();
    }
}
