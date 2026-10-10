<?php

namespace App\Filament\Syndic\Widgets;

use App\Models\Expense;
use App\Models\Payment;
use Filament\Widgets\ChartWidget;

class MonthlyFlowChart extends ChartWidget
{
    protected static ?string $heading = 'مقارنة المداخيل والمصاريف (آخر 6 أشهر)';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $tenantId = auth()->user()->tenant_id;
        $labels   = [];
        $inflows  = [];
        $outflows = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $labels[]  = $date->format('M Y');

            $inflows[] = (float) Payment::where('tenant_id', $tenantId)
                ->whereMonth('payment_date', $date->month)
                ->whereYear('payment_date', $date->year)
                ->sum('amount_paid');

            $outflows[] = (float) Expense::where('tenant_id', $tenantId)
                ->whereMonth('expense_date', $date->month)
                ->whereYear('expense_date', $date->year)
                ->sum('amount');
        }

        return [
            'datasets' => [
                [
                    'label'           => 'المداخيل (درهم)',
                    'data'            => $inflows,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
                    'borderColor'     => 'rgb(16, 185, 129)',
                ],
                [
                    'label'           => 'المصاريف (درهم)',
                    'data'            => $outflows,
                    'backgroundColor' => 'rgba(244, 63, 94, 0.2)',
                    'borderColor'     => 'rgb(244, 63, 94)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
