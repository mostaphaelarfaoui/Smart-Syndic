<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BlockedIp;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\SecurityIncident;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Security\FraudDetectorService;
use App\Services\Server\LogViewerService;
use App\Services\Server\ServerHealthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

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
        $tenant->update(['status' => 'ACTIVE']);

        AuditLog::record(
            actorId: auth()->id(),
            action: 'SUPERADMIN_TENANT_APPROVE',
            targetTenantId: $tenant->id,
            metadata: ['tenant_name' => $tenant->name]
        );

        return back()->with('success', "تم تفعيل الإقامة \"{$tenant->name}\" بنجاح وتمكين السانديك من إدارة المنظومة.");
    }

    public function suspendTenant($id)
    {
        $tenant = Tenant::findOrFail($id);
        $tenant->update(['status' => 'SUSPENDED']);

        AuditLog::record(
            actorId: auth()->id(),
            action: 'SUPERADMIN_TENANT_SUSPEND',
            targetTenantId: $tenant->id,
            metadata: ['tenant_name' => $tenant->name]
        );

        return back()->with('success', "تم توقيف إقامة \"{$tenant->name}\".");
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
