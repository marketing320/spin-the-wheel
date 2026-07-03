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

        // A blocked player is signed out immediately.
        if ($player->isBlocked()) {
            Auth::guard('player')->logout();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your access has been disabled.'], 403);
            }

            return redirect()->route('player.register')
                ->with('status', 'Your access has been disabled. Please contact the event staff.');
        }

        // Track presence, at most once per minute to avoid write churn.
        if (! $player->last_seen_at || $player->last_seen_at->lt(now()->subMinute())) {
            $player->forceFill(['last_seen_at' => now()])->saveQuietly();
        }

        return $next($request);
    }
}
