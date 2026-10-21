<?php

namespace App\Support;

use App\Models\Event;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class EventConfig
{
    /** @var array<string, string|null> */
    private array $values;

    public function __construct(private Event $event)
    {
        $settings = $event->relationLoaded('settings')
            ? $event->settings
            : $event->settings()->get();

        $this->values = $settings->pluck('value', 'key')->all();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    public function holdHours(): int
    {
        return max(1, (int) $this->get('hold_hours', 2));
    }

    public function maxSlots(): int
    {
        return max(1, (int) $this->get('max_slots_per_applicant', 3));
    }

    public function registrationClosesAt(): ?CarbonInterface
    {
        $value = $this->get('registration_closes_at');

        return $value ? Carbon::parse($value) : null;
    }

    public function registrationPaused(): bool
    {
        return in_array((string) $this->get('registration_paused', '0'), ['1', 'true', 'yes'], true);
    }

    public function registrationClosedMessage(): string
    {
        if ($this->registrationPaused()) {
            $custom = trim((string) $this->get('registration_paused_message', ''));

            return $custom !== ''
                ? $custom
                : 'Pendaftaran sementara ditutup oleh panitia. Silakan cek lagi nanti.';
        }

        return 'Pendaftaran sudah ditutup.';
    }

    /** @return array<int, array{question: string, answer: string}> */
    public function faqs(): array
    {
        $decoded = json_decode((string) $this->get('faqs', '[]'), true);

        return is_array($decoded) ? $decoded : [];
    }

    public function logoUrl(): string
    {
        $path = $this->get('logo_path');

        if ($path) {
            return asset('storage/'.$path);
        }

        $file = public_path('images/logo-nu.png');

        return asset('images/logo-nu.png').'?v='.(is_file($file) ? filemtime($file) : 1);
    }

    public function floorplanUrl(): string
    {
        $path = $this->get('floorplan_path');

        if ($path) {
            return asset('storage/'.$path);
        }

        $file = public_path('images/denah-acara.jpg');

        return asset('images/denah-acara.jpg').'?v='.(is_file($file) ? filemtime($file) : 1);
    }

    public function qrisUrl(): string
    {
        $path = $this->get('qris_path');

        if ($path) {
            return asset('storage/'.$path);
        }

        $file = public_path('images/qris-pcnu-magelang.jpg');

        return asset('images/qris-pcnu-magelang.jpg').'?v='.(is_file($file) ? filemtime($file) : 1);
    }

    public function event(): Event
    {
        return $this->event;
    }
}
