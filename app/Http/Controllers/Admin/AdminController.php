<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;

abstract class AdminController extends Controller
{
    protected function event(): Event
    {
        return Event::current() ?? abort(404, 'Event belum dikonfigurasi.');
    }
}
