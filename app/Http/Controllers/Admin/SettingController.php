<?php

namespace App\Http\Controllers\Admin;

use App\Models\EventSetting;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends AdminController
{
    public function edit()
    {
        $event = $this->event();

        return view('admin.settings.edit', [
            'event' => $event,
            'config' => $event->config(),
        ]);
    }

    public function update(Request $request, AuditLogger $audit)
    {
        $event = $this->event();
        $request->merge([
            'contact_email' => $request->input('contact_email') ?: null,
            'instagram_url' => $request->input('instagram_url') ?: null,
            'facebook_url' => $request->input('facebook_url') ?: null,
            'youtube_url' => $request->input('youtube_url') ?: null,
        ]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'activity_name' => ['required', 'string', 'max:160'],
            'starts_at' => ['required', 'date'],
            'venue' => ['required', 'string', 'max:160'],
            'organizer' => ['required', 'string', 'max:160'],
            'tagline' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:2000'],
            'registration_closes_at' => ['required', 'date'],
            'hold_hours' => ['required', 'integer', 'min:1', 'max:168'],
            'max_slots_per_applicant' => ['required', 'integer', 'min:1', 'max:10'],
            'contact_name' => ['required', 'string', 'max:120'],
            'contact_whatsapp' => ['required', 'string', 'max:20'],
            'contact_email' => ['nullable', 'email', 'max:160'],
            'instagram_url' => ['nullable', 'url', 'max:200'],
            'facebook_url' => ['nullable', 'url', 'max:200'],
            'youtube_url' => ['nullable', 'url', 'max:200'],
            'qris_merchant_name' => ['required', 'string', 'max:120'],
            'qris_nmid' => ['required', 'string', 'max:40'],
            'qris_terminal_id' => ['nullable', 'string', 'max:20'],
            'terms' => ['required', 'string', 'max:8000'],
            'cancellation_policy' => ['required', 'string', 'max:4000'],
            'refund_policy' => ['required', 'string', 'max:4000'],
            'faqs' => ['nullable', 'array'],
            'faqs.*.question' => ['nullable', 'string', 'max:200'],
            'faqs.*.answer' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'floorplan' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'qris' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $event->update([
            'name' => $data['name'],
            'activity_name' => $data['activity_name'],
            'starts_at' => $data['starts_at'],
            'venue' => $data['venue'],
            'organizer' => $data['organizer'],
        ]);

        $faqs = collect($data['faqs'] ?? [])
            ->filter(fn ($faq) => filled($faq['question'] ?? null) && filled($faq['answer'] ?? null))
            ->values()
            ->all();

        $settings = [
            'tagline' => $data['tagline'],
            'description' => $data['description'],
            'registration_closes_at' => $data['registration_closes_at'],
            'hold_hours' => (string) $data['hold_hours'],
            'max_slots_per_applicant' => (string) $data['max_slots_per_applicant'],
            'contact_name' => $data['contact_name'],
            'contact_whatsapp' => preg_replace('/\D+/', '', $data['contact_whatsapp']),
            'contact_email' => $data['contact_email'] ?? '',
            'instagram_url' => $data['instagram_url'] ?? '',
            'facebook_url' => $data['facebook_url'] ?? '',
            'youtube_url' => $data['youtube_url'] ?? '',
            'qris_merchant_name' => $data['qris_merchant_name'],
            'qris_nmid' => $data['qris_nmid'],
            'qris_terminal_id' => $data['qris_terminal_id'] ?? '',
            'terms' => $data['terms'],
            'cancellation_policy' => $data['cancellation_policy'],
            'refund_policy' => $data['refund_policy'],
            'faqs' => json_encode($faqs, JSON_UNESCAPED_UNICODE),
        ];

        foreach (['logo' => 'logo_path', 'floorplan' => 'floorplan_path', 'qris' => 'qris_path'] as $input => $key) {
            if ($request->hasFile($input)) {
                $old = $event->config()->get($key);
                if ($old) {
                    Storage::disk('public')->delete($old);
                }
                $settings[$key] = $request->file($input)->store('event', 'public');
            }
        }

        foreach ($settings as $key => $value) {
            EventSetting::query()->updateOrCreate(
                ['event_id' => $event->id, 'key' => $key],
                ['value' => $value]
            );
        }

        $audit->log('event.updated', $event, ['keys' => array_keys($settings)]);

        return back()->with('status', 'Informasi event disimpan.');
    }

    public function toggleRegistration(Request $request, AuditLogger $audit)
    {
        $event = $this->event();
        $pause = $request->boolean('pause');
        $message = trim((string) $request->input('message', ''));

        EventSetting::query()->updateOrCreate(
            ['event_id' => $event->id, 'key' => 'registration_paused'],
            ['value' => $pause ? '1' : '0']
        );

        if ($pause && $message !== '') {
            EventSetting::query()->updateOrCreate(
                ['event_id' => $event->id, 'key' => 'registration_paused_message'],
                ['value' => mb_substr($message, 0, 500)]
            );
        }

        $audit->log($pause ? 'registration.paused' : 'registration.resumed', $event, [
            'paused' => $pause,
        ]);

        return back()->with(
            'status',
            $pause ? 'Pendaftaran sementara ditutup.' : 'Pendaftaran dibuka kembali.'
        );
    }
}
