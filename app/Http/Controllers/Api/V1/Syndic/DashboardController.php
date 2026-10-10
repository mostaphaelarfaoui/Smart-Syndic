<?php

namespace App\Http\Controllers\Api\V1\Syndic;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Suggestion;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * GET /api/v1/syndic/dashboard/kpis
     *
     * Returns real-time financial summaries, collection rates, and pending counters.
     */
    public function kpis(Request $request): JsonResponse
    {
        $user   = $request->user();
        $tenant = $user->tenant;

        $currentMonth = now()->format('Y-m');

        // Total apartments
        $totalApartments = Apartment::whereHas('building', fn ($q) => $q->where('tenant_id', $tenant->id))
            ->count();

        // Total revenue this month
        $monthRevenue = Payment::where('tenant_id', $tenant->id)
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('amount_paid');

        // Total expenses this month
        $monthExpenses = Expense::where('tenant_id', $tenant->id)
            ->whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)
            ->sum('amount');

        // Collection rate (apartments that have paid for current month)
        $paidApartments = Payment::where('tenant_id', $tenant->id)
            ->whereJsonContains('covered_months', $currentMonth)
            ->distinct('apartment_id')
            ->count('apartment_id');

        $collectionRate = $totalApartments > 0
            ? round(($paidApartments / $totalApartments) * 100, 1)
            : 0;

        // Total arrears
        $totalExpectedMonthly = Apartment::whereHas('building', fn ($q) => $q->where('tenant_id', $tenant->id))
            ->sum('monthly_fee');
        $totalArrears = max(0, $totalExpectedMonthly - $monthRevenue);

        // Pending resident approvals
        $pendingResidents = User::where('tenant_id', $tenant->id)
            ->where('role', 'RESIDENT')
            ->where('status', 'PENDING')
            ->count();

        // Unread suggestions
        $pendingSuggestions = Suggestion::where('tenant_id', $tenant->id)
            ->where('status', 'PENDING')
            ->count();

        // 6-month inflow/outflow chart data
        $chartData = $this->getMonthlyChart($tenant->id);

        return response()->json([
            'total_apartments'    => $totalApartments,
            'month_revenue'       => (float) $monthRevenue,
            'month_expenses'      => (float) $monthExpenses,
            'net_balance'         => (float) ($monthRevenue - $monthExpenses),
            'collection_rate'     => $collectionRate,
            'total_arrears'       => (float) $totalArrears,
            'pending_residents'   => $pendingResidents,
            'pending_suggestions' => $pendingSuggestions,
            'invitation_code'     => $tenant->invitation_code,
            'chart_data'          => $chartData,
        ]);
    }

    /**
     * Build 6-month inflow vs outflow chart data.
     */
    private function getMonthlyChart(string $tenantId): array
    {
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $date  = now()->subMonths($i);
            $month = $date->format('Y-m');

            $inflow = Payment::where('tenant_id', $tenantId)
                ->whereMonth('payment_date', $date->month)
                ->whereYear('payment_date', $date->year)
                ->sum('amount_paid');

            $outflow = Expense::where('tenant_id', $tenantId)
                ->whereMonth('expense_date', $date->month)
                ->whereYear('expense_date', $date->year)
                ->sum('amount');

            $data[] = [
                'month'   => $month,
                'inflow'  => (float) $inflow,
                'outflow' => (float) $outflow,
            ];
        }

        return $data;
    }
}
