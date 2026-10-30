<?php

namespace App\Http\Controllers\Web\Portal;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class SettingsController extends Controller
{
    public function __construct(
        private readonly FileUploadService $uploadService,
    ) {}

    public function index()
    {
        $user = auth()->user();
        $tenant = $user->tenant;
        $currentSessionId = session()->getId();

        $webSessions = \Illuminate\Support\Facades\DB::table('sessions')
            ->where('user_id', $user->id)
            ->orderBy('last_activity', 'desc')
            ->get()
            ->map(function ($s) use ($currentSessionId) {
                $s->is_current = ($s->id === $currentSessionId);
                $s->last_activity_human = \Carbon\Carbon::createFromTimestamp($s->last_activity)->locale('ar')->diffForHumans();
                return $s;
            });

        $mobileTokens = PersonalAccessToken::where('tokenable_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('portal.settings.index', compact('tenant', 'user', 'webSessions', 'mobileTokens'));
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'first_name'       => ['required', 'string', 'max:100'],
            'last_name'        => ['required', 'string', 'max:100'],
            'phone'            => ['nullable', 'string', 'max:30'],
            'locale'           => ['required', 'string', 'in:ar,fr'],
            'theme_preference' => ['required', 'string', 'in:dark,light'],
        ], [
            'first_name.required'       => 'يرجى إدخال الاسم الشخصي.',
            'last_name.required'        => 'يرجى إدخال الاسم العائلي.',
            'locale.required'           => 'يرجى تحديد لغة الواجهة المفضلة.',
            'theme_preference.required' => 'يرجى تحديد المظهر المفضل للواجهة.',
        ]);

        $notifPrefs = [
            'payments'      => $request->boolean('notify_payments'),
            'residents'     => $request->boolean('notify_residents'),
            'polls'         => $request->boolean('notify_polls'),
            'announcements' => $request->boolean('notify_announcements'),
        ];

        $user->update([
            'first_name'               => $validated['first_name'],
            'last_name'                => $validated['last_name'],
            'phone'                    => $validated['phone'] ?? null,
            'locale'                   => $validated['locale'],
            'theme_preference'         => $validated['theme_preference'],
            'notification_preferences' => $notifPrefs,
        ]);

        session(['locale' => $validated['locale']]);

        AuditLog::record(
            actorId: $user->id,
            action: 'USER_PROFILE_UPDATED',
            targetTenantId: $user->tenant_id,
            metadata: [
                'user_id' => $user->id,
                'email'   => $user->email,
                'role'    => $user->role,
                'source'  => 'PORTAL_SETTINGS',
                'ip'      => $request->ip(),
            ]
        );

