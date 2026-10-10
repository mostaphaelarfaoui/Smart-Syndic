<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Payment $payment): bool
    {
        if ($user->isSuperAdmin()) return true;
        if ($user->isAdminSyndic()) return $user->tenant_id === $payment->tenant_id;
        return $user->apartments()->where('apartments.id', $payment->apartment_id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->isAdminSyndic();
    }

    public function update(User $user, Payment $payment): bool
    {
        return $user->isAdminSyndic() && $user->tenant_id === $payment->tenant_id;
    }

    public function delete(User $user, Payment $payment): bool
    {
        return false; // Payments should not be deleted
    }
}
