<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureTenantActive — Verifies tenant subscription/trial validity.
 *
 * Checks that the authenticated user's tenant is ACTIVE and that
 * their subscription or trial period has not expired.
 */
class EnsureTenantActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isSuperAdmin()) {
            return $next($request);
        }

        $tenant = $user->tenant;

        if (! $tenant) {
            return response()->json([
                'message' => __('auth.no_tenant'),
            ], 403);
        }

        // Check tenant status
        if ($tenant->status === 'SUSPENDED') {
            return response()->json([
                'message' => __('tenant.suspended'),
            ], 403);
        }

        if ($tenant->status === 'EXPIRED') {
            return response()->json([
                'message' => __('tenant.expired'),
            ], 403);
        }

        // Check trial expiration
        if ($tenant->plan_type === 'TRIAL' && $tenant->trial_ends_at?->isPast()) {
            return response()->json([
                'message' => __('tenant.trial_expired'),
            ], 403);
        }

        // Check subscription expiration
        if (in_array($tenant->plan_type, ['PRO', 'ENTERPRISE']) && $tenant->subscription_expires_at?->isPast()) {
            return response()->json([
                'message' => __('tenant.subscription_expired'),
            ], 403);
        }

        return $next($request);
    }
}
