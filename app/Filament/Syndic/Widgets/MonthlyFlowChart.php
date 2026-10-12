<?php

namespace App\Filament\Syndic\Widgets;

use App\Models\Expense;
use App\Models\Payment;
use Filament\Widgets\ChartWidget;

class MonthlyFlowChart extends ChartWidget
{
    protected static ?string $heading = '📊 مقارنة حركة المداخيل والمصاريف (آخر 6 أشهر)';

    protected static ?int $sort = 4;

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $tenantId = auth()->user()?->tenant_id;
        $labels   = [];
        $inflows  = [];
        $outflows = [];

        $arabicMonths = [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
            5 => 'مايو', 6 => 'يونيو', 7 => 'يوليوز', 8 => 'غشت',
            9 => 'شتنبر', 10 => 'أكتوبر', 11 => 'نونبر', 12 => 'دجنبر'
        ];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthNum = (int) $date->format('n');
            $labels[]  = ($arabicMonths[$monthNum] ?? $date->format('M')) . ' ' . $date->format('Y');

            $inflows[] = (float) Payment::where('tenant_id', $tenantId)
                ->where('status', '!=', 'DRAFT')
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
                    'label'           => 'المداخيل المحصلة (درهم)',
                    'data'            => $inflows,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.75)',
                    'borderColor'     => 'rgb(16, 185, 129)',
                    'borderRadius'    => 6,
                ],
                [
                    'label'           => 'المصاريف والنفقات (درهم)',
                    'data'            => $outflows,
                    'backgroundColor' => 'rgba(244, 63, 94, 0.75)',
                    'borderColor'     => 'rgb(244, 63, 94)',
                    'borderRadius'    => 6,
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
