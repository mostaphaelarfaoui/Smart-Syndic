<?php

namespace App\Policies;

use App\Models\Building;
use App\Models\User;

class BuildingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdminSyndic();
    }

    public function view(User $user, Building $building): bool
    {
        return $user->isSuperAdmin() || $user->tenant_id === $building->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->isAdminSyndic();
    }

    public function update(User $user, Building $building): bool
    {
        return $user->isAdminSyndic() && $user->tenant_id === $building->tenant_id;
    }

    public function delete(User $user, Building $building): bool
    {
        return $user->isAdminSyndic() && $user->tenant_id === $building->tenant_id;
    }
}
