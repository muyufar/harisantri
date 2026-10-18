<?php

namespace App\Http\Middleware;

use App\Services\ExpiryService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReleaseExpiredHolds
{
    public function __construct(private ExpiryService $expiry) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->expiry->releaseExpired();

        return $next($request);
    }
}
