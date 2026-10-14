<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\AuditLog;
use App\Models\BlockedIp;
use App\Models\Building;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Poll;
use App\Models\SecurityIncident;
use App\Models\Suggestion;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Security\FraudDetectorService;
use App\Services\Server\LogViewerService;
use App\Services\Server\ServerHealthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use App\Models\PlatformAnnouncement;
use App\Models\SystemSetting;
use App\Services\System\SystemSettingsService;
use Carbon\Carbon;
use Laravel\Sanctum\PersonalAccessToken;

class SuperAdminController extends Controller
{
    public function dashboard(
        ServerHealthService $healthService,
        FraudDetectorService $fraudDetector
    ) {
        $totalTenants     = Tenant::count();
        $activeTenants    = Tenant::where('status', 'ACTIVE')->count();
        $pendingTenants   = Tenant::where('status', 'PENDING_APPROVAL')->count();
        $suspendedTenants = Tenant::where('status', 'SUSPENDED')->count();

        $totalUsers     = User::count();
        $totalSyndics   = User::where('role', 'ADMIN_SYNDIC')->count();
        $totalResidents = User::where('role', 'RESIDENT')->count();

        $totalPlatformPayments = Payment::where('status', '!=', 'DRAFT')->sum('amount_paid');
        $totalPlatformExpenses = Expense::sum('amount');

        $recentTenants = Tenant::latest('created_at')->limit(6)->get();

        $activeRate = $totalTenants > 0 ? round(($activeTenants / $totalTenants) * 100, 1) : 0;

        // Server Health snapshot
        $systemHealth = $healthService->getSystemHealth();

        // Security snapshot
        $openIncidentsCount = SecurityIncident::where('status', 'OPEN')->count();
        $criticalIncidentsCount = SecurityIncident::where('severity', 'CRITICAL')->where('status', 'OPEN')->count();
        $blockedIpsCount = BlockedIp::count();
        $recentIncidents = SecurityIncident::latest('created_at')->limit(6)->get();

        // Blocked accounts due to security/brute-force
        $blockedUsers = User::where('status', 'BLOCKED')->with('tenant')->latest('blocked_at')->get();
        $blockedUsersCount = $blockedUsers->count();

        // Fraud heuristic alerts
        $fraudAlerts = $fraudDetector->scan();

        return view('superadmin.dashboard', compact(
            'totalTenants',
            'activeTenants',
            'pendingTenants',
            'suspendedTenants',
            'totalUsers',
            'totalSyndics',
            'totalResidents',
            'totalPlatformPayments',
            'totalPlatformExpenses',
            'recentTenants',
            'activeRate',
            'systemHealth',
            'openIncidentsCount',
            'criticalIncidentsCount',
            'blockedIpsCount',
            'blockedUsers',
            'blockedUsersCount',
            'recentIncidents',
            'fraudAlerts'
        ));
    }

    public function tenants(Request $request)
    {
        $query = Tenant::withCount(['apartments', 'users']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $tenants = $query->latest('created_at')->paginate(20)->withQueryString();

        return view('superadmin.tenants.index', compact('tenants'));
    }

    public function users(Request $request)
    {
        $query = User::with('tenant');

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")
                  ->orWhere('last_name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        $totalUsersCount     = User::count();
        $totalSyndicsCount   = User::whereIn('role', ['SYNDIC', 'ADMIN_SYNDIC'])->count();
        $totalResidentsCount = User::where('role', 'RESIDENT')->count();
        $activeUsersCount    = User::where('status', 'ACTIVE')->count();

        $users = $query->latest('created_at')->paginate(20)->withQueryString();
        $tenants = Tenant::orderBy('name')->get();

        return view('superadmin.users.index', compact(
            'users',
            'tenants',
            'totalUsersCount',
            'totalSyndicsCount',
            'totalResidentsCount',
            'activeUsersCount'
        ));
    }

    public function toggleUserStatus($id)
    {
        $user = User::findOrFail($id);

        if ($user->role === 'SUPER_ADMIN') {
            return back()->with('error', 'لا يمكن توقيف حساب السوبر أدمن.');
        }

        $newStatus = $user->status === 'ACTIVE' ? 'SUSPENDED' : 'ACTIVE';
        $user->update(['status' => $newStatus]);

        if ($newStatus === 'SUSPENDED') {
            PersonalAccessToken::where('tokenable_id', $user->id)->delete();
        }

        AuditLog::record(
            actorId: auth()->id(),
            action: 'SUPERADMIN_USER_TOGGLE_STATUS',
            targetTenantId: $user->tenant_id,
            metadata: [
                'target_user_id' => $user->id,
                'target_name'    => $user->full_name,
                'new_status'     => $newStatus,
            ]
        );

        $msg = $newStatus === 'ACTIVE' ? "تم تنشيط حساب {$user->full_name} بنجاح." : "تم توقيف حساب {$user->full_name} وإلغاء تسجيل دخوله فوراً.";
        return back()->with('success', $msg);
    }

    public function resetUserPassword(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'new_password' => 'required|string|min:6',
        ]);

        $user->update([
            'password' => bcrypt($request->new_password),
        ]);

        // Revoke tokens so mobile app forces new login
        PersonalAccessToken::where('tokenable_id', $user->id)->delete();

        AuditLog::record(
            actorId: auth()->id(),
            action: 'SUPERADMIN_USER_PASSWORD_RESET',
            targetTenantId: $user->tenant_id,
            metadata: [
                'target_user_id' => $user->id,
                'target_email'   => $user->email,
            ]
        );

        return back()->with('success', "تم تغيير كلمة المرور للمستخدم {$user->full_name} بنجاح.");
    }

