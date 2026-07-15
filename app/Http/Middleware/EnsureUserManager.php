<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts user management (create / edit / delete back-office accounts and
 * the destructive campaign-reset tool) to full admins only. Staff (sales team)
 * accounts can use the limited surface but must never manage other users or
 * wipe production data. Sits on top of the standard auth guard.
 */
class EnsureUserManager
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user();

        if (! $user || ! $user->isAdmin()) {
            abort(403, 'User management requires admin access.');
        }

        return $next($request);
    }
}
