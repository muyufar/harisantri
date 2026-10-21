<?php

namespace App\Http\Controllers\Admin;

use App\Models\Booth;
use App\Models\BoothZone;
use App\Services\AuditLogger;
use App\Services\BoothMapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BoothController extends AdminController
{
    public function index()
    {
        $event = $this->event();

        return view('admin.booths.index', [
            'booths' => Booth::query()->with('zone')->where('event_id', $event->id)->orderBy('code')->paginate(30),
            'zones' => $event->zones()->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $event = $this->event();
        $data = $request->validate([
            'zone_id' => ['required', 'integer'],
            'code' => ['required', 'string', 'max:20', Rule::unique('booths', 'code')->where('event_id', $event->id)],
            'type' => ['required', 'in:umkm,sponsorship'],
            'size' => ['nullable', 'required_if:type,umkm', 'in:2x2,3x3'],
            'pos_x' => ['nullable', 'numeric', 'between:0,100'],
            'pos_y' => ['nullable', 'numeric', 'between:0,100'],
        ]);

        $zone = BoothZone::query()->where('event_id', $event->id)->findOrFail($data['zone_id']);
        if ($zone->type !== $data['type']) {
            return back()->withErrors(['zone_id' => 'Tipe lapak harus sama dengan tipe zona.'])->withInput();
        }
        if ($zone->booths()->count() >= $zone->capacity) {
            return back()->withErrors(['zone_id' => 'Kapasitas zona sudah penuh. Naikkan kapasitas terlebih dahulu.'])->withInput();
        }
        if ($data['type'] === 'umkm' && Booth::query()->where('event_id', $event->id)->where('type', 'umkm')->count() >= 70) {
            return back()->withErrors(['code' => 'Tempat UMKM sudah mencapai batas maksimal 70.'])->withInput();
        }

        Booth::create([
            'event_id' => $event->id,
            'zone_id' => $zone->id,
            'code' => strtoupper($data['code']),
            'type' => $data['type'],
            'size' => $data['type'] === 'umkm' ? $data['size'] : null,
            'state' => 'available',
            'pos_x' => $data['pos_x'] ?? 10,
            'pos_y' => $data['pos_y'] ?? 10,
            'width' => $data['type'] === 'sponsorship' ? 12 : 8,
            'height' => 10,
        ]);

        return back()->with('status', 'Slot lapak ditambahkan.');
    }

    public function update(Request $request, Booth $booth, AuditLogger $audit)
    {
        abort_unless($booth->event_id === $this->event()->id, 404);
        $data = $request->validate([
            'state' => ['required', 'in:available,blocked'],
        ]);

        $before = $booth->state;
        $booth->update(['state' => $data['state']]);
        $audit->log('booth.state', $booth, ['code' => $booth->code, 'from' => $before, 'to' => $data['state']]);

        return back()->with('status', 'Status slot diperbarui.');
    }

    public function destroy(Booth $booth)
    {
        abort_unless($booth->event_id === $this->event()->id, 404);
        if ($booth->reservation()->exists() || $booth->registrationItems()->exists()) {
            return back()->withErrors(['booth' => 'Slot yang sudah pernah dipesan tidak dihapus. Nonaktifkan saja.']);
        }

        $booth->delete();

        return back()->with('status', 'Slot dihapus.');
    }

    public function editor(BoothMapService $maps)
    {
        $event = $this->event();

        return view('admin.map.editor', [
            'map' => $maps->payload($event, 'edit'),
        ]);
    }

    public function saveLayout(Request $request, AuditLogger $audit)
    {
        $event = $this->event();
        $data = $request->validate([
            'booths' => ['required', 'array'],
            'booths.*.id' => ['required', 'integer'],
            'booths.*.x' => ['required', 'numeric', 'between:0,100'],
            'booths.*.y' => ['required', 'numeric', 'between:0,100'],
            'booths.*.w' => ['required', 'numeric', 'between:0.6,40'],
            'booths.*.h' => ['required', 'numeric', 'between:0.6,40'],
        ]);

        DB::transaction(function () use ($data, $event) {
            foreach ($data['booths'] as $row) {
                Booth::query()
                    ->where('event_id', $event->id)
                    ->whereKey($row['id'])
                    ->update([
                        'pos_x' => $row['x'],
                        'pos_y' => $row['y'],
                        'width' => $row['w'],
                        'height' => $row['h'],
                    ]);
            }
        });

        $audit->log('map.updated', $event, ['slots' => count($data['booths'])]);

        return response()->json(['ok' => true]);
    }

    public function zones()
    {
        return view('admin.zones.index', [
            'zones' => $this->event()->zones()->withCount('booths')->orderBy('sort_order')->get(),
        ]);
    }

    public function storeZone(Request $request)
    {
        $event = $this->event();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'code' => ['required', 'string', 'max:20', Rule::unique('booth_zones', 'code')->where('event_id', $event->id)],
            'type' => ['required', 'in:umkm,sponsorship'],
            'capacity' => ['required', 'integer', 'min:1', 'max:200'],
        ]);

        BoothZone::create([
            ...$data,
            'code' => strtoupper($data['code']),
            'event_id' => $event->id,
            'sort_order' => (int) $event->zones()->max('sort_order') + 1,
        ]);

        return back()->with('status', 'Zona ditambahkan.');
    }

    public function updateZone(Request $request, BoothZone $zone)
    {
        abort_unless($zone->event_id === $this->event()->id, 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'capacity' => ['required', 'integer', 'min:1', 'max:200'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($data['capacity'] < $zone->booths()->count()) {
            return back()->withErrors(['capacity' => 'Kapasitas tidak boleh lebih kecil dari jumlah slot yang sudah ada.']);
        }

        $zone->update([
            'name' => $data['name'],
            'capacity' => $data['capacity'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', 'Zona diperbarui.');
    }

    public function toggleZone(Request $request, BoothZone $zone, AuditLogger $audit)
    {
        abort_unless($zone->event_id === $this->event()->id, 404);
        abort_unless($zone->type === 'umkm', 404);

        $open = $request->boolean('open');
        $zone->update(['is_active' => $open]);

        $audit->log($open ? 'zone.opened' : 'zone.closed', $zone, [
            'code' => $zone->code,
            'name' => $zone->name,
        ]);

        return back()->with(
            'status',
            $open
                ? $zone->name.' dibuka lagi untuk pendaftaran.'
                : $zone->name.' ditutup sementara dari pilihan pendaftar.'
        );
    }
}
