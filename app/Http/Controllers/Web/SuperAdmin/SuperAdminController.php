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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
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

    public function destroyTenant($id)
    {
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

        $counts = [
            'total'    => SecurityIncident::count(),
            'open'     => SecurityIncident::where('status', 'OPEN')->count(),
            'critical' => SecurityIncident::where('severity', 'CRITICAL')->count(),
            'blocked'  => BlockedIp::count(),
        ];

        return view('superadmin.security.index', compact('fraudAlerts', 'incidents', 'blockedIps', 'counts'));
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

    public function clearLogs(LogViewerService $logService)
    {
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
}
