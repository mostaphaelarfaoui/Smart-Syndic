<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * BelongsToTenant — Trait for all tenant-scoped models.
 *
 * Automatically:
 * 1. Registers the TenantScope global scope (BOLA defense)
 * 2. Sets tenant_id on model creation from the authenticated user
 * 3. Provides the tenant() relationship
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        // ─── Register global scope for automatic WHERE tenant_id = ? ──
        static::addGlobalScope(new TenantScope);

        // ─── Auto-assign tenant_id on creation ────────────────────────
        static::creating(function ($model) {
            if (empty($model->tenant_id) && Auth::check() && Auth::user()->tenant_id) {
                $model->tenant_id = Auth::user()->tenant_id;
            }
        });
    }

    /**
     * Get the tenant (organization) that owns this resource.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
