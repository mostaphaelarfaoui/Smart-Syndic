<?php

namespace App\Filament\Syndic\Widgets;

use App\Models\Expense;
use App\Models\Payment;
use App\Models\Suggestion;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SyndicStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $tenantId = auth()->user()->tenant_id;

        $monthRevenue = Payment::where('tenant_id', $tenantId)
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('amount_paid');

        $monthExpenses = Expense::where('tenant_id', $tenantId)
            ->whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)
            ->sum('amount');

        $netBalance = $monthRevenue - $monthExpenses;

        $pendingResidents = User::where('tenant_id', $tenantId)
            ->where('role', 'RESIDENT')
            ->where('status', 'PENDING')
            ->count();

        $pendingSuggestions = Suggestion::where('tenant_id', $tenantId)
            ->where('status', 'PENDING')
            ->count();

        return [
            Stat::make('مداخيل الشهر الحالي', number_format($monthRevenue, 2) . ' درهم')
                ->description('التحصيلات والواجبات للشهر الجاري')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('مصاريف الشهر الحالي', number_format($monthExpenses, 2) . ' درهم')
                ->description('مجموع النفقات للشهر الجاري')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            Stat::make('صافي الرصيد الشهري', number_format($netBalance, 2) . ' درهم')
                ->description('المداخيل ناقص المصاريف')
                ->color($netBalance >= 0 ? 'success' : 'danger'),

            Stat::make('طلبات الانضمام المعلقة', $pendingResidents)
                ->description('سكان في انتظار تفعيل الحساب')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color($pendingResidents > 0 ? 'warning' : 'success'),

            Stat::make('اقتراحات وشكايات جديدة', $pendingSuggestions)
                ->description('ملاحظات قيد المراجعة')
                ->descriptionIcon('heroicon-m-light-bulb')
                ->color($pendingSuggestions > 0 ? 'info' : 'success'),
        ];
    }
}
