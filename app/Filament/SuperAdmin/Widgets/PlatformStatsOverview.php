<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\Tenant;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalTenants  = Tenant::count();
        $activeTenants = Tenant::where('status', 'ACTIVE')->count();
        $pendingTenants = Tenant::where('status', 'PENDING_APPROVAL')->count();
        $totalUsers    = User::count();
        $totalResidents = User::where('role', 'RESIDENT')->count();

        return [
            Stat::make('إجمالي الإقامات والمنظمات', $totalTenants)
                ->description("{$activeTenants} مفعلة، {$pendingTenants} قيد الانتظار")
                ->color('primary'),

            Stat::make('إجمالي المستخدمين', $totalUsers)
                ->description("{$totalResidents} ساكناً مسجلاً")
                ->color('info'),

            Stat::make('إقامات في انتظار التفعيل', $pendingTenants)
                ->description('تسجيلات سانديك جديدة تنتظر الموافقة')
                ->color($pendingTenants > 0 ? 'warning' : 'success'),

            Stat::make('نسبة النشاط والتفعيل', $totalTenants > 0
                ? round(($activeTenants / $totalTenants) * 100, 1) . '%'
                : '0%')
                ->description('نسبة المنظمات المفعلة من الإجمالي')
                ->color('success'),
        ];
    }
}
