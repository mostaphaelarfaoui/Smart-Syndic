<?php

namespace App\Filament\Syndic\Widgets;

use App\Models\Apartment;
use App\Models\Payment;
use Filament\Widgets\ChartWidget;

class CollectionRateDonutChart extends ChartWidget
{
    protected static ?string $heading = '🎯 حالة استخلاص شقق الإقامة (الشهر الحالي)';

    protected static ?int $sort = 6;

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $tenantId     = auth()->user()?->tenant_id;
        $currentMonth = now()->format('Y-m');

        $totalApartments = Apartment::whereHas('building', fn ($q) => $q->where('tenant_id', $tenantId))->count();
        $billableApartmentIds = Apartment::whereHas('building', fn ($q) => $q->where('tenant_id', $tenantId))
            ->billable()
            ->pluck('id');

        $totalBillable = $billableApartmentIds->count();
        $exemptCount   = max(0, $totalApartments - $totalBillable);

        $paidCount = Payment::where('tenant_id', $tenantId)
            ->where('status', '!=', 'DRAFT')
            ->whereIn('apartment_id', $billableApartmentIds)
            ->whereJsonContains('covered_months', $currentMonth)
            ->distinct('apartment_id')
            ->count('apartment_id');

        $unpaidCount = max(0, $totalBillable - $paidCount);

        return [
            'datasets' => [
                [
                    'label'           => 'عدد الشقق',
                    'data'            => [$paidCount, $unpaidCount, $exemptCount],
                    'backgroundColor' => [
                        '#10b981', // Emerald / Success: Paid
                        '#f43f5e', // Rose / Danger: Unpaid
                        '#f59e0b', // Amber / Warning: Exempt
                    ],
                ],
            ],
            'labels' => [
                "مؤداة ({$paidCount})",
                "في الانتظار ({$unpaidCount})",
                "معفاة من السانديك ({$exemptCount})",
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
