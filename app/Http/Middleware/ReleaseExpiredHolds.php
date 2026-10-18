<?php

namespace App\Http\Middleware;

use App\Services\ExpiryService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ReleaseExpiredHolds
{
    public function __construct(private ExpiryService $expiry) {}

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! Cache::add('hsn:release-expired', 1, 30)) {
            return;
        }

        $this->expiry->releaseExpired();
    }
}
