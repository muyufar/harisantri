<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBoothSelectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'area_numbers' => ['required', 'array', 'min:1'],
            'area_numbers.*' => ['integer', 'distinct', 'between:1,8'],
        ];
    }

    public function messages(): array
    {
        return [
            'area_numbers.required' => 'Pilih minimal satu nomor area tenda (1–8).',
            'area_numbers.min' => 'Pilih minimal satu nomor area tenda (1–8).',
            'area_numbers.*.between' => 'Nomor area hanya 1 sampai 8.',
        ];
    }

    /** @return array<int, int> */
    public function areaNumbers(): array
    {
        return collect($this->input('area_numbers', []))
            ->map(fn ($number) => (int) $number)
            ->unique()
            ->values()
            ->all();
    }
}
