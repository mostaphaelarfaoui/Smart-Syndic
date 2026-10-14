<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * POST /api/v1/auth/login
     *
     * Authenticates a user and returns a Bearer token with role/tenant metadata.
     */
    public function __invoke(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        // 1. Email not found
        if (! $user) {
            return response()->json([
                'error_type' => 'EMAIL_NOT_FOUND',
                'message'    => 'البريد الإلكتروني غير مسجل في النظام. يرجى التأكد من كتابة البريد بشكل صحيح أو إنشاء حساب جديد.',
                'errors'     => [
                    'email' => ['البريد الإلكتروني غير مسجل في النظام.'],
                ],
            ], 422);
        }

        // 2. Check permanent security block (Tier 3: 15+ attempts)
        if ($user->isBlocked() || $user->status === 'BLOCKED' || $user->blocked_at !== null) {
            \App\Models\SecurityIncident::log(
                'BLOCKED_ACCOUNT_ATTEMPT',
                'HIGH',
                "محاولة تسجيل دخول عبر التطبيق لحساب محظور أمنياً: {$user->email}",
                $user->email
            );

            return response()->json([
                'error_type'     => 'ACCOUNT_BLOCKED',
                'message'        => 'تم حظر هذا الحساب نهائياً لأسباب أمنية لتجاوز الحد الأقصى للمحاولات الخاطئة. لا يمكن فك الحظر إلا من طرف السوبر أدمن عبر لوحة التحكم.',
                'blocked_at'     => $user->blocked_at?->toIso8601String(),
                'blocked_reason' => $user->blocked_reason ?? 'تجاوز الحد الأقصى لمحاولات تسجيل الدخول (15 محاولة فاشلة)',
            ], 403);
        }

        // 3. Check progressive temporary lockout (5 mins / 10 mins)
        if ($user->locked_until && $user->locked_until->isFuture()) {
            $remaining = max(1, (int) ceil(now()->diffInSeconds($user->locked_until) / 60));

            \App\Models\SecurityIncident::log(
                'LOCKED_ACCOUNT_ATTEMPT',
                'MEDIUM',
                "محاولة دخول عبر التطبيق أثناء فترة القفل المؤقت: {$user->email} (متبقي {$remaining} دقيقة)",
                $user->email
            );

            return response()->json([
                'error_type'        => 'ACCOUNT_LOCKED',
                'message'           => "تم قفل هذا الحساب مؤقتاً لأسباب أمنية نظراً لتكرار محاولات الدخول الخاطئة. يرجى الانتظار لمدة {$remaining} دقيقة قبل المحاولة مجدداً.",
                'remaining_minutes' => $remaining,
                'locked_until'      => $user->locked_until->toIso8601String(),
            ], 423);
        }

        // 4. Verify password
        if (! Hash::check($request->password, $user->password)) {
            $attempts = (int) $user->failed_login_attempts + 1;

            // Tier 3: 15 attempts reached -> Permanent Account Ban
            if ($attempts >= 15) {
                $user->update([
                    'failed_login_attempts' => $attempts,
                    'status'                => 'BLOCKED',
                    'blocked_at'            => now(),
                    'blocked_reason'        => 'تجاوز الحد الأقصى لمحاولات تسجيل الدخول (15 محاولة فاشلة متتالية)',
                    'locked_until'          => null,
                ]);

                $user->tokens()->delete();

                \App\Models\SecurityIncident::log(
                    'ACCOUNT_PERMANENTLY_BLOCKED',
                    'CRITICAL',
                    "تم حظر حساب المستخدم {$user->email} نهائياً عبر التطبيق بسبب تجاوز 15 محاولة دخول خاطئة متتالية.",
                    $user->email
                );

                \App\Models\AuditLog::record(
                    actorId: $user->id,
                    action: 'SECURITY_ACCOUNT_BLOCKED_BRUTE_FORCE',
                    targetTenantId: $user->tenant_id,
                    metadata: [
                        'user_id'  => $user->id,
                        'email'    => $user->email,
                        'attempts' => $attempts,
                        'channel'  => 'mobile_api',
                    ]
                );

                // Notify Super Admins
                app(\App\Services\NotificationService::class)->sendToSuperAdmins(
                    title: 'حظر حساب مستخدم أمنياً 🚨',
                    message: "تم حظر الحساب ({$user->email}) نهائياً عبر التطبيق لتجاوزه 15 محاولة دخول خاطئة متتالية.",
                    type: 'danger',
                    url: route('superadmin.users.index'),
                    icon: '🛡️',
                    extra: [
                        'target_screen' => 'users',
                        'target_id'     => $user->id,
                    ]
                );

                return response()->json([
                    'error_type'     => 'ACCOUNT_BLOCKED',
                    'message'        => 'تم حظر هذا الحساب نهائياً لأسباب أمنية بعد تسجيل 15 محاولة دخول خاطئة. لا يمكن فك الحظر إلا من طرف السوبر أدمن عبر لوحة التحكم.',
                    'blocked_at'     => now()->toIso8601String(),
                    'blocked_reason' => 'تجاوز الحد الأقصى لمحاولات تسجيل الدخول (15 محاولة فاشلة متتالية)',
                ], 403);
            }

            // Tier 2: 10 attempts reached -> 10 minutes progressive lockout
            if ($attempts === 10) {
                $user->update([
                    'failed_login_attempts' => $attempts,
                    'locked_until'          => now()->addMinutes(10),
                ]);

                \App\Models\SecurityIncident::log(
                    'LOGIN_LOCKOUT_10MIN',
                    'HIGH',
                    "تم قفل الحساب {$user->email} مؤقتاً لمدة 10 دقائق بعد تسجيل 10 محاولات دخول فاشلة عبر التطبيق.",
                    $user->email
                );

                return response()->json([
                    'error_type'        => 'ACCOUNT_LOCKED',
                    'message'           => 'تم استنفاد 10 محاولات دخول خاطئة. تم قفل الحساب مؤقتاً لمدة 10 دقائق للحماية القصوى. تنبيه: عند الوصول إلى 15 محاولة سيتم حظر الحساب نهائياً.',
                    'remaining_minutes' => 10,
                    'locked_until'      => now()->addMinutes(10)->toIso8601String(),
                ], 423);
            }

            // Tier 1: 5 attempts reached -> 5 minutes lockout
            if ($attempts === 5) {
                $user->update([
                    'failed_login_attempts' => $attempts,
                    'locked_until'          => now()->addMinutes(5),
                ]);

                \App\Models\SecurityIncident::log(
                    'LOGIN_LOCKOUT_5MIN',
                    'MEDIUM',
                    "تم قفل الحساب {$user->email} مؤقتاً لمدة 5 دقائق بعد تسجيل 5 محاولات دخول فاشلة عبر التطبيق.",
                    $user->email
                );

                return response()->json([
                    'error_type'        => 'ACCOUNT_LOCKED',
                    'message'           => 'تم استنفاد 5 محاولات دخول خاطئة. تم قفل الحساب مؤقتاً لمدة 5 دقائق للحماية. يرجى الانتظار والمحاولة لاحقاً.',
                    'remaining_minutes' => 5,
                    'locked_until'      => now()->addMinutes(5)->toIso8601String(),
                ], 423);
            }

            // Other attempt numbers: 1..4, 6..9, 11..14
            $user->update(['failed_login_attempts' => $attempts]);
            $remainingForLock = $attempts < 5 ? (5 - $attempts) : ($attempts < 10 ? (10 - $attempts) : (15 - $attempts));
            $nextStage = $attempts < 5 ? 'قفل مؤقت لمدة 5 دقائق' : ($attempts < 10 ? 'قفل مؤقت لمدة 10 دقائق' : 'الحظر النهائي للحساب');

            \App\Models\SecurityIncident::log(
                'FAILED_LOGIN',
                'LOW',
                "محاولة دخول فاشلة بكلمة مرور خاطئة للمستخدم {$user->email} (المحاولة {$attempts}/15)",
                $user->email
            );

            return response()->json([
                'error_type'         => 'INVALID_PASSWORD',
                'message'            => "كلمة المرور غير صحيحة. المحاولة ({$attempts}/15). بقي لديك {$remainingForLock} محاولات قبل {$nextStage}.",
                'attempts'           => $attempts,
                'remaining_attempts' => $remainingForLock,
                'next_stage'         => $nextStage,
                'errors'             => [
                    'password' => ['كلمة المرور غير صحيحة.'],
                ],
            ], 422);
        }

        // 5. Password matches -> Reset failed counters & check account active status
        $user->update([
            'failed_login_attempts' => 0,
            'locked_until'          => null,
        ]);

        // 6. Block inactive or pending accounts (except Super Admins)
        if ($user->status !== 'ACTIVE' && ! $user->isSuperAdmin()) {
            if ($user->isResident()) {
                $apartment = $user->apartments()->with('building')->first();
                $tenant = $user->tenant;
                $syndic = $tenant ? User::where('tenant_id', $tenant->id)->where('role', 'ADMIN_SYNDIC')->first() : null;

                $isPending = $user->status === 'PENDING';
                $message = $isPending
                    ? 'طلب انضمامك قيد المراجعة حالياً من طرف سانديك الإقامة.'
                    : 'حسابك معطل أو موقوف حالياً. يرجى التواصل مع سانديك الإقامة لإعادة تفعيله.';

                return response()->json([
                    'error_type'    => $isPending ? 'RESIDENT_PENDING_APPROVAL' : 'RESIDENT_SUSPENDED',
                    'message'       => $message,
                    'status'        => $user->status,
                    'role'          => 'RESIDENT',
                    'user'          => [
                        'name'  => $user->full_name,
                        'email' => $user->email,
                    ],
                    'residence'     => $tenant ? [
                        'id'   => $tenant->id,
                        'name' => $tenant->name,
                    ] : null,
                    'apartment'     => $apartment ? [
                        'id'          => $apartment->id,
                        'unit_number' => $apartment->unit_number,
                        'building'    => $apartment->building?->name,
                    ] : null,
                    'syndic'        => $syndic ? [
                        'name'  => $syndic->full_name,
                        'phone' => $syndic->phone,
                        'email' => $syndic->email,
                    ] : null,
                ], 403);
            }

            // For syndics awaiting approval or suspended
            $contactInfo = null;
            if ($user->isAdminSyndic()) {
                $contactInfo = $user->tenant?->contact_info ?? [
                    'email'    => config('smartsyndic.admin_contact.email', 'support@smartsyndic.com'),
                    'phone'    => config('smartsyndic.admin_contact.phone', '+212 600 000 000'),
                    'whatsapp' => config('smartsyndic.admin_contact.whatsapp', '+212 600 000 000'),
                ];
            }

            $isPending = $user->status === 'PENDING';
            $message = $isPending
                ? 'حساب السانديك قيد التحقق والتفعيل من قبل إدارة المنصة.'
                : 'تم إيقاف حسابك مؤقتاً. يرجى التواصل مع إدارة المنصة للاستفسار.';

            return response()->json([
                'error_type'    => $isPending ? 'SYNDIC_PENDING_APPROVAL' : 'SYNDIC_SUSPENDED',
                'message'       => $message,
                'status'        => $user->status,
                'role'          => $user->role,
                'tenant_status' => $user->tenant?->status,
                'contact_info'  => $contactInfo,
            ], 403);
        }

        // Revoke previous tokens for security
        $user->tokens()->delete();

        // Create new Sanctum token
        $token = $user->createToken('api-token', [$user->role])->plainTextToken;

        return response()->json([
            'token'   => $token,
            'user'    => [
                'id'         => $user->id,
                'first_name' => $user->first_name,
                'last_name'  => $user->last_name,
                'email'      => $user->email,
                'phone'      => $user->phone,
                'role'       => $user->role,
                'locale'     => $user->locale,
                'theme'      => $user->theme_preference,
                'status'     => $user->status,
            ],
            'tenant'  => $user->tenant ? [
                'id'                      => $user->tenant->id,
                'name'                    => $user->tenant->name,
                'property_type'           => $user->tenant->property_type,
                'plan_type'               => $user->tenant->plan_type,
                'is_transparency_enabled' => $user->tenant->is_transparency_enabled,
            ] : null,
        ]);
    }
}
