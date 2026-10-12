<?php

namespace App\Filament\Syndic\Widgets;

use App\Models\Expense;
use Filament\Widgets\ChartWidget;

class ExpenseCategoryChart extends ChartWidget
{
    protected static ?string $heading = '🧹 توزيع المصاريف حسب الصنف والنوع (السنة الحالية)';

    protected static ?int $sort = 5;

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $tenantId = auth()->user()?->tenant_id;

        $categories = [
            'ELEVATOR'          => ['label' => '🛗 المصعد', 'color' => '#0ea5e9'],
            'CLEANING'          => ['label' => '🧹 النظافة', 'color' => '#10b981'],
            'SECURITY'          => ['label' => '🔒 الحراسة والأمن', 'color' => '#f43f5e'],
            'WATER_ELECTRICITY' => ['label' => '💡 الماء والكهرباء', 'color' => '#f59e0b'],
            'GARDENING'         => ['label' => '🌿 البستنة', 'color' => '#84cc16'],
            'MAINTENANCE'       => ['label' => '🔧 الصيانة العامة', 'color' => '#8b5cf6'],
            'OTHER'             => ['label' => '📋 مصاريف أخرى', 'color' => '#64748b'],
        ];

        $labels = [];
        $data   = [];
        $colors = [];

        foreach ($categories as $key => $info) {
            $sum = (float) Expense::where('tenant_id', $tenantId)
                ->whereYear('expense_date', now()->year)
                ->where('category', $key)
                ->sum('amount');

            if ($sum > 0) {
                $labels[] = $info['label'];
                $data[]   = $sum;
                $colors[] = $info['color'];
            }
        }

        // Fallback if no expenses recorded yet
        if (empty($data)) {
            $labels = ['لا توجد مصاريف مسجلة'];
            $data   = [1];
            $colors = ['#cbd5e1'];
        }

        return [
            'datasets' => [
                [
                    'label'           => 'المبلغ (درهم)',
                    'data'            => $data,
                    'backgroundColor' => $colors,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
