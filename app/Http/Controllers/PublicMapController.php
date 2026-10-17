<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\BoothMapService;

class PublicMapController extends Controller
{
    public function __invoke(BoothMapService $maps)
    {
        $event = Event::current();
        abort_unless($event, 404);

        return view('public.map', [
            'event' => $event,
            'stats' => $maps->stats($event),
            'map' => $maps->payload($event, 'view'),
        ]);
    }
}