    public function unblockUser($id)
    {
        $user = User::findOrFail($id);

        $prevAttempts = $user->failed_login_attempts;
        $user->update([
            'status'                => 'ACTIVE',
            'failed_login_attempts' => 0,
            'locked_until'          => null,
            'blocked_at'            => null,
            'blocked_reason'        => null,
        ]);

        AuditLog::record(
            actorId: auth()->id(),
            action: 'SUPERADMIN_SECURITY_USER_UNBLOCK',
            targetTenantId: $user->tenant_id,
            metadata: [
                'unblocked_user_id' => $user->id,
                'user_email'        => $user->email,
                'user_name'         => $user->full_name,
                'previous_attempts' => $prevAttempts,
                'unblocked_by'      => auth()->user()->full_name,
            ]
        );

        SecurityIncident::log(
            'ACCOUNT_UNBLOCKED',
            'LOW',
            "تم فك الحظر الأمني عن حساب {$user->email} وإعادة تفعيله وتصفير محاولات الدخول بواسطة السوبر أدمن: " . auth()->user()->full_name,
            $user->email
        );

        return back()->with('success', "تم فك الحظر الأمني بنجاح عن حساب {$user->full_name} ({$user->email}) وتصفير عداد المحاولات وإعادته للعمل.");
    }

    public function approveTenant($id)
    {
        $tenant = Tenant::findOrFail($id);

        DB::transaction(function () use ($tenant) {
            // 1. Activate tenant
            $tenant->update(['status' => 'ACTIVE']);

            // 2. Cascade: Reactivate all users under this tenant (Syndic & Residents)
            User::where('tenant_id', $tenant->id)->update(['status' => 'ACTIVE']);

            AuditLog::record(
                actorId: auth()->id(),
                action: 'SUPERADMIN_TENANT_APPROVE',
                targetTenantId: $tenant->id,
                metadata: ['tenant_name' => $tenant->name]
            );
        });

        return back()->with('success', "تم تفعيل الإقامة \"{$tenant->name}\" وتفعيل كافة حسابات السانديك والسكان التابعة لها بنجاح.");
    }

    public function rejectTenant($id)
    {
        $tenant = Tenant::findOrFail($id);

        DB::transaction(function () use ($tenant) {
            // 1. Reject tenant
            $tenant->update(['status' => 'REJECTED']);

            // 2. Cascade: Reject and disable all users under this tenant
            User::where('tenant_id', $tenant->id)->update(['status' => 'REJECTED']);

            // 3. Revoke any active tokens
            $userIds = User::where('tenant_id', $tenant->id)->pluck('id')->toArray();
            if (!empty($userIds)) {
                PersonalAccessToken::whereIn('tokenable_id', $userIds)->delete();
            }

            AuditLog::record(
                actorId: auth()->id(),
                action: 'SUPERADMIN_TENANT_REJECT',
                targetTenantId: $tenant->id,
                metadata: ['tenant_name' => $tenant->name]
            );
        });

        return back()->with('success', "تم رفض تسجيل سانديك وإقامة \"{$tenant->name}\" وتعطيل كافة الحسابات التابعة لها.");
    }

