<?php

namespace App\Http\Controllers\Concerns;

use App\Models\AuditLog;
use App\Models\SecurityIncident;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

trait VerifiesSensitiveAction
{
    /**
     * Verify that the current user (or Super Admin impersonator) confirmed their password
     * before allowing a sensitive destructive action (e.g., permanent deletion, vacating apartment, wiping logs).
     *
     * Returns a RedirectResponse with an error if verification fails, or null if valid.
     */
    protected function verifySensitivePassword(?Request $request = null, string $actionDescription = 'حذف حساس'): ?RedirectResponse
    {
        $request = $request ?? request();
        $password = $request->input('confirm_password') ?? $request->input('password');

        if (empty($password)) {
            return back()->with('error', 'يرجى إدخال كلمة المرور لحسابك لتأكيد هذه العملية الحساسة.');
        }

        $user = auth('web')->user() ?? auth()->user();
        if (! $user) {
            return back()->with('error', 'يجب تسجيل الدخول لتنفيذ هذا الإجراء.');
        }

        $valid = Hash::check($password, $user->password);

        // If impersonated by Super Admin, also accept the Super Admin's password
        if (! $valid && session()->has('impersonator_id')) {
            $impersonator = User::find(session('impersonator_id'));
            if ($impersonator && Hash::check($password, $impersonator->password)) {
                $valid = true;
            }
        }

        if (! $valid) {
            // Log security incident & audit log
            AuditLog::record(
                actorId: $user->id,
                action: 'SECURITY_FAILED_PASSWORD_CONFIRMATION',
                targetTenantId: $user->tenant_id,
                metadata: [
                    'action' => $actionDescription,
                    'ip'     => $request->ip(),
                    'user'   => $user->email,
                ]
            );

            SecurityIncident::log(
                'FAILED_SENSITIVE_ACTION_AUTH',
                'MEDIUM',
                "فشل تأكيد كلمة المرور لمحاولة تنفيذ إجراء حساس ({$actionDescription}) من الحساب: {$user->email}",
                $user->email
            );

            return back()->with('error', 'كلمة المرور المدخلة غير صحيحة! تم إلغاء العملية لحماية البيانات.');
        }

        return null;
    }
}
