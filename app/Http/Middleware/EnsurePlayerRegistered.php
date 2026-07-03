<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Requires an authenticated, OTP-verified player on the "player" guard.
 */
class EnsurePlayerRegistered
{
    public function handle(Request $request, Closure $next): Response
    {
        $player = Auth::guard('player')->user();

        if (! $player || ! $player->isVerified()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Please verify your email first.'], 401);
            }

            return redirect()->route('player.register');
        }

        return $next($request);
    }
}
