<?php

namespace App\Policies;

use App\Models\Apartment;
use App\Models\User;

class ApartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->isResident() || true; // Residents see their own via scoping
    }

    public function view(User $user, Apartment $apartment): bool
    {
        if ($user->isSuperAdmin()) return true;
        if ($user->isAdminSyndic()) return $user->tenant_id === $apartment->building->tenant_id;
        return $user->apartments()->where('apartments.id', $apartment->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->isAdminSyndic();
    }

    public function update(User $user, Apartment $apartment): bool
    {
        return $user->isAdminSyndic() && $user->tenant_id === $apartment->building->tenant_id;
    }

    public function delete(User $user, Apartment $apartment): bool
    {
        return $user->isAdminSyndic() && $user->tenant_id === $apartment->building->tenant_id;
    }
}