    public function suspendTenant($id)
    {
        $tenant = Tenant::findOrFail($id);

        $suspendedCount = 0;
        DB::transaction(function () use ($tenant, &$suspendedCount) {
            // 1. Suspend tenant
            $tenant->update(['status' => 'SUSPENDED']);

            // 2. Cascade: Suspend ALL users of this tenant (both Syndic and all Residents)
            $users = User::where('tenant_id', $tenant->id)->get();
            $suspendedCount = $users->count();
            $userIds = $users->pluck('id')->toArray();

            User::where('tenant_id', $tenant->id)->update(['status' => 'SUSPENDED']);

            // 3. Revoke all active API tokens immediately
            if (!empty($userIds)) {
                PersonalAccessToken::whereIn('tokenable_id', $userIds)->delete();
            }

            AuditLog::record(
                actorId: auth()->id(),
                action: 'SUPERADMIN_TENANT_SUSPEND',
                targetTenantId: $tenant->id,
                metadata: [
                    'tenant_name'     => $tenant->name,
                    'suspended_users' => $suspendedCount,
                ]
            );
        });

        return back()->with('success', "تم توقيف إقامة \"{$tenant->name}\" وإيقاف كافة حسابات السانديك والسكان التابعة لها ({$suspendedCount} حساب) عن العمل فوراً.");
    }

    public function destroyTenant(Request $request, $id)
    {
        if ($error = $this->verifySensitivePassword($request, 'حذف إقامة وسانديك نهائياً')) {
            return $error;
        }

        $tenant = Tenant::findOrFail($id);
        $tenantName = $tenant->name;

        DB::transaction(function () use ($tenant, $tenantName) {
            $tenantId = $tenant->id;

            // 1. Gather all users belonging to this tenant
            $users = User::where('tenant_id', $tenantId)->get();
            $userIds = $users->pluck('id')->toArray();

            // 2. Revoke any tokens
            if (!empty($userIds)) {
                PersonalAccessToken::whereIn('tokenable_id', $userIds)->delete();
            }

            // 3. Delete Resident-Apartment bindings
            if (!empty($userIds)) {
                DB::table('resident_apartments')->whereIn('user_id', $userIds)->delete();
            }

            // 4. Delete Payments & Expenses
            Payment::where('tenant_id', $tenantId)->delete();
            Expense::where('tenant_id', $tenantId)->delete();

            // 5. Delete Suggestions & Polls
            Suggestion::where('tenant_id', $tenantId)->delete();
            $pollIds = Poll::where('tenant_id', $tenantId)->pluck('id');
            if ($pollIds->isNotEmpty()) {
                DB::table('poll_votes')->whereIn('poll_id', $pollIds)->delete();
                DB::table('poll_options')->whereIn('poll_id', $pollIds)->delete();
                Poll::where('tenant_id', $tenantId)->delete();
            }

            // 6. Delete Apartments and Buildings
            $buildingIds = Building::where('tenant_id', $tenantId)->pluck('id');
            if ($buildingIds->isNotEmpty()) {
                Apartment::whereIn('building_id', $buildingIds)->delete();
                Building::where('tenant_id', $tenantId)->delete();
            }

            // 7. Delete ALL users (Syndic & all Residents)
            User::where('tenant_id', $tenantId)->delete();

            // 8. Delete stamp file if exists
            if ($tenant->stamp_image_path) {
                Storage::disk('public')->delete($tenant->stamp_image_path);
            }

            // 9. Delete the tenant
            $tenant->delete();

            AuditLog::record(
                actorId: auth()->id(),
                action: 'SUPERADMIN_TENANT_DELETE',
                metadata: [
                    'tenant_name' => $tenantName,
                    'deleted_at'  => now()->toDateTimeString(),
                ]
            );
        });

        return back()->with('success', "تم حذف الإقامة \"{$tenantName}\" وحذف كافة حسابات السانديك والسكان وجميع بياناتها نهائياً.");
    }

