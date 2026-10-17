<?php

namespace App\Http\Middleware;

use App\Models\Event;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShareEventContext
{
    public function handle(Request $request, Closure $next): Response
    {
        view()->share('currentEvent', Event::current());

        return $next($request);
    }
}
