<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * TenantScope — Global query scope enforcing BOLA/IDOR defense.
 *
 * Automatically appends `WHERE tenant_id = ?` to every query on models
 * using the BelongsToTenant trait, ensuring complete tenant data isolation.
 *
 * @see \App\Models\Concerns\BelongsToTenant
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        // Super Admins bypass tenant scoping (they operate across all tenants)
        if ($user->role === 'SUPER_ADMIN') {
            return;
        }

        if ($user->tenant_id) {
            $builder->where($model->getTable() . '.tenant_id', $user->tenant_id);
        } else {
            // Defensive BOLA: Non-super-admin with no tenant_id must never access tenant resources
            $builder->whereRaw('1 = 0');
        }
    }
}
