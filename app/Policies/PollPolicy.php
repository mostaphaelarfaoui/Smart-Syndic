<?php
namespace App\Policies;
use App\Models\Poll;
use App\Models\User;

class PollPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Poll $poll): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->tenant_id === $poll->tenant_id;
    }
    public function create(User $user): bool { return $user->isAdminSyndic(); }
    public function update(User $user, Poll $poll): bool
    {
        return $user->isAdminSyndic() && $user->tenant_id === $poll->tenant_id;
    }
    public function delete(User $user, Poll $poll): bool
    {
        return $user->isAdminSyndic() && $user->tenant_id === $poll->tenant_id;
    }
}