    public function impersonateTenant($id)
    {
        $tenant = Tenant::findOrFail($id);

        if ($tenant->status !== 'ACTIVE') {
            return back()->with('error', 'لا يمكن الدخول كمسير لإقامة غير نشطة أو موقوفة.');
        }

        $syndic = User::where('tenant_id', $tenant->id)
            ->whereIn('role', ['SYNDIC', 'ADMIN_SYNDIC'])
            ->where('status', 'ACTIVE')
            ->first();

        if (!$syndic) {
            return back()->with('error', 'لا يوجد حساب سانديك نشط مرتبط بهذه الإقامة حالياً.');
        }

        $superAdmin = auth()->user();

        session([
            'impersonator_id'   => $superAdmin->id,
            'impersonator_name' => $superAdmin->full_name,
        ]);

        AuditLog::record(
            actorId: $superAdmin->id,
            action: 'SUPERADMIN_IMPERSONATE_START',
            targetTenantId: $tenant->id,
            metadata: [
                'target_user_id'    => $syndic->id,
                'target_user_name'  => $syndic->full_name,
                'target_user_email' => $syndic->email,
                'tenant_name'       => $tenant->name,
            ]
        );

        Auth::login($syndic);

        return redirect()->route('portal.dashboard')->with('success', "تم الدخول بنجاح كمسير لإقامة \"{$tenant->name}\". أنت الآن في وضع الدعم الفني.");
    }

    public function impersonateUser($id)
    {
        $user = User::findOrFail($id);

        if ($user->status !== 'ACTIVE') {
            return back()->with('error', 'لا يمكن تقمص حساب غير نشط أو موقوف.');
        }

        if ($user->role === 'SUPER_ADMIN') {
            return back()->with('error', 'لا يمكن تقمص حساب سوبر أدمن آخر.');
        }

        $superAdmin = auth()->user();

        session([
            'impersonator_id'   => $superAdmin->id,
            'impersonator_name' => $superAdmin->full_name,
        ]);

        AuditLog::record(
            actorId: $superAdmin->id,
            action: 'SUPERADMIN_IMPERSONATE_START',
            targetTenantId: $user->tenant_id,
            metadata: [
                'target_user_id'    => $user->id,
                'target_user_name'  => $user->full_name,
                'target_user_email' => $user->email,
                'user_role'         => $user->role,
            ]
        );

        Auth::login($user);

        return redirect()->route('portal.dashboard')->with('success', "تم الدخول بنجاح بصفتك \"{$user->full_name}\".");
    }

    public function leaveImpersonation()
    {
        if (!session()->has('impersonator_id')) {
            return redirect()->route('portal.dashboard');
        }

        $superAdminId = session('impersonator_id');
        $superAdmin = User::findOrFail($superAdminId);
        $impersonatedUser = auth()->user();

        AuditLog::record(
            actorId: $superAdmin->id,
            action: 'SUPERADMIN_IMPERSONATE_STOP',
            targetTenantId: $impersonatedUser?->tenant_id,
            metadata: [
                'returned_from_user' => $impersonatedUser?->full_name,
                'returned_at'        => now()->toDateTimeString(),
            ]
        );

        session()->forget(['impersonator_id', 'impersonator_name']);

        Auth::login($superAdmin);

        return redirect()->route('superadmin.dashboard')->with('success', 'تم إنهاء وضع الدعم الفني والعودة بنجاح إلى لوحة تحكم السوبر أدمن.');
    }

    public function server(ServerHealthService $healthService)
    {
        $health = $healthService->getSystemHealth();

        return view('superadmin.server.index', compact('health'));
    }

    public function vacuumDb(ServerHealthService $healthService)
    {
        $result = $healthService->vacuumDatabase();

        AuditLog::record(
            actorId: auth()->id(),
            action: 'SUPERADMIN_DB_VACUUM',
            metadata: $result
        );

        return back()->with('success', "تم تحسين وتفريغ قاعدة البيانات بنجاح في {$result['duration_ms']}ms (المساحة المسترجعة: {$result['reclaimed_kb']} KB).");
    }

    public function backupDb()
    {
        $dbPath = database_path('database.sqlite');

        if (! file_exists($dbPath)) {
            return back()->with('error', 'ملف قاعدة البيانات غير موجود.');
        }

        $filename = 'smartsyndic-backup-' . now()->format('Y-m-d_H-i-s') . '.sqlite';

        AuditLog::record(
            actorId: auth()->id(),
            action: 'SUPERADMIN_DB_BACKUP_DOWNLOAD',
            metadata: ['filename' => $filename]
        );

        return response()->download($dbPath, $filename, [
            'Content-Type' => 'application/x-sqlite3',
        ]);
    }

