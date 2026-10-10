<?php
namespace App\Policies;
use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Expense $expense): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->tenant_id === $expense->tenant_id;
    }
    public function create(User $user): bool { return $user->isAdminSyndic(); }
    public function update(User $user, Expense $expense): bool
    {
        return $user->isAdminSyndic() && $user->tenant_id === $expense->tenant_id;
    }
    public function delete(User $user, Expense $expense): bool
    {
        return $user->isAdminSyndic() && $user->tenant_id === $expense->tenant_id;
    }
}
