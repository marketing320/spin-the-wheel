<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Requires that the authenticated player has completed the dynamic
 * registration form before reaching the spin experience.
 */
class EnsurePlayerFormCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        $player = Auth::guard('player')->user();

        if ($player && ! $player->hasCompletedForm()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Please complete the registration form first.'], 403);
            }

            return redirect()->route('player.form');
        }

        return $next($request);
    }
}
