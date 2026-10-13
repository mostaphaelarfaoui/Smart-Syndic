<?php

namespace App\Http\Controllers\Web\Portal;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\Expense;
use App\Models\Payment;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $year     = (int) $request->input('year', now()->year);

        // 12 Months Breakdown for the selected year
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

        // Category Breakdown
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

        // Compliance / Arrears breakdown
        $billableApartments = Apartment::whereHas('building', fn ($q) => $q->where('tenant_id', $tenantId))
            ->billable()
            ->with(['residents', 'building'])
            ->get();

        $currentMonthStr = now()->format('Y-m');

        $paidApartments   = [];
        $unpaidApartments = [];

        foreach ($billableApartments as $apt) {
            $hasPaidThisMonth = Payment::where('tenant_id', $tenantId)
                ->where('apartment_id', $apt->id)
                ->where('status', '!=', 'DRAFT')
                ->whereJsonContains('covered_months', $currentMonthStr)
                ->exists();

            if ($hasPaidThisMonth) {
                $paidApartments[] = $apt;
            } else {
                $unpaidApartments[] = $apt;
            }
        }

        return view('portal.analytics.index', compact(
            'year',
            'monthlyBreakdown',
            'totalYearInflow',
            'totalYearOutflow',
            'totalYearNet',
            'chartLabels',
            'chartInflows',
            'chartOutflows',
            'categoryLabels',
            'categoryData',
            'categoryColors',
            'paidApartments',
            'unpaidApartments'
        ));
    }
}