    public function security(Request $request, FraudDetectorService $fraudDetector)
    {
        $fraudAlerts = $fraudDetector->scan();

        $incidentQuery = SecurityIncident::with('tenant');

        if ($request->filled('severity')) {
            $incidentQuery->where('severity', $request->severity);
        }

        if ($request->filled('type')) {
            $incidentQuery->where('incident_type', $request->type);
        }

        if ($request->filled('status')) {
            $incidentQuery->where('status', $request->status);
        }

        $incidents = $incidentQuery->latest('created_at')->paginate(20)->withQueryString();

        $blockedIps = BlockedIp::with('blocker')->latest('created_at')->paginate(15, ['*'], 'ips_page');

        $blockedUsers = User::where('status', 'BLOCKED')->with('tenant')->latest('blocked_at')->get();

        $counts = [
            'total'         => SecurityIncident::count(),
            'open'          => SecurityIncident::where('status', 'OPEN')->count(),
            'critical'      => SecurityIncident::where('severity', 'CRITICAL')->count(),
            'blocked'       => BlockedIp::count(),
            'blocked_users' => $blockedUsers->count(),
        ];

        return view('superadmin.security.index', compact('fraudAlerts', 'incidents', 'blockedIps', 'blockedUsers', 'counts'));
    }

    public function resolveIncident($id)
    {
        $incident = SecurityIncident::findOrFail($id);
        $incident->update(['status' => 'RESOLVED']);

        AuditLog::record(
            actorId: auth()->id(),
            action: 'SUPERADMIN_INCIDENT_RESOLVE',
            metadata: ['incident_id' => $incident->id, 'type' => $incident->incident_type]
        );

        return back()->with('success', "تم إغلاق البلاغ الأمني رقم #{$incident->id} بنجاح.");
    }

    public function blockIp(Request $request)
    {
        $data = $request->validate([
            'ip_address' => ['required', 'ip', 'unique:blocked_ips,ip_address'],
            'reason'     => ['required', 'string', 'max:255'],
        ], [
            'ip_address.required' => 'يرجى إدخال عنوان IP صحيح.',
            'ip_address.ip'       => 'صيغة عنوان IP غير صالحة.',
            'ip_address.unique'   => 'هذا العنوان محظور بالفعل.',
            'reason.required'     => 'يرجى كتابة سبب الحظر.',
        ]);

        BlockedIp::create([
            'ip_address' => $data['ip_address'],
            'reason'     => $data['reason'],
            'blocked_by' => auth()->id(),
        ]);

        AuditLog::record(
            actorId: auth()->id(),
            action: 'SUPERADMIN_FIREWALL_BLOCK_IP',
            metadata: $data
        );

        return back()->with('success', "تمت إضافة العنوان {$data['ip_address']} إلى جدار الحماية وحظر وصوله فوراً.");
    }

    public function unblockIp($id)
    {
        $ip = BlockedIp::findOrFail($id);
        $ipAddress = $ip->ip_address;
        $ip->delete();

        AuditLog::record(
            actorId: auth()->id(),
            action: 'SUPERADMIN_FIREWALL_UNBLOCK_IP',
            metadata: ['unblocked_ip' => $ipAddress]
        );

        return back()->with('success', "تم فك الحظر عن العنوان {$ipAddress} بنجاح.");
    }

    public function logs(Request $request, LogViewerService $logService)
    {
        $level = $request->input('level');
        $search = $request->input('search');

        $logs = $logService->getLogs(limit: 100, level: $level, search: $search);
        $fileSize = $logService->getLogFileSize();

        return view('superadmin.logs.index', compact('logs', 'fileSize', 'level', 'search'));
    }

    public function clearLogs(Request $request, LogViewerService $logService)
    {
        if ($error = $this->verifySensitivePassword($request, 'مسح ملف سجلات الأخطاء')) {
            return $error;
        }

        $logService->clear();

        AuditLog::record(
            actorId: auth()->id(),
            action: 'SUPERADMIN_CLEAR_LOGS'
        );

        return back()->with('success', 'تم مسح ملف سجلات الأخطاء بنجاح.');
    }

    public function downloadLogs(LogViewerService $logService)
    {
        $logPath = $logService->getLogPath();

        if (! file_exists($logPath)) {
            return back()->with('error', 'ملف السجلات غير موجود.');
        }

        return response()->download($logPath, 'laravel-' . now()->format('Y-m-d') . '.log');
    }

