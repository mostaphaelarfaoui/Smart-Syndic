<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;

class TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, Tenant $tenant): bool
    {
        return $user->isSuperAdmin() || $user->tenant_id === $tenant->id;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, Tenant $tenant): bool
    {
        return $user->isSuperAdmin() || ($user->isAdminSyndic() && $user->tenant_id === $tenant->id);
    }

    public function delete(User $user, Tenant $tenant): bool
    {
        return $user->isSuperAdmin();
    }
}
