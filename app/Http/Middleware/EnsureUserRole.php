<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureUserRole — Named middleware for role-based access control.
 *
 * Usage in routes: ->middleware('role:ADMIN_SYNDIC')
 *                  ->middleware('role:SUPER_ADMIN,ADMIN_SYNDIC')
 */
class EnsureUserRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => __('auth.unauthenticated'),
            ], 401);
        }

        if (! in_array($user->role, $roles, true)) {
            return response()->json([
                'message' => __('auth.forbidden'),
            ], 403);
        }

        // Block non-active users from accessing protected resources
        if ($user->status !== 'ACTIVE' && $user->role !== 'SUPER_ADMIN') {
            return response()->json([
                'message' => __('auth.account_inactive'),
            ], 403);
        }

        return $next($request);
    }
}
