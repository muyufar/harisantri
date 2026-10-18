<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\BoothMapService;

class BoothFeedController extends Controller
{
    public function __invoke(BoothMapService $maps)
    {
        $event = Event::current();
        abort_unless($event, 404);

        return response()->json([
            'booths' => $maps->present($event),
            'areas' => $maps->areaCards($event),
            'stats' => $maps->stats($event),
        ]);
    }
}
