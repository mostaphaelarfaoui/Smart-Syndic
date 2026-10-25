<?php

namespace App\Http\Controllers\Web\Portal;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Expense;
use App\Models\Payment;
use App\Services\FinancialReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FinancialReportController extends Controller
{
    public function __construct(
        protected FinancialReportService $reportService
    ) {}

    /**
     * Reports & Analytics Center landing hub.
     */
    public function index(Request $request)
    {
        $tenant = auth()->user()->tenant;
        if (! $tenant) {
            abort(403, 'غير مصرح: لا توجد إقامة مرتبطة بهذا الحساب.');
        }

        $tenantId = $tenant->id;
        $tab = $request->input('tab', 'reports');

        // 1. PDF Reports Data
        $currentMonth = now()->format('Y-m');
        $lastMonth = now()->subMonth()->format('Y-m');
        $currentYear = now()->format('Y');

        $currentMonthData = $this->reportService->buildReportData($tenant, $currentMonth, $currentMonth, 'both', auth()->user());
        $lastMonthData    = $this->reportService->buildReportData($tenant, $lastMonth, $lastMonth, 'both', auth()->user());

        // 2. Annual Analytics Data
        $year = (int) $request->input('year', now()->year);
        $monthlyBreakdown = [];
        $arabicMonths = [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
            5 => 'مايو', 6 => 'يونيو', 7 => 'يوليوز', 8 => 'غشت',
            9 => 'شتنبر', 10 => 'أكتوبر', 11 => 'نونبر', 12 => 'دجنبر'
        ];

        $chartLabels   = [];
        $chartInflows  = [];
        $chartOutflows = [];
        $totalYearInflow  = 0;
        $totalYearOutflow = 0;

        for ($m = 1; $m <= 12; $m++) {
            $monthStr = sprintf('%04d-%02d', $year, $m);
            $monthName = $arabicMonths[$m];

            $inflow = (float) Payment::where('tenant_id', $tenantId)
                ->where('status', '!=', 'DRAFT')
                ->whereYear('payment_date', $year)
                ->whereMonth('payment_date', $m)
                ->sum('amount_paid');

            $outflow = (float) Expense::where('tenant_id', $tenantId)
                ->whereYear('expense_date', $year)
                ->whereMonth('expense_date', $m)
                ->sum('amount');

            $net = $inflow - $outflow;

            $totalYearInflow  += $inflow;
            $totalYearOutflow += $outflow;

            $monthlyBreakdown[] = [
                'month_num'  => $m,
                'month_name' => $monthName,
                'month_str'  => $monthStr,
                'inflow'     => $inflow,
                'outflow'    => $outflow,
                'net'        => $net,
            ];

            $chartLabels[]   = $monthName;
            $chartInflows[]  = $inflow;
            $chartOutflows[] = $outflow;
        }

        $totalYearNet = $totalYearInflow - $totalYearOutflow;

        $categoriesConfig = [
            'CLEANING'          => ['label' => '🧹 النظافة', 'color' => '#10b981'],
            'ELEVATOR'          => ['label' => '🛗 المصعد', 'color' => '#0ea5e9'],
            'SECURITY'          => ['label' => '🔒 الحراسة', 'color' => '#f43f5e'],
            'WATER_ELECTRICITY' => ['label' => '💡 الماء والكهرباء', 'color' => '#f59e0b'],
            'GARDENING'         => ['label' => '🌿 البستنة', 'color' => '#84cc16'],
            'MAINTENANCE'       => ['label' => '🔧 الصيانة العامة', 'color' => '#8b5cf6'],
            'OTHER'             => ['label' => '📋 مصاريف أخرى', 'color' => '#64748b'],
        ];

        $categoryLabels = [];
        $categoryData   = [];
        $categoryColors = [];

        foreach ($categoriesConfig as $key => $conf) {
            $sum = (float) Expense::where('tenant_id', $tenantId)
                ->whereYear('expense_date', $year)
                ->where('category', $key)
                ->sum('amount');

            if ($sum > 0) {
                $categoryLabels[] = $conf['label'];
                $categoryData[]   = $sum;
                $categoryColors[] = $conf['color'];
            }
        }

        // 3. Audit Logs Data
        $auditQuery = AuditLog::where('target_tenant_id', $tenantId)->with('actor');
        if ($request->filled('action')) {
            $auditQuery->where('action', $request->action);
        }
        $auditLogs = $auditQuery->latest('created_at')->paginate(25, ['*'], 'audit_page')->withQueryString();

        return view('portal.reports.index', compact(
            'tenant',
            'tab',
            'currentMonth',
            'lastMonth',
            'currentYear',
            'currentMonthData',
            'lastMonthData',
            'year',
            'monthlyBreakdown',
            'chartLabels',
            'chartInflows',
            'chartOutflows',
            'totalYearInflow',
            'totalYearOutflow',
            'totalYearNet',
            'categoryLabels',
            'categoryData',
            'categoryColors',
            'auditLogs'
        ));
    }

    /**
     * Generate and stream/download the financial PDF report.
     */
    public function generate(Request $request)
    {
        $tenant = auth()->user()->tenant;
        if (! $tenant) {
            abort(403, 'غير مصرح: لا توجد إقامة مرتبطة بهذا الحساب.');
        }

        $validated = $request->validate([
            'start_month' => ['required', 'date_format:Y-m'],
            'end_month'   => ['nullable', 'date_format:Y-m'],
            'scope'       => ['nullable', 'in:both,revenues,expenses'],
            'mode'        => ['nullable', 'in:download,preview'],
        ]);

        $startMonth = $validated['start_month'];
        $endMonth   = $validated['end_month'] ?? $startMonth;
        $scope      = $validated['scope'] ?? 'both';
        $mode       = $validated['mode'] ?? 'download';

        // Order months if inverted
        if ($startMonth > $endMonth) {
            [$startMonth, $endMonth] = [$endMonth, $startMonth];
        }

        $pdfContent = $this->reportService->generatePdf(
            tenant: $tenant,
            startMonth: $startMonth,
            endMonth: $endMonth,
            scope: $scope,
            actor: auth()->user()
        );

        $tenantSlug = Str::slug($tenant->name, '-');
        $periodSlug = ($startMonth === $endMonth) ? $startMonth : "{$startMonth}_to_{$endMonth}";
        $filename   = "rapport-financier-{$tenantSlug}-{$scope}-{$periodSlug}.pdf";

        $disposition = ($mode === 'preview') ? 'inline' : 'attachment';

        return response($pdfContent, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "{$disposition}; filename=\"{$filename}\"",
            'Cache-Control'       => 'private, max-age=0, must-revalidate',
            'Pragma'              => 'public',
        ]);
    }
}
