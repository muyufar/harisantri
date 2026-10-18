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
            'quantities' => ['sometimes', 'array'],
            'quantities.*' => ['integer', 'min:1', 'max:3'],
            'with_tent' => ['required', 'array'],
            'with_tent.*' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'area_numbers.required' => 'Pilih minimal satu nomor area tenda (1–8).',
            'area_numbers.min' => 'Pilih minimal satu nomor area tenda (1–8).',
            'area_numbers.*.between' => 'Nomor area hanya 1 sampai 8.',
            'with_tent.required' => 'Pilih pakai tenda atau tidak untuk setiap nomor.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            foreach ($this->areaNumbers() as $number) {
                if (! $this->exists('with_tent.'.$number)) {
                    $validator->errors()->add('area_numbers', 'Pilih pakai tenda atau tidak untuk Kotak '.$number.'.');
                }
            }
        });
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

    /** @return array<int, bool> */
    public function tentChoices(): array
    {
        $choices = [];

        foreach ($this->areaNumbers() as $number) {
            $choices[$number] = $this->boolean('with_tent.'.$number);
        }

        return $choices;
    }

    /** @return array<int, int> */
    public function quantities(): array
    {
        $quantities = [];

        foreach ($this->areaNumbers() as $number) {
            $quantities[$number] = min(3, max(1, (int) $this->input('quantities.'.$number, 1)));
        }

        return $quantities;
    }
}