    public function reports(Request $request)
    {
        $tenants = Tenant::withCount(['apartments', 'users'])
            ->with(['payments' => function ($q) {
                $q->where('status', '!=', 'DRAFT');
            }, 'expenses'])
            ->get()
            ->map(function ($tenant) {
                $totalCollected = $tenant->payments->sum('amount_paid');
                $totalExpenses  = $tenant->expenses->sum('amount');
                $lastPaymentDate = $tenant->payments->max('created_at');
                $isDormant = empty($lastPaymentDate) || $lastPaymentDate < now()->subDays(30);

                return (object) [
                    'id'               => $tenant->id,
                    'name'             => $tenant->name,
                    'city'             => $tenant->city,
                    'status'           => $tenant->status,
                    'apartments_count' => $tenant->apartments_count,
                    'users_count'      => $tenant->users_count,
                    'total_collected'  => $totalCollected,
                    'total_expenses'   => $totalExpenses,
                    'net_treasury'     => $totalCollected - $totalExpenses,
                    'last_payment_date'=> $lastPaymentDate,
                    'is_dormant'       => $isDormant,
                ];
            });

        $topCollectedTenants = $tenants->sortByDesc('total_collected')->take(5);
        $dormantTenants = $tenants->where('is_dormant', true);

        $summary = [
            'total_tenants'    => $tenants->count(),
            'active_tenants'   => $tenants->where('status', 'ACTIVE')->count(),
            'dormant_count'    => $dormantTenants->count(),
            'total_collected'  => $tenants->sum('total_collected'),
            'total_expenses'   => $tenants->sum('total_expenses'),
            'platform_net'     => $tenants->sum('net_treasury'),
        ];

        return view('superadmin.reports.index', compact('tenants', 'topCollectedTenants', 'dormantTenants', 'summary'));
    }

    public function exportReportCsv()
    {
        $tenants = Tenant::withCount(['apartments', 'users'])
            ->with(['payments' => function ($q) {
                $q->where('status', '!=', 'DRAFT');
            }, 'expenses'])
            ->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="smartsyndic-platform-report-' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($tenants) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel Arabic support

            fputcsv($handle, [
                'اسم الإقامة',
                'المدينة',
                'الحالة',
                'عدد الشقق',
                'عدد المستخدمين',
                'مجموع المقبوضات (درهم)',
                'مجموع المصاريف (درهم)',
                'صافي الصندوق (درهم)',
                'تاريخ آخر حركة مالية',
            ]);

            foreach ($tenants as $t) {
                $collected = $t->payments->sum('amount_paid');
                $expenses = $t->expenses->sum('amount');
                $lastDate = $t->payments->max('created_at');

                fputcsv($handle, [
                    $t->name,
                    $t->city ?? '-',
                    $t->status,
                    $t->apartments_count,
                    $t->users_count,
                    number_format($collected, 2, '.', ''),
                    number_format($expenses, 2, '.', ''),
                    number_format($collected - $expenses, 2, '.', ''),
                    $lastDate ? $lastDate->format('Y-m-d H:i') : 'لا توجد حركات',
                ]);
            }

            fclose($handle);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function auditLogs(Request $request)
    {
        $query = AuditLog::with('actor');

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        $logs = $query->latest('created_at')->paginate(30)->withQueryString();

        return view('superadmin.audit.index', compact('logs'));
    }

    // ─── Feature 3: Announcements & Broadcasts ───────────────────────────────
    public function announcements(Request $request)
    {
        $query = PlatformAnnouncement::with(['tenant', 'creator']);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('audience')) {
            $query->where('target_audience', $request->audience);
        }

        $announcements = $query->latest('created_at')->paginate(15)->withQueryString();
        $tenants = Tenant::where('status', 'ACTIVE')->orderBy('name')->get();

        $totalCount  = PlatformAnnouncement::count();
        $activeCount = PlatformAnnouncement::where('is_active', true)->count();

        return view('superadmin.announcements.index', compact('announcements', 'tenants', 'totalCount', 'activeCount'));
    }

