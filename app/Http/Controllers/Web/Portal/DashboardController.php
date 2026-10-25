<?php

namespace App\Http\Controllers\Web\Portal;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Poll;
use App\Models\Suggestion;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user   = auth()->user();
        $tenant = $user->tenant;

        if (! $tenant) {
            abort(403, 'لا توجد إقامة مرتبطة بهذا الحساب.');
        }

        $tenantId     = $tenant->id;
        $currentMonth = now()->format('Y-m');

        // Buildings & Apartments
        $totalBuildings       = Building::where('tenant_id', $tenantId)->count();
        $totalApartments      = Apartment::whereHas('building', fn ($q) => $q->where('tenant_id', $tenantId))->count();
        $billableApartmentIds = Apartment::whereHas('building', fn ($q) => $q->where('tenant_id', $tenantId))
            ->billable()
            ->pluck('id');
        $billableCount        = $billableApartmentIds->count();
        $exemptCount          = max(0, $totalApartments - $billableCount);

        // Expected Monthly Total
        $monthlyExpectedTotal = (float) Apartment::whereHas('building', fn ($q) => $q->where('tenant_id', $tenantId))
            ->billable()
            ->sum('monthly_fee');

        // Payments & Revenue for current month
        $monthRevenue = (float) Payment::where('tenant_id', $tenantId)
            ->where('status', '!=', 'DRAFT')
            ->whereIn('apartment_id', $billableApartmentIds)
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('amount_paid');

        // Expenses for current month
        $monthExpenses = (float) Expense::where('tenant_id', $tenantId)
            ->whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)
            ->sum('amount');

        // Paid units
        $paidUnitsCount = Payment::where('tenant_id', $tenantId)
            ->where('status', '!=', 'DRAFT')
            ->whereIn('apartment_id', $billableApartmentIds)
            ->whereJsonContains('covered_months', $currentMonth)
            ->distinct('apartment_id')
            ->count('apartment_id');

        $unpaidUnitsCount = max(0, $billableCount - $paidUnitsCount);
        $collectionRate   = $billableCount > 0 ? round(($paidUnitsCount / $billableCount) * 100, 1) : 0;

        // Cash vs Bank Balances
        $cashPayments = (float) Payment::where('tenant_id', $tenantId)
            ->where('status', '!=', 'DRAFT')
            ->where('payment_method', 'CASH')
            ->sum('amount_paid');
        $cashExpenses = (float) Expense::where('tenant_id', $tenantId)
            ->where('payment_method', 'CASH')
            ->sum('amount');
        $cashBalance = (float) $tenant->initial_treasury_balance + $cashPayments - $cashExpenses;

        $bankPayments = (float) Payment::where('tenant_id', $tenantId)
            ->where('status', '!=', 'DRAFT')
            ->whereIn('payment_method', ['BANK_TRANSFER', 'CHEQUE'])
            ->sum('amount_paid');
        $bankExpenses = (float) Expense::where('tenant_id', $tenantId)
            ->whereIn('payment_method', ['BANK_TRANSFER', 'CHEQUE'])
            ->sum('amount');
        $bankBalance = (float) $tenant->initial_bank_balance + $bankPayments - $bankExpenses;

        $totalTreasury = $cashBalance + $bankBalance;
        $totalArrears  = max(0, $monthlyExpectedTotal - $monthRevenue);
        $netMonthly    = $monthRevenue - $monthExpenses;

        // Residents
        $activeResidents  = User::where('tenant_id', $tenantId)->where('role', 'RESIDENT')->where('status', 'ACTIVE')->count();
        $pendingResidents = User::where('tenant_id', $tenantId)->where('role', 'RESIDENT')->where('status', 'PENDING')->count();

        // Community
        $pendingSuggestions = Suggestion::where('tenant_id', $tenantId)->where('status', 'PENDING')->count();
        $activePolls        = Poll::where('tenant_id', $tenantId)->where('is_active', true)->count();

        // Recent Payments (Latest 6)
        $recentPayments = Payment::where('tenant_id', $tenantId)
            ->with(['apartment.residents', 'recorder'])
            ->latest('payment_date')
            ->limit(6)
            ->get();

        // Pending Residents (Latest 5)
        $pendingUsers = User::where('tenant_id', $tenantId)
            ->where('role', 'RESIDENT')
            ->where('status', 'PENDING')
            ->with('apartments')
            ->latest('created_at')
            ->limit(5)
            ->get();

        // Chart Data for Monthly Flow (Last 6 Months)
        $chartLabels  = [];
        $chartInflows = [];
        $chartOutflows = [];
        $arabicMonths = [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
            5 => 'مايو', 6 => 'يونيو', 7 => 'يوليوز', 8 => 'غشت',
            9 => 'شتنبر', 10 => 'أكتوبر', 11 => 'نونبر', 12 => 'دجنبر'
        ];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthNum = (int) $date->format('n');
            $chartLabels[] = ($arabicMonths[$monthNum] ?? $date->format('M')) . ' ' . $date->format('Y');

            $chartInflows[] = (float) Payment::where('tenant_id', $tenantId)
                ->where('status', '!=', 'DRAFT')
                ->whereMonth('payment_date', $date->month)
                ->whereYear('payment_date', $date->year)
                ->sum('amount_paid');

            $chartOutflows[] = (float) Expense::where('tenant_id', $tenantId)
                ->whereMonth('expense_date', $date->month)
                ->whereYear('expense_date', $date->year)
                ->sum('amount');
        }

        // Unpaid Apartments for current month (up to 5 for quick followup)
        $paidApartmentIds = Payment::where('tenant_id', $tenantId)
            ->where('status', '!=', 'DRAFT')
            ->whereIn('apartment_id', $billableApartmentIds)
            ->whereJsonContains('covered_months', $currentMonth)
            ->pluck('apartment_id');

        $unpaidApartments = Apartment::whereHas('building', fn ($q) => $q->where('tenant_id', $tenantId))
            ->billable()
            ->whereNotIn('id', $paidApartmentIds)
            ->with(['residents', 'building'])
            ->limit(5)
            ->get();

        return view('portal.dashboard', compact(
            'tenant',
            'user',
            'totalBuildings',
            'totalApartments',
            'billableCount',
            'exemptCount',
            'monthlyExpectedTotal',
            'monthRevenue',
            'monthExpenses',
            'paidUnitsCount',
            'unpaidUnitsCount',
            'collectionRate',
            'cashBalance',
            'bankBalance',
            'totalTreasury',
            'totalArrears',
            'netMonthly',
            'activeResidents',
            'pendingResidents',
            'pendingSuggestions',
            'activePolls',
            'recentPayments',
            'pendingUsers',
            'unpaidApartments',
            'chartLabels',
            'chartInflows',
            'chartOutflows'
        ));
    }
}
