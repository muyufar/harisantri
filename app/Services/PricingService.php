<?php

namespace App\Services;

use App\Exceptions\BookingException;
use App\Models\Booth;
use App\Models\PriceRule;

class PricingService
{
    /** @var array<string, PriceRule> */
    private array $rules = [];

    public function ruleFor(Booth $booth): PriceRule
    {
        if ($booth->type !== 'umkm' || ! $booth->size) {
            throw new BookingException('Harga lapak sponsorship ditentukan panitia, bukan dihitung otomatis.');
        }

        $key = $booth->event_id.'|'.$booth->size;

        if (! isset($this->rules[$key])) {
            $rule = PriceRule::query()
                ->where('event_id', $booth->event_id)
                ->where('size', $booth->size)
                ->where('is_active', true)
                ->first();

            if (! $rule) {
                throw new BookingException('Harga untuk ukuran lapak ini belum diatur panitia.');
            }

            $this->rules[$key] = $rule;
        }

        return $this->rules[$key];
    }

    /** @return array{place_price: int, tent_price: int, tent_fee: int, line_total: int, size: string} */
    public function forBooth(Booth $booth, bool $withTent): array
    {
        $rule = $this->ruleFor($booth);
        $tent = $withTent ? (int) $rule->tent_price : 0;

        return [
            'place_price' => (int) $rule->place_price,
            'tent_price' => $tent,
            'tent_fee' => (int) $rule->tent_price,
            'line_total' => (int) $rule->place_price + $tent,
            'size' => $rule->size,
        ];
    }
}
