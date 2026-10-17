<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\PriceRule;
use App\Services\BoothMapService;

class HomeController extends Controller
{
    public function __invoke(BoothMapService $maps)
    {
        $event = Event::current();

        return view('public.home', [
            'event' => $event,
            'prices' => $event
                ? PriceRule::query()->where('event_id', $event->id)->where('is_active', true)->orderBy('size')->get()
                : collect(),
            'stats' => $event ? $maps->stats($event) : null,
            'map' => $event ? $maps->payload($event, 'view') : null,
        ]);
    }
}