    public function storeAnnouncement(Request $request)
    {
        $request->validate([
            'title'           => 'required|string|max:255',
            'message'         => 'required|string',
            'type'            => 'required|in:INFO,WARNING,DANGER,SUCCESS',
            'target_audience' => 'required|in:ALL,SYNDICS_ONLY,RESIDENTS_ONLY,SPECIFIC_TENANT',
            'tenant_id'       => 'nullable|required_if:target_audience,SPECIFIC_TENANT|uuid|exists:tenants,id',
            'starts_at'       => 'nullable|date',
            'expires_at'      => 'nullable|date|after_or_equal:starts_at',
        ]);

        $announcement = PlatformAnnouncement::create([
            'title'           => $request->title,
            'message'         => $request->message,
            'type'            => $request->type,
            'target_audience' => $request->target_audience,
            'tenant_id'       => $request->target_audience === 'SPECIFIC_TENANT' ? $request->tenant_id : null,
            'is_active'       => $request->boolean('is_active', true),
            'is_dismissible'  => $request->boolean('is_dismissible', true),
            'starts_at'       => $request->starts_at,
            'expires_at'      => $request->expires_at,
            'created_by'      => auth()->id(),
        ]);

        AuditLog::record(
            actorId: auth()->id(),
            action: 'SUPERADMIN_BROADCAST_CREATE',
            targetTenantId: $announcement->tenant_id,
            metadata: [
                'announcement_id' => $announcement->id,
                'title'           => $announcement->title,
                'audience'        => $announcement->target_audience,
            ]
        );

        return back()->with('success', 'تم نشر الإعلان / الإشعار الجماعي بنجاح.');
    }

    public function toggleAnnouncement($id)
    {
        $announcement = PlatformAnnouncement::findOrFail($id);
        $announcement->update(['is_active' => !$announcement->is_active]);

        AuditLog::record(
            actorId: auth()->id(),
            action: 'SUPERADMIN_BROADCAST_TOGGLE',
            targetTenantId: $announcement->tenant_id,
            metadata: [
                'announcement_id' => $announcement->id,
                'new_status'      => $announcement->is_active,
            ]
        );

        $statusStr = $announcement->is_active ? 'تفعيل' : 'إيقاف';
        return back()->with('success', "تم {$statusStr} الإعلان بنجاح.");
    }

    public function destroyAnnouncement(Request $request, $id)
    {
        if ($error = $this->verifySensitivePassword($request, 'حذف إعلان جماعي')) {
            return $error;
        }

        $announcement = PlatformAnnouncement::findOrFail($id);
        $title = $announcement->title;
        $announcement->delete();

        AuditLog::record(
            actorId: auth()->id(),
            action: 'SUPERADMIN_BROADCAST_DELETE',
            metadata: ['title' => $title]
        );

        return back()->with('success', 'تم حذف الإعلان الجماعي نهائياً.');
    }

