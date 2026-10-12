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
     *
     * Features addressed:
     *   - Feature 5: Only billable apartments count in calculations
     *   - Feature 7: Current month paid status per apartment
     *   - Feature 8: Monthly expected total from all billable apartments
     *   - Feature 11: Expenses visible in syndic dashboard
     */
    public function kpis(Request $request): JsonResponse
    {
        $user   = $request->user();
        $tenant = $user->tenant;

        $currentMonth = now()->format('Y-m');

        // Features 4 & 5 — Only BILLABLE apartments count
        $billableApartmentIds = Apartment::whereHas('building', fn ($q) => $q->where('tenant_id', $tenant->id))
            ->billable()
            ->pluck('id');

        $totalBillableApartments = $billableApartmentIds->count();

        // Feature 8 — Total expected monthly income from ALL billable apartments
        $monthlyExpectedTotal = Apartment::whereHas('building', fn ($q) => $q->where('tenant_id', $tenant->id))
            ->billable()
            ->sum('monthly_fee');

        // Total revenue this month (from billable apartments only, excluding drafts)
        $monthRevenue = Payment::where('tenant_id', $tenant->id)
            ->where('status', '!=', 'DRAFT')
            ->whereIn('apartment_id', $billableApartmentIds)
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('amount_paid');

        // Feature 11 — Total expenses this month (visible in syndic dashboard)
        $monthExpenses = Expense::where('tenant_id', $tenant->id)
            ->whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)
            ->sum('amount');

        // Recent expenses for dashboard display (Feature 11)
        $recentExpenses = Expense::where('tenant_id', $tenant->id)
            ->with('building')
            ->orderByDesc('expense_date')
            ->limit(5)
            ->get()
            ->map(fn ($e) => [
                'id'           => $e->id,
                'title'        => $e->title,
                'amount'       => (float) $e->amount,
                'category'     => $e->category,
                'expense_date' => $e->expense_date?->format('Y-m-d'),
                'building'     => $e->building?->name,
            ]);

        // Feature 7 — Collection rate: billable apartments paid for current month (excluding drafts)
        $paidThisMonth = Payment::where('tenant_id', $tenant->id)
            ->where('status', '!=', 'DRAFT')
            ->whereIn('apartment_id', $billableApartmentIds)
            ->whereJsonContains('covered_months', $currentMonth)
            ->distinct('apartment_id')
            ->count('apartment_id');

        $collectionRate = $totalBillableApartments > 0
            ? round(($paidThisMonth / $totalBillableApartments) * 100, 1)
            : 0;

        // Total arrears (monthly expected - paid this month for current month)
        $totalArrears = max(0, $monthlyExpectedTotal - $monthRevenue);

        // Features 9 & 11 — Cash (الصندوق) vs Bank (البنك) breakdown
        $cashPayments = (float) Payment::where('tenant_id', $tenant->id)
            ->where('status', '!=', 'DRAFT')
            ->where('payment_method', 'CASH')
            ->sum('amount_paid');
        $cashExpenses = (float) Expense::where('tenant_id', $tenant->id)
            ->where('payment_method', 'CASH')
            ->sum('amount');
        $cashBalance = (float) $tenant->initial_treasury_balance + $cashPayments - $cashExpenses;

        $bankPayments = (float) Payment::where('tenant_id', $tenant->id)
            ->where('status', '!=', 'DRAFT')
            ->whereIn('payment_method', ['BANK_TRANSFER', 'CHEQUE'])
            ->sum('amount_paid');
        $bankExpenses = (float) Expense::where('tenant_id', $tenant->id)
            ->whereIn('payment_method', ['BANK_TRANSFER', 'CHEQUE'])
            ->sum('amount');
        $bankBalance = (float) $tenant->initial_bank_balance + $bankPayments - $bankExpenses;

        $treasuryBalance = $cashBalance + $bankBalance;

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
        $chartData = $this->getMonthlyChart($tenant->id, $billableApartmentIds->toArray());

        return response()->json([
            'total_apartments'         => $totalBillableApartments,
            'monthly_expected_total'   => (float) $monthlyExpectedTotal, // Feature 8
            'month_revenue'            => (float) $monthRevenue,
            'month_expenses'           => (float) $monthExpenses,        // Feature 11
            'net_balance'              => (float) ($monthRevenue - $monthExpenses),
            'collection_rate'          => $collectionRate,
            'paid_this_month'          => $paidThisMonth,
            'total_arrears'            => (float) $totalArrears,
            'treasury_balance'         => $treasuryBalance,              // Feature 9 & 11 Total
            'cash_balance'             => $cashBalance,                  // Feature 11 Cash (الصندوق)
            'bank_balance'             => $bankBalance,                  // Feature 11 Bank (البنك)
            'initial_treasury_balance' => (float) $tenant->initial_treasury_balance,
            'initial_bank_balance'     => (float) $tenant->initial_bank_balance,
            'initial_balance_locked'   => $tenant->initial_balance_locked,
            'pending_residents'        => $pendingResidents,
            'pending_suggestions'      => $pendingSuggestions,
            'invitation_code'          => $tenant->invitation_code,
            'recent_expenses'          => $recentExpenses,               // Feature 11
            'chart_data'               => $chartData,
        ]);
    }

    /**
     * Build 6-month inflow vs outflow chart data.
     * Only counts payments from billable apartments (Feature 5).
     */
    private function getMonthlyChart(string $tenantId, array $billableApartmentIds): array
    {
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $date  = now()->subMonths($i);
            $month = $date->format('Y-m');

            $inflow = Payment::where('tenant_id', $tenantId)
                ->where('status', '!=', 'DRAFT')
                ->whereIn('apartment_id', $billableApartmentIds)
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
