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
            'booth_ids' => ['required', 'array', 'min:1'],
            'booth_ids.*' => ['integer', 'distinct'],
            'tents' => ['nullable', 'array'],
            'tents.*' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'booth_ids.required' => 'Pilih minimal satu lapak yang masih tersedia.',
            'booth_ids.min' => 'Pilih minimal satu lapak yang masih tersedia.',
        ];
    }

    /** @return array<int, array{booth_id: int, with_tent: bool}> */
    public function selections(): array
    {
        $tents = $this->input('tents', []);

        return collect($this->input('booth_ids', []))
            ->map(fn ($id) => [
                'booth_id' => (int) $id,
                'with_tent' => (bool) ($tents[$id] ?? false),
            ])
            ->all();
    }
}
