<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gives authenticated staff and admins access to the dashboard and spin history.
 * Admins are always a superset of staff — see User::canAccessStaffTools().
 * Sensitive admin pages stay behind the stricter `admin` middleware.
 */
class EnsureStaffAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user();

        if (! $user || ! $user->canAccessStaffTools()) {
            abort(403, 'Staff access required.');
        }

        return $next($request);
    }
}
