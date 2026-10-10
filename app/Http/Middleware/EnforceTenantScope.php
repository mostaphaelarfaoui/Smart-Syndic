<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnforceTenantScope — Middleware ensuring tenant isolation (BOLA Defense §1.6).
 *
 * Validates that the authenticated user has a valid tenant_id before
 * allowing access to tenant-scoped resources. Super Admins are exempt.
 */
class EnforceTenantScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => __('auth.unauthenticated'),
            ], 401);
        }

        // Super Admins operate outside tenant boundaries
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Non-super-admin users MUST belong to a tenant
        if (! $user->tenant_id) {
            return response()->json([
                'message' => __('auth.no_tenant'),
            ], 403);
        }

        // Verify the tenant is active
        $tenant = $user->tenant;
        if (! $tenant || ! $tenant->isActive()) {
            return response()->json([
                'message' => __('auth.tenant_inactive'),
            ], 403);
        }

        return $next($request);
    }
}
