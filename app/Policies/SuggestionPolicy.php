<?php
namespace App\Policies;
use App\Models\Suggestion;
use App\Models\User;

class SuggestionPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Suggestion $suggestion): bool
    {
        if ($user->isSuperAdmin() || $user->isAdminSyndic()) {
            return $user->isSuperAdmin() || $user->tenant_id === $suggestion->tenant_id;
        }
        return $user->id === $suggestion->user_id; // Residents see own only
    }
    public function create(User $user): bool { return $user->isResident(); }
    public function update(User $user, Suggestion $suggestion): bool
    {
        return $user->isAdminSyndic() && $user->tenant_id === $suggestion->tenant_id;
    }
    public function delete(User $user, Suggestion $suggestion): bool { return false; }
}
