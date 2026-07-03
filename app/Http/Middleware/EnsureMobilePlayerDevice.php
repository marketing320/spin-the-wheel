<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMobilePlayerDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        $agent = (string) $request->userAgent();

        // Empty agents are allowed for health checks and framework tests.
        $mobileOrTablet = $agent === '' || (bool) preg_match(
            '/Mobile|Android|iPhone|iPad|iPod|Tablet|Silk|Kindle|PlayBook|Opera Mini|IEMobile|webOS/i',
            $agent,
        );

        if (! $mobileOrTablet) {
            return response()->view('desktop-blocked', status: 403);
        }

        return $next($request);
    }
}
