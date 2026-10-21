<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RegistrationStatus;
use App\Models\AnnouncementSend;
use App\Models\Registration;
use App\Support\Phone;
use Illuminate\Http\Request;

class AnnouncementController extends AdminController
{
    public function dateChange()
    {
        $event = $this->event();
        $flyerUrl = asset('images/pengumuman-jadwal-1-november-2026.jpg');
        $key = AnnouncementSend::KEY_DATE_CHANGE_1_NOV_2026;

        $sentAtByRegistration = AnnouncementSend::query()
            ->where('event_id', $event->id)
            ->where('key', $key)
            ->pluck('sent_at', 'registration_id');

        $recipients = Registration::query()
            ->with('applicant')
            ->where('event_id', $event->id)
            ->where('type', 'umkm')
            ->whereIn('status', [
                RegistrationStatus::Confirmed,
                RegistrationStatus::AwaitingVerification,
                RegistrationStatus::AwaitingPayment,
                RegistrationStatus::PaymentRejected,
            ])
            ->latest('id')
            ->get()
            ->filter(fn (Registration $registration) => filled($registration->whatsapp) && Phone::normalize($registration->whatsapp) !== '')
            ->map(function (Registration $registration) use ($flyerUrl, $sentAtByRegistration) {
                $name = $registration->applicant?->full_name ?: 'Bapak/Ibu';
                $business = $registration->applicant?->business_name ?: '-';
                $message = "Assalamu'alaikum warahmatullahi wabarakatuh.\n"
                    ."Salam silaturahim.\n\n"
                    ."Yth. {$name}\n"
                    ."Usaha: {$business}\n"
                    ."Kode pendaftaran: {$registration->code}\n\n"
                    ."Mohon maaf atas perubahan jadwal ini.\n"
                    ."Berdasarkan rapat panitia, kegiatan Jalan Sehat & Bazar UMKM HSN 2026 berubah jadwal:\n\n"
                    ."Semula: Sabtu, 31 Oktober 2026\n"
                    ."Menjadi: Minggu, 1 November 2026\n"
                    ."Waktu: 07.00 WIB sampai selesai\n"
                    ."Tempat: tetap Halaman MAJT An-Nuur Kabupaten Magelang\n\n"
                    ."Slot lapak dan pembayaran Bapak/Ibu tetap berlaku.\n"
                    ."Flyer pemberitahuan: {$flyerUrl}\n\n"
                    ."Atas perhatian dan pengertiannya, kami ucapkan terima kasih.\n"
                    ."Wassalamu'alaikum warahmatullahi wabarakatuh.\n"
                    .'— Panitia HSN 2026 PCNU Kabupaten Magelang';

                $sentAt = $sentAtByRegistration->get($registration->id);

                return [
                    'id' => $registration->id,
                    'code' => $registration->code,
                    'name' => $name,
                    'business' => $business,
                    'status' => $registration->status->label(),
                    'whatsapp' => Phone::display($registration->whatsapp),
                    'url' => Phone::whatsappUrl($registration->whatsapp, $message),
                    'sent' => $sentAt !== null,
                    'sent_at' => $sentAt?->timezone('Asia/Jakarta')->format('d/m H:i'),
                    'mark_url' => route('admin.announcements.mark', $registration),
                ];
            })
            ->sortBy([
                fn (array $row) => $row['sent'] ? 1 : 0,
                fn (array $row) => $row['id'] * -1,
            ])
            ->values();

        return view('admin.announcements.date-change', [
            'event' => $event,
            'flyerUrl' => $flyerUrl,
            'recipients' => $recipients,
        ]);
    }

    public function mark(Request $request, Registration $registration)
    {
        $event = $this->event();
        abort_unless($registration->event_id === $event->id, 404);

        $sent = $request->boolean('sent', true);
        $key = AnnouncementSend::KEY_DATE_CHANGE_1_NOV_2026;

        if ($sent) {
            AnnouncementSend::query()->updateOrCreate(
                [
                    'registration_id' => $registration->id,
                    'key' => $key,
                ],
                [
                    'event_id' => $event->id,
                    'sent_by' => $request->user()->id,
                    'sent_at' => now(),
                ]
            );
        } else {
            AnnouncementSend::query()
                ->where('registration_id', $registration->id)
                ->where('key', $key)
                ->delete();
        }

        return response()->json([
            'sent' => $sent,
            'sent_at' => $sent ? now()->timezone('Asia/Jakarta')->format('d/m H:i') : null,
        ]);
    }
}
