<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $eventId = DB::table('events')->where('slug', 'hsn-2026')->value('id');

        if (! $eventId) {
            return;
        }

        DB::table('events')->where('id', $eventId)->update([
            'starts_at' => '2026-11-01 07:00:00',
            'venue' => 'Halaman MAJT An-Nuur Kabupaten Magelang',
            'updated_at' => now(),
        ]);

        DB::table('event_settings')->updateOrInsert(
            ['event_id' => $eventId, 'key' => 'registration_closes_at'],
            ['value' => '2026-10-31 23:59:00', 'updated_at' => now(), 'created_at' => now()]
        );
    }

    public function down(): void
    {
        $eventId = DB::table('events')->where('slug', 'hsn-2026')->value('id');

        if (! $eventId) {
            return;
        }

        DB::table('events')->where('id', $eventId)->update([
            'starts_at' => '2026-10-31 07:00:00',
            'venue' => 'Halaman MAJT Kabupaten Magelang',
            'updated_at' => now(),
        ]);

        DB::table('event_settings')->updateOrInsert(
            ['event_id' => $eventId, 'key' => 'registration_closes_at'],
            ['value' => '2026-10-28 23:59:00', 'updated_at' => now(), 'created_at' => now()]
        );
    }
};
