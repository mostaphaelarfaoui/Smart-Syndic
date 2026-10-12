<?php

namespace App\Filament\Syndic\Widgets;

use App\Models\Apartment;
use App\Models\Expense;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinancialTreasuryStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $tenant = auth()->user()?->tenant;

        if (! $tenant) {
            return [];
        }

        $tenantId     = $tenant->id;
        $currentMonth = now()->format('Y-m');

        // Billable apartments
        $billableApartmentIds = Apartment::whereHas('building', fn ($q) => $q->where('tenant_id', $tenantId))
            ->billable()
            ->pluck('id');

        $totalBillable = $billableApartmentIds->count();

        // Monthly expected target
        $monthlyExpectedTotal = (float) Apartment::whereHas('building', fn ($q) => $q->where('tenant_id', $tenantId))
            ->billable()
            ->sum('monthly_fee');

        // Month revenue
        $monthRevenue = (float) Payment::where('tenant_id', $tenantId)
            ->where('status', '!=', 'DRAFT')
            ->whereIn('apartment_id', $billableApartmentIds)
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('amount_paid');

        // Month expenses
        $monthExpenses = (float) Expense::where('tenant_id', $tenantId)
            ->whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)
            ->sum('amount');

        // Paid units this month
        $paidUnitsCount = Payment::where('tenant_id', $tenantId)
            ->where('status', '!=', 'DRAFT')
            ->whereIn('apartment_id', $billableApartmentIds)
            ->whereJsonContains('covered_months', $currentMonth)
            ->distinct('apartment_id')
            ->count('apartment_id');

        $unpaidUnitsCount = max(0, $totalBillable - $paidUnitsCount);

        // Collection rate
        $collectionRate = $totalBillable > 0
            ? round(($paidUnitsCount / $totalBillable) * 100, 1)
            : 0;

        // Cash vs Bank balances
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

        return [
            Stat::make('إجمالي خزينة الإقامة (السيولة والبنك)', number_format($totalTreasury, 2) . ' درهم')
                ->description("💵 الصندوق: " . number_format($cashBalance, 2) . " د.م | 🏦 البنك: " . number_format($bankBalance, 2) . " د.م")
                ->descriptionIcon('heroicon-m-wallet')
                ->color($totalTreasury >= 0 ? 'success' : 'danger'),

            Stat::make('مداخيل الشهر الحالي (المحصل)', number_format($monthRevenue, 2) . ' درهم')
                ->description("نسبة التحصيل: {$collectionRate}% (المستهدف: " . number_format($monthlyExpectedTotal, 2) . " د.م)")
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color($collectionRate >= 70 ? 'success' : ($collectionRate >= 40 ? 'warning' : 'danger')),

            Stat::make('مصاريف ونفقات الشهر الحالي', number_format($monthExpenses, 2) . ' درهم')
                ->description("الرصيد الصافي للشهر: " . number_format($netMonthly, 2) . " د.م " . ($netMonthly >= 0 ? '(فائض)' : '(عجز)'))
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color($netMonthly >= 0 ? 'info' : 'danger'),

            Stat::make('المتأخرات غير المحصلة للشهر', number_format($totalArrears, 2) . ' درهم')
                ->description("{$unpaidUnitsCount} شقة لم تؤد بعد من أصل {$totalBillable} شقة")
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($unpaidUnitsCount > 0 ? 'danger' : 'success'),
        ];
    }
}
