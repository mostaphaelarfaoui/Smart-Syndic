<?php

namespace App\Filament\Syndic\Widgets;

use App\Models\Apartment;
use App\Models\Payment;
use App\Models\Poll;
use App\Models\Suggestion;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ResidenceOccupancyStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $tenant = auth()->user()?->tenant;

        if (! $tenant) {
            return [];
        }

        $tenantId     = $tenant->id;
        $currentMonth = now()->format('Y-m');

        // Total apartments
        $totalApartments = Apartment::whereHas('building', fn ($q) => $q->where('tenant_id', $tenantId))->count();
        $billableCount   = Apartment::whereHas('building', fn ($q) => $q->where('tenant_id', $tenantId))->billable()->count();
        $exemptCount     = $totalApartments - $billableCount;

        // Paid vs Unpaid units for current month
        $paidUnitsCount = Payment::where('tenant_id', $tenantId)
            ->where('status', '!=', 'DRAFT')
            ->whereHas('apartment', fn ($q) => $q->billable())
            ->whereJsonContains('covered_months', $currentMonth)
            ->distinct('apartment_id')
            ->count('apartment_id');

        $unpaidUnitsCount = max(0, $billableCount - $paidUnitsCount);

        // Residents
        $activeResidents = User::where('tenant_id', $tenantId)
            ->where('role', 'RESIDENT')
            ->where('status', 'ACTIVE')
            ->count();

        $pendingResidents = User::where('tenant_id', $tenantId)
            ->where('role', 'RESIDENT')
            ->where('status', 'PENDING')
            ->count();

        // Community interactions
        $pendingSuggestions = Suggestion::where('tenant_id', $tenantId)
            ->where('status', 'PENDING')
            ->count();

        $activePolls = Poll::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->count();

        return [
            Stat::make('الشقق والوحدات السكنية', "{$totalApartments} شقة")
                ->description("{$billableCount} خاضعة للأداء | {$exemptCount} معفاة من السانديك")
                ->descriptionIcon('heroicon-m-home-modern')
                ->color('info'),

            Stat::make('استخلاص واجبات الشهر الجاري', "{$paidUnitsCount} / {$billableCount} شقة")
                ->description("{$unpaidUnitsCount} شقة متأخرة في الانتظار")
                ->descriptionIcon('heroicon-m-check-badge')
                ->color($unpaidUnitsCount === 0 ? 'success' : 'warning'),

            Stat::make('حسابات السكان والمقيمين', "{$activeResidents} ساكن مفعل")
                ->description("{$pendingResidents} طلب انضمام في انتظار الموافقة")
                ->descriptionIcon('heroicon-m-user-group')
                ->color($pendingResidents > 0 ? 'warning' : 'success'),

            Stat::make('الملاحظات واستطلاعات الرأي', "{$pendingSuggestions} شكايات وملاحظات")
                ->description("{$activePolls} تصويت واستطلاع نشط حالياً")
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->color($pendingSuggestions > 0 ? 'info' : 'success'),
        ];
    }
}