    // ─── Feature 4: SaaS Subscription Plans & Quotas ──────────────────────────
    public function subscriptions(Request $request)
    {
        $query = Tenant::withCount(['apartments', 'users']);

        if ($request->filled('plan_type')) {
            $query->where('plan_type', $request->plan_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        if ($request->boolean('expiring_soon')) {
            $query->where(function ($q) {
                $q->whereBetween('subscription_expires_at', [now(), now()->addDays(14)])
                  ->orWhereBetween('trial_ends_at', [now(), now()->addDays(14)]);
            });
        }

        $tenants = $query->orderBy('name')->paginate(20)->withQueryString();

        // Calculate SaaS metrics
        $totalTenants        = Tenant::count();
        $trialTenants        = Tenant::where('plan_type', 'TRIAL')->count();
        $proTenants          = Tenant::where('plan_type', 'PRO')->count();
        $enterpriseTenants   = Tenant::where('plan_type', 'ENTERPRISE')->count();
        
        $expiringSoonCount   = Tenant::where(function ($q) {
            $q->whereBetween('subscription_expires_at', [now(), now()->addDays(14)])
              ->orWhereBetween('trial_ends_at', [now(), now()->addDays(14)]);
        })->count();

        // Pro: 299 DH/month, Enterprise: 799 DH/month
        $estimatedMRR = ($proTenants * 299) + ($enterpriseTenants * 799);

        $stats = [
            'totalTenants'      => $totalTenants,
            'trialTenants'      => $trialTenants,
            'proTenants'        => $proTenants,
            'enterpriseTenants' => $enterpriseTenants,
            'expiringSoonCount' => $expiringSoonCount,
            'estimatedMRR'      => $estimatedMRR,
        ];

        return view('superadmin.subscriptions.index', compact('tenants', 'stats'));
    }

    public function updateSubscription(Request $request, $id)
    {
        $tenant = Tenant::findOrFail($id);

        $request->validate([
            'plan_type'               => 'required|in:TRIAL,PRO,ENTERPRISE',
            'status'                  => 'required|in:ACTIVE,PENDING_APPROVAL,SUSPENDED,EXPIRED',
            'trial_ends_at'           => 'nullable|date',
            'subscription_expires_at' => 'nullable|date',
        ]);

        $tenant->update([
            'plan_type'               => $request->plan_type,
            'status'                  => $request->status,
            'trial_ends_at'           => $request->trial_ends_at,
            'subscription_expires_at' => $request->subscription_expires_at,
        ]);

        AuditLog::record(
            actorId: auth()->id(),
            action: 'SUPERADMIN_SUBSCRIPTION_UPDATE',
            targetTenantId: $tenant->id,
            metadata: [
                'tenant_name' => $tenant->name,
                'plan_type'   => $tenant->plan_type,
                'status'      => $tenant->status,
            ]
        );

        return back()->with('success', "تم تحديث خطة الاشتراك وحالة الإقامة \"{$tenant->name}\" بنجاح.");
    }

    public function extendSubscription(Request $request, $id)
    {
        $tenant = Tenant::findOrFail($id);

        $request->validate([
            'days' => 'required|integer|in:30,90,180,365',
        ]);

        $days = (int) $request->days;

        if ($tenant->plan_type === 'TRIAL') {
            $base = $tenant->trial_ends_at && Carbon::parse($tenant->trial_ends_at)->isFuture()
                ? Carbon::parse($tenant->trial_ends_at)
                : now();
            $tenant->update(['trial_ends_at' => $base->addDays($days)]);
        } else {
            $base = $tenant->subscription_expires_at && Carbon::parse($tenant->subscription_expires_at)->isFuture()
                ? Carbon::parse($tenant->subscription_expires_at)
                : now();
            $tenant->update(['subscription_expires_at' => $base->addDays($days)]);
        }

        AuditLog::record(
            actorId: auth()->id(),
            action: 'SUPERADMIN_SUBSCRIPTION_EXTEND',
            targetTenantId: $tenant->id,
            metadata: [
                'tenant_name' => $tenant->name,
                'added_days'  => $days,
            ]
        );

        return back()->with('success', "تم تمديد اشتراك إقامة \"{$tenant->name}\" بمقدار {$days} يوماً بنجاح.");
    }

    // ─── Feature 5: System Settings & Feature Flags & Maintenance ────────────
    public function settings(SystemSettingsService $settingsService)
    {
        $settingsGrouped = $settingsService->getAllGrouped();

        return view('superadmin.settings.index', compact('settingsGrouped'));
    }

    public function updateSettings(Request $request, SystemSettingsService $settingsService)
    {
        $data = $request->except(['_token', 'maintenance_mode_enabled']);

        // Handle boolean checkboxes in features group
        $booleanKeys = [
            'allow_new_registrations',
            'enable_online_payments',
            'enable_resident_polls',
            'enable_incident_reporting',
        ];

        foreach ($booleanKeys as $bKey) {
            $val = $request->has($bKey) ? '1' : '0';
            $settingsService->set($bKey, $val, 'boolean', 'features');
        }

        // Handle text/general fields
        foreach ($data as $key => $value) {
            if (!in_array($key, $booleanKeys)) {
                $setting = SystemSetting::where('key', $key)->first();
                if ($setting) {
                    $settingsService->set($key, (string)$value, $setting->type, $setting->group, $setting->description);
                }
            }
        }

        AuditLog::record(
            actorId: auth()->id(),
            action: 'SUPERADMIN_SETTINGS_UPDATE',
            metadata: ['updated_by' => auth()->user()->full_name]
        );

        return back()->with('success', 'تم حفظ إعدادات النظام وعلامات الميزات بنجاح.');
    }

    public function toggleMaintenance(Request $request, SystemSettingsService $settingsService)
    {
        $currentState = $settingsService->isMaintenanceMode();
        $newState = !$currentState;

        $settingsService->set('maintenance_mode_enabled', $newState ? '1' : '0', 'boolean', 'maintenance', 'تفعيل وضع الصيانة العام');
        
        if ($request->filled('maintenance_message')) {
            $settingsService->set('maintenance_message', $request->maintenance_message, 'string', 'maintenance');
        }

        AuditLog::record(
            actorId: auth()->id(),
            action: 'SUPERADMIN_MAINTENANCE_TOGGLE',
            metadata: [
                'enabled' => $newState,
                'message' => $request->maintenance_message ?? '',
            ]
        );

        $stateMsg = $newState ? 'تم تفعيل وضع الصيانة العام على المنصة.' : 'تم إلغاء وضع الصيانة وعودة المنصة للعمل الطبيعي.';
        return back()->with('success', $stateMsg);
    }
}

