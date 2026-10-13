<?php

namespace App\Http\Controllers\Web\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        if (Auth::guard('web')->attempt($credentials, $remember)) {
            $request->session()->regenerate();
            $user = Auth::guard('web')->user();

            if (! $user->isActive()) {
                \App\Models\SecurityIncident::log(
                    'SUSPICIOUS_ACCESS',
                    'MEDIUM',
                    "محاولة دخول لحساب معطل أو غير مفعل: {$user->email}",
                    $user->email
                );
                Auth::guard('web')->logout();
                return back()->withErrors(['email' => 'هذا الحساب معطل أو قيد المراجعة.'])->onlyInput('email');
            }

            if ($user->isSuperAdmin()) {
                return redirect()->intended(route('superadmin.dashboard'));
            }

            if ($user->isAdminSyndic()) {
                return redirect()->intended(route('portal.dashboard'));
            }

            // Residents should use mobile app
            Auth::guard('web')->logout();
            return back()->withErrors(['email' => 'هذه البوابة مخصصة لإدارة السانديك فقط. يرجى استخدام تطبيق الهاتف للسكان.'])->onlyInput('email');
        }

        // Record Failed Login Incident
        $ip = $request->ip() ?? '127.0.0.1';
        $email = (string) $request->input('email');
        \App\Models\SecurityIncident::log(
            'FAILED_LOGIN',
            'LOW',
            "محاولة دخول فاشلة باستخدام البريد: {$email}",
            $email
        );

        // Brute-force check: count failed attempts from this IP in the last 15 minutes
        $recentFailures = \App\Models\SecurityIncident::where('ip_address', $ip)
            ->where('incident_type', 'FAILED_LOGIN')
            ->where('created_at', '>=', now()->subMinutes(15))
            ->count();

        if ($recentFailures >= 5) {
            \App\Models\SecurityIncident::log(
                'BRUTE_FORCE',
                'CRITICAL',
                "رصد هجوم تخمين محتمل (Brute Force): {$recentFailures} محاولات دخول فاشلة متتالية خلال 15 دقيقة من عنوان IP: {$ip}",
                $email
            );
        }

        return back()->withErrors([
            'email' => 'بيانات الدخول غير صحيحة. يرجى التأكد من البريد أو كلمة المرور.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login')->with('success', 'تم تسجيل الخروج بنجاح.');
    }
}
