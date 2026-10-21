<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RegistrationStatus;
use App\Models\Registration;
use App\Support\Phone;

class AnnouncementController extends AdminController
{
    public function dateChange()
    {
        $event = $this->event();
        $flyerUrl = asset('images/pengumuman-jadwal-1-november-2026.jpg');

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
            ->map(function (Registration $registration) use ($flyerUrl) {
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

                return [
                    'id' => $registration->id,
                    'code' => $registration->code,
                    'name' => $name,
                    'business' => $business,
                    'status' => $registration->status->label(),
                    'whatsapp' => Phone::display($registration->whatsapp),
                    'url' => Phone::whatsappUrl($registration->whatsapp, $message),
                ];
            })
            ->values();

        return view('admin.announcements.date-change', [
            'event' => $event,
            'flyerUrl' => $flyerUrl,
            'recipients' => $recipients,
            'intervalSeconds' => 8,
        ]);
    }
}