        return back()->with('success', 'تم تحديث المعلومات الشخصية بنجاح.');
    }

    public function update(Request $request)
    {
        $tenant = auth()->user()->tenant;

        $validated = $request->validate([
            'name'                     => ['required', 'string', 'max:255'],
            'city'                     => ['nullable', 'string', 'max:100'],
            'property_type'            => ['required', 'string'],
            'bank_rib'                 => ['nullable', 'string', 'max:50'],
            'default_monthly_fee'      => ['required', 'numeric', 'min:0'],
            'payment_due_day'          => ['required', 'integer', 'min:1', 'max:28'],
            'receipt_footer_note'      => ['nullable', 'string', 'max:500'],
            'is_transparency_enabled'  => ['boolean'],
            'initial_treasury_balance' => ['nullable', 'numeric'],
            'initial_bank_balance'     => ['nullable', 'numeric'],
        ], [
            'default_monthly_fee.required' => 'يرجى تحديد مبلغ الواجب الشهري الافتراضي.',
            'default_monthly_fee.min'      => 'لا يمكن أن يكون مبلغ الواجب الشهري سالباً.',
            'payment_due_day.required'     => 'يرجى تحديد يوم الاستحقاق الشهري.',
            'payment_due_day.min'          => 'يوم الاستحقاق يجب أن يكون بين 1 و 28.',
            'payment_due_day.max'          => 'يوم الاستحقاق يجب أن يكون بين 1 و 28.',
        ]);

        $updateData = [
            'name'                    => $validated['name'],
            'city'                    => $validated['city'] ?? null,
            'property_type'           => $validated['property_type'],
            'bank_rib'                => $validated['bank_rib'] ?? null,
            'default_monthly_fee'     => $validated['default_monthly_fee'],
            'payment_due_day'         => $validated['payment_due_day'],
            'receipt_footer_note'     => $validated['receipt_footer_note'] ?? null,
            'is_transparency_enabled' => $request->boolean('is_transparency_enabled'),
        ];

        // Only update initial balances if not locked yet
        if (! $tenant->initial_balance_locked) {
            if ($request->filled('initial_treasury_balance')) {
                $updateData['initial_treasury_balance'] = $validated['initial_treasury_balance'];
            }
            if ($request->filled('initial_bank_balance')) {
                $updateData['initial_bank_balance'] = $validated['initial_bank_balance'];
            }
        }

        $tenant->update($updateData);

        return back()->with('success', 'تم حفظ إعدادات الإقامة بنجاح.');
    }

    public function uploadStamp(Request $request)
    {
        $request->validate([
            'stamp' => ['required', 'file', 'image', 'max:2048'],
        ]);

        $tenant = auth()->user()->tenant;

        if ($tenant->stamp_image_path) {
            $this->uploadService->delete($tenant->stamp_image_path);
        }

        $path = $this->uploadService->upload($request->file('stamp'), 'stamps');
        $tenant->update(['stamp_image_path' => $path]);

        return back()->with('success', 'تم رفع وتعيين خاتم السانديك بنجاح. سيظهر تلقائياً على كل التوصيلات PDF.');
    }

    public function deleteStamp(Request $request)
    {
        if ($error = $this->verifySensitivePassword($request, 'حذف خاتم السانديك')) {
            return $error;
        }

        $tenant = auth()->user()->tenant;

        if ($tenant->stamp_image_path) {
            $this->uploadService->delete($tenant->stamp_image_path);
            $tenant->update(['stamp_image_path' => null]);
        }

        return back()->with('success', 'تم حذف خاتم السانديك.');
    }

    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => ['required', 'file', 'image', 'max:2048'],
        ]);

        $tenant = auth()->user()->tenant;

        if ($tenant->logo_path) {
            $this->uploadService->delete($tenant->logo_path);
        }

        $path = $this->uploadService->upload($request->file('logo'), 'logos');
        $tenant->update(['logo_path' => $path]);

        return back()->with('success', 'تم رفع شعار الإقامة بنجاح. سيظهر في ترويسة التوصيلات والتقارير المالية.');
    }

    public function deleteLogo(Request $request)
    {
        if ($error = $this->verifySensitivePassword($request, 'حذف شعار الإقامة')) {
            return $error;
        }

        $tenant = auth()->user()->tenant;

        if ($tenant->logo_path) {
            $this->uploadService->delete($tenant->logo_path);
            $tenant->update(['logo_path' => null]);
        }

        return back()->with('success', 'تم حذف شعار الإقامة.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'يرجى إدخال كلمة المرور الحالية.',
            'password.required'         => 'يرجى إدخال كلمة المرور الجديدة.',
            'password.min'              => 'يجب أن لا تقل كلمة المرور الجديدة عن 8 أحرف.',
            'password.confirmed'        => 'تأكيد كلمة المرور الجديدة غير متطابق.',
        ]);

        $user = auth()->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'كلمة المرور الحالية غير صحيحة.'])->withInput();
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Revoke tokens so any stolen mobile sessions are terminated
        PersonalAccessToken::where('tokenable_id', $user->id)->delete();

        AuditLog::record(
            actorId: $user->id,
            action: 'USER_PASSWORD_UPDATED',
            targetTenantId: $user->tenant_id,
            metadata: [
                'user_id' => $user->id,
                'email'   => $user->email,
                'role'    => $user->role,
                'source'  => 'PORTAL_SETTINGS',
                'ip'      => $request->ip(),
            ]
        );

        return back()->with('success', 'تم تغيير كلمة المرور بنجاح.');
    }

    public function logoutOtherDevices(Request $request)
    {
        $user = auth()->user();
        $currentSessionId = session()->getId();

        // Delete all other web sessions
        \Illuminate\Support\Facades\DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->delete();

        // Delete all mobile access tokens
        PersonalAccessToken::where('tokenable_id', $user->id)->delete();

        AuditLog::record(
            actorId: $user->id,
            action: 'USER_OTHER_SESSIONS_REVOKED',
            targetTenantId: $user->tenant_id,
            metadata: [
                'user_id' => $user->id,
                'source'  => 'PORTAL_SETTINGS',
                'ip'      => $request->ip(),
            ]
        );

        return back()->with('success', 'تم إنهاء وتسجيل الخروج من جميع الأجهزة والجلسات الأخرى بنجاح.');
    }

    public function exportData(Request $request)
    {
        $tenant = auth()->user()->tenant;
        $type = $request->query('type', 'apartments');

        $filename = sprintf('%s-%s-%s.csv', $tenant->slug ?? 'smart-syndic', $type, now()->format('Y-m-d'));

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Cache-Control'       => 'no-store, no-cache',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ];

        return response()->stream(function () use ($tenant, $type) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM so Microsoft Excel renders Arabic properly
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            if ($type === 'apartments') {
                fputcsv($handle, ['رقم الشقة', 'المبنى / العمارة', 'الطابق', 'الواجب الشهري (درهم)', 'حالة الفوترة', 'الشاغل / الساكن', 'الهاتف']);
                $apartments = \App\Models\Apartment::whereHas('building', fn($q) => $q->where('tenant_id', $tenant->id))
                    ->with(['building', 'residents'])
                    ->orderBy('unit_number')
                    ->get();

                foreach ($apartments as $apt) {
                    $resident = $apt->residents->first();
                    fputcsv($handle, [
                        $apt->unit_number,
                        $apt->building?->name ?? 'العمارة الرئيسية',
                        $apt->floor,
                        number_format($apt->monthly_fee, 2, '.', ''),
                        $apt->is_billable ? 'مفعلة بالواجب الشهري' : 'معفاة / فارغة',
                        $resident ? $resident->full_name : 'شاغرة / غير محدد',
                        $resident ? $resident->phone : '-',
                    ]);
                }
            } elseif ($type === 'residents') {
                fputcsv($handle, ['الاسم الشخصي', 'الاسم العائلي', 'الشقة', 'الصفة', 'الهاتف', 'البريد الإلكتروني', 'الحالة', 'تاريخ الانضمام']);
                $residents = \App\Models\User::where('tenant_id', $tenant->id)
                    ->where('role', 'RESIDENT')
                    ->with('apartments')
                    ->orderBy('last_name')
                    ->get();

                foreach ($residents as $res) {
                    $apt = $res->apartments->first();
                    fputcsv($handle, [
                        $res->first_name,
                        $res->last_name,
                        $apt ? $apt->unit_number : '-',
                        $apt?->pivot?->occupant_type === 'TENANT' ? 'مكتري' : 'مالك',
                        $res->phone ?? '-',
                        $res->email,
                        $res->status === 'ACTIVE' ? 'نشط' : ($res->status === 'PENDING' ? 'قيد المراجعة' : 'موقف'),
                        $res->created_at ? $res->created_at->format('Y-m-d') : '-',
                    ]);
                }
            } elseif ($type === 'payments') {
                fputcsv($handle, ['رقم الوصل', 'رقم الشقة', 'المبلغ (درهم)', 'طريقة الأداء', 'المرجع البنكي', 'عن الأشهر', 'الحالة', 'تاريخ الأداء', 'تاريخ التأكيد']);
                $payments = \App\Models\Payment::where('tenant_id', $tenant->id)
                    ->with('apartment')
                    ->orderBy('payment_date', 'desc')
                    ->get();

                foreach ($payments as $pay) {
                    $months = is_array($pay->covered_months) ? implode('، ', $pay->covered_months) : '-';
                    fputcsv($handle, [
                        $pay->receipt_number ?? '-',
                        $pay->apartment?->unit_number ?? '-',
                        number_format($pay->amount_paid, 2, '.', ''),
                        match ($pay->payment_method) {
                            'CASH'          => 'نقداً',
                            'BANK_TRANSFER' => 'تحويل بنكي',
                            'CHEQUE'        => 'شيك بنكي',
                            default         => $pay->payment_method ?? 'أخرى',
                        },
                        $pay->cheque_reference ?? '-',
                        $months,
                        match ($pay->status) {
                            'CONFIRMED' => 'مؤكد',
                            'PENDING'   => 'قيد المراجعة',
                            'REJECTED'  => 'مرفوض',
                            default     => $pay->status ?? '-',
                        },
                        $pay->payment_date ? $pay->payment_date->format('Y-m-d') : '-',
                        $pay->confirmed_at ? $pay->confirmed_at->format('Y-m-d H:i') : '-',
                    ]);
                }
            } elseif ($type === 'expenses') {
                fputcsv($handle, ['عنوان المصروف', 'التصنيف', 'المبنى', 'المبلغ (درهم)', 'طريقة الدفع', 'تاريخ الصرف']);
                $expenses = \App\Models\Expense::where('tenant_id', $tenant->id)
                    ->with('building')
                    ->orderBy('expense_date', 'desc')
                    ->get();

                foreach ($expenses as $exp) {
                    fputcsv($handle, [
                        $exp->title,
                        match ($exp->category) {
                            'CLEANING'    => 'النظافة',
                            'ELECTRICITY' => 'الكهرباء',
                            'WATER'       => 'الماء',
                            'ELEVATOR'    => 'صيانة المصعد',
                            'SECURITY'    => 'الحراسة والأمن',
                            'GARDENING'   => 'البستنة',
                            'REPAIR'      => 'إصلاحات عامة',
                            default       => $exp->category ?? 'مصاريف أخرى',
                        },
                        $exp->building?->name ?? 'العمارة الرئيسية',
                        number_format($exp->amount, 2, '.', ''),
                        match ($exp->payment_method) {
                            'CASH'          => 'نقداً',
                            'BANK_TRANSFER' => 'تحويل بنكي',
                            'CHEQUE'        => 'شيك بنكي',
                            default         => $exp->payment_method ?? 'أخرى',
                        },
                        $exp->expense_date ? $exp->expense_date->format('Y-m-d') : '-',
                    ]);
                }
            }

            fclose($handle);
        }, 200, $headers);
    }
}
