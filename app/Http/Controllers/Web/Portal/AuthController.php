<?php

namespace App\Http\Controllers\Web\Portal;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SecurityIncident;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();
            if ($user->isSuperAdmin()) {
                return redirect()->route('superadmin.dashboard');
            }
            if ($user->isAdminSyndic()) {
                return redirect()->route('portal.dashboard');
            }
        }

        return view('portal.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required'    => 'يرجى إدخال البريد الإلكتروني',
            'email.email'       => 'صيغة البريد الإلكتروني غير صحيحة',
            'password.required' => 'يرجى إدخال كلمة المرور',
        ]);

        $remember = $request->boolean('remember');
        $email = strtolower(trim((string) $request->input('email')));
        $ip = $request->ip() ?? '127.0.0.1';

        $user = \App\Models\User::where('email', $email)->first();

        // 1. Check if the account is permanently blocked (e.g. exceeded 15 attempts)
        if ($user && $user->isBlocked()) {
            \App\Models\SecurityIncident::log(
                'BLOCKED_LOGIN_ATTEMPT',
                'HIGH',
                "محاولة دخول لحساب محظور أمنياً: {$user->email} من عنوان IP: {$ip}",
                $user->email
            );

            return back()->withErrors([
                'email' => 'هذا الحساب محظور أمنياً لتجاوزه 15 محاولة دخول خاطئة. يرجى التواصل مع المشرف العام (السوبر أدمن) لفك الحظر من خلال لوحة التحكم.',
            ])->onlyInput('email');
        }

        // 2. Check if the account is currently temporarily locked out
        if ($user && $user->isLockedOut()) {
            $remaining = $user->lockoutRemainingMinutes();
            \App\Models\SecurityIncident::log(
                'LOCKED_LOGIN_ATTEMPT',
                'MEDIUM',
                "محاولة دخول لحساب مقفل مؤقتاً: {$user->email} (المتبقي: {$remaining} دقيقة) من IP: {$ip}",
                $user->email
            );

            return back()->withErrors([
                'email' => "تم قفل هذا الحساب مؤقتاً لأسباب أمنية نظراً لتكرار محاولات الدخول الخاطئة. يرجى الانتظار لمدة {$remaining} دقيقة قبل المحاولة مجدداً.",
            ])->onlyInput('email');
        }

        // 3. Check general IP temporary lock for non-registered email brute-force
        if (cache()->has('login_ip_locked:' . $ip)) {
            $lockedUntil = cache()->get('login_ip_locked_until:' . $ip, now()->addMinutes(5));
            $remaining = max(1, (int) ceil(now()->diffInSeconds($lockedUntil) / 60));

            return back()->withErrors([
                'email' => "تم قفل محاولات تسجيل الدخول مؤقتاً من عنوان جهازك لأسباب أمنية. يرجى المحاولة بعد {$remaining} دقيقة.",
            ])->onlyInput('email');
        }

        // 4. Attempt authentication
        if (Auth::guard('web')->attempt($credentials, $remember)) {
            // Authentication successful -> Reset failed counters
            if ($user) {
                $user->update([
                    'failed_login_attempts' => 0,
                    'locked_until'          => null,
                ]);
            }
            cache()->forget('login_fail_ip:' . $ip);

            $request->session()->regenerate();
            $authUser = Auth::guard('web')->user();

            if (! $authUser->isActive()) {
                \App\Models\SecurityIncident::log(
                    'SUSPICIOUS_ACCESS',
                    'MEDIUM',
                    "محاولة دخول لحساب معطل أو غير مفعل: {$authUser->email}",
                    $authUser->email
                );
                Auth::guard('web')->logout();
                return back()->withErrors(['email' => 'هذا الحساب معطل أو قيد المراجعة.'])->onlyInput('email');
            }

            if ($authUser->isSuperAdmin()) {
                return redirect()->intended(route('superadmin.dashboard'));
            }

            if ($authUser->isAdminSyndic()) {
                return redirect()->intended(route('portal.dashboard'));
            }

            // Residents should use mobile app
            Auth::guard('web')->logout();
            return back()->withErrors(['email' => 'هذه البوابة مخصصة لإدارة السانديك فقط. يرجى استخدام تطبيق الهاتف للسكان.'])->onlyInput('email');
        }

        // 5. Authentication FAILED -> Handle Progressive Lockout & Permanent Ban
        \App\Models\SecurityIncident::log(
            'FAILED_LOGIN',
            'LOW',
            "محاولة دخول فاشلة للبريد: {$email} من عنوان IP: {$ip}",
            $email
        );

        if ($user) {
            $attempts = (int) $user->failed_login_attempts + 1;

            // Tier 3: Exceeded or reached 15 attempts -> Permanent Account Ban
            if ($attempts >= 15) {
                $user->update([
                    'failed_login_attempts' => $attempts,
                    'status'                => 'BLOCKED',
                    'blocked_at'            => now(),
                    'blocked_reason'        => 'تجاوز الحد الأقصى لمحاولات تسجيل الدخول (15 محاولة فاشلة متتالية)',
                    'locked_until'          => null,
                ]);

                \Laravel\Sanctum\PersonalAccessToken::where('tokenable_id', $user->id)->delete();

                \App\Models\SecurityIncident::log(
                    'ACCOUNT_PERMANENTLY_BLOCKED',
                    'CRITICAL',
                    "تم حظر حساب المستخدم {$user->email} نهائياً بسبب تجاوز 15 محاولة دخول خاطئة متتالية.",
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
                    ]
                );

                // Notify Super Admins
                app(\App\Services\NotificationService::class)->sendToSuperAdmins(
                    title: 'حظر حساب مستخدم أمنياً 🚨',
                    message: "تم حظر الحساب ({$user->email}) نهائياً لتجاوزه 15 محاولة دخول خاطئة متتالية.",
                    type: 'danger',
                    url: route('superadmin.users.index'),
                    icon: '🛡️',
                    extra: [
                        'target_screen' => 'users',
                        'target_id'     => $user->id,
                    ]
                );

                return back()->withErrors([
                    'email' => 'تم حظر هذا الحساب نهائياً لأسباب أمنية بعد تسجيل 15 محاولة دخول خاطئة. لا يمكن فك الحظر إلا من طرف السوبر أدمن عبر لوحة التحكم.',
                ])->onlyInput('email');
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
                    "تم قفل الحساب {$user->email} مؤقتاً لمدة 10 دقائق بعد تسجيل 10 محاولات دخول فاشلة.",
                    $user->email
                );

                return back()->withErrors([
                    'email' => "تم استنفاد 10 محاولات دخول خاطئة. تم قفل الحساب مؤقتاً لمدة 10 دقائق للحماية القصوى. تنبيه: عند الوصول إلى 15 محاولة سيتم حظر الحساب نهائياً.",
                ])->onlyInput('email');
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
                    "تم قفل الحساب {$user->email} مؤقتاً لمدة 5 دقائق بعد تسجيل 5 محاولات دخول فاشلة.",
                    $user->email
                );

                return back()->withErrors([
                    'email' => "تم استنفاد 5 محاولات دخول خاطئة. تم قفل الحساب مؤقتاً لمدة 5 دقائق للحماية. يرجى الانتظار والمحاولة لاحقاً.",
                ])->onlyInput('email');
            }

            // Other attempt numbers: 1..4, 6..9, 11..14
            $user->update(['failed_login_attempts' => $attempts]);

            $remainingForLock = $attempts < 5 ? (5 - $attempts) : ($attempts < 10 ? (10 - $attempts) : (15 - $attempts));
            $nextStage = $attempts < 5 ? 'قفل مؤقت لمدة 5 دقائق' : ($attempts < 10 ? 'قفل مؤقت لمدة 10 دقائق' : 'الحظر النهائي للحساب');

            return back()->withErrors([
                'email' => "بيانات الدخول غير صحيحة. المحاولة الفاشلة رقم {$attempts} من أصل 15 محاولة (متبقي {$remainingForLock} محاولات قبل {$nextStage}).",
            ])->onlyInput('email');
        }

        // For non-existing users (IP rate-limiting)
        $ipKey = 'login_fail_ip:' . $ip;
        $ipAttempts = (int) cache()->get($ipKey, 0) + 1;
        cache()->put($ipKey, $ipAttempts, now()->addHours(2));

        if ($ipAttempts >= 15) {
            cache()->put('login_ip_locked:' . $ip, true, now()->addMinutes(30));
            cache()->put('login_ip_locked_until:' . $ip, now()->addMinutes(30), now()->addMinutes(30));

            \App\Models\SecurityIncident::log(
                'BRUTE_FORCE_IP',
                'CRITICAL',
                "رصد هجوم تخمين مكثف (15 محاولة) من IP: {$ip}",
                $email
            );

            return back()->withErrors([
                'email' => 'تم قفل محاولات تسجيل الدخول مؤقتاً لمدة 30 دقيقة نظراً لتكرار المحاولات الخاطئة من هذا العنوان.',
            ])->onlyInput('email');
        }

        if ($ipAttempts === 10) {
            cache()->put('login_ip_locked:' . $ip, true, now()->addMinutes(10));
            cache()->put('login_ip_locked_until:' . $ip, now()->addMinutes(10), now()->addMinutes(10));

            return back()->withErrors([
                'email' => 'تم قفل محاولات الدخول مؤقتاً لمدة 10 دقائق بعد تسجيل 10 محاولات فاشلة.',
            ])->onlyInput('email');
        }

        if ($ipAttempts === 5) {
            cache()->put('login_ip_locked:' . $ip, true, now()->addMinutes(5));
            cache()->put('login_ip_locked_until:' . $ip, now()->addMinutes(5), now()->addMinutes(5));

            return back()->withErrors([
                'email' => 'تم قفل محاولات الدخول مؤقتاً لمدة 5 دقائق بعد تسجيل 5 محاولات فاشلة.',
            ])->onlyInput('email');
        }

        return back()->withErrors([
            'email' => "بيانات الدخول غير صحيحة. يرجى التأكد من البريد أو كلمة المرور (المحاولة {$ipAttempts}).",
        ])->onlyInput('email');
    }

    public function verifyPassword(Request $request): JsonResponse
    {
        $password = $request->input('password');

        if (empty($password)) {
            return response()->json([
                'valid'   => false,
                'message' => 'يرجى إدخال كلمة المرور لحسابك للمتابعة.',
            ], 422);
        }

        $user = Auth::guard('web')->user();
        if (! $user) {
            return response()->json([
                'valid'   => false,
                'message' => 'انتهت جلستك، يرجى إعادة تسجيل الدخول.',
            ], 401);
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
            AuditLog::record(
                actorId: $user->id,
                action: 'SECURITY_FAILED_PASSWORD_CONFIRMATION',
                targetTenantId: $user->tenant_id,
                metadata: [
                    'action' => $request->input('action_description', 'حذف حساس'),
                    'ip'     => $request->ip(),
                    'user'   => $user->email,
                ]
            );

            SecurityIncident::log(
                'FAILED_SENSITIVE_ACTION_AUTH',
                'MEDIUM',
                "فشل تأكيد كلمة المرور في نافذة الحذف الحساس للحساب: {$user->email}",
                $user->email
            );

            return response()->json([
                'valid'   => false,
                'message' => 'كلمة المرور المدخلة غير صحيحة! يرجى التأكد وإعادة المحاولة.',
            ], 422);
        }

        return response()->json([
            'valid'   => true,
            'message' => 'تم تأكيد كلمة المرور بنجاح.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login')->with('success', 'تم تسجيل الخروج بنجاح.');
    }
}
