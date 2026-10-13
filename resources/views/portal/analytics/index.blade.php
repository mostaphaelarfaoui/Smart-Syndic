@extends('layouts.dashboard')

@section('title', 'مركز الإحصائيات والتحليلات المالية')

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">📊 مركز الإحصائيات والتحليلات المالية</h1>
        <p class="page-subtitle">تحليل أداء المداخيل والمصاريف عبر الشهور والسنوات، ومتابعة نسب الامتثال والتحصيل</p>
    </div>

    {{-- Year Selector --}}
    <form method="GET" action="{{ route('portal.analytics.index') }}" style="display:flex; align-items:center; gap:0.75rem;">
        <label style="font-weight:700; font-size:0.9rem; color:var(--text-secondary);">السنة المالية:</label>
        <select name="year" class="form-select" onchange="this.form.submit()" style="width:auto; min-width:130px; font-weight:800;" class="num">
            @for($y = now()->year; $y >= now()->year - 4; $y--)
                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
            @endfor
        </select>
    </form>
</div>

{{-- Annual KPI Cards --}}
<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-card-header">
            <span class="kpi-label">إجمالي مداخيل سنة {{ $year }}</span>
            <div class="kpi-icon-box emerald">📈</div>
        </div>
        <div class="kpi-value num" style="color:var(--color-success);">
            {{ number_format($totalYearInflow, 2) }} <span class="unit">د.م</span>
        </div>
        <div class="kpi-footer">
            <span>مجموع تحصيلات اشتراكات السكان</span>
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-card-header">
            <span class="kpi-label">إجمالي نفقات سنة {{ $year }}</span>
            <div class="kpi-icon-box rose">📉</div>
        </div>
        <div class="kpi-value num" style="color:var(--color-danger);">
            {{ number_format($totalYearOutflow, 2) }} <span class="unit">د.م</span>
        </div>
        <div class="kpi-footer">
            <span>مجموع فواتير ومصاريف الإقامة</span>
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-card-header">
            <span class="kpi-label">الرصيد الصافي (الفائض / العجز)</span>
            <div class="kpi-icon-box {{ $totalYearNet >= 0 ? 'emerald' : 'rose' }}">⚖️</div>
        </div>
        <div class="kpi-value num" style="color: {{ $totalYearNet >= 0 ? 'var(--color-success)' : 'var(--color-danger)' }};">
            {{ number_format($totalYearNet, 2) }} <span class="unit">د.م</span>
        </div>
        <div class="kpi-footer">
            <span>{{ $totalYearNet >= 0 ? 'فائض مالي متاح بالخزينة ✓' : 'عجز مالي مسجل ⚠' }}</span>
        </div>
    </div>
</div>

{{-- Interactive Charts Row --}}
<div class="grid-2">
    {{-- Monthly Inflow vs Outflow Bar Chart --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <span>📊 التدفق المالي الشهري لسنة {{ $year }}</span>
            </div>
        </div>
        <div class="card-body">
            <div style="height:320px; width:100%;">
                <canvas id="annualFlowCanvas"></canvas>
            </div>
        </div>
    </div>

    {{-- Expense Categories Donut Chart --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <span>🧹 توزيع المصاريف حسب الصنف</span>
            </div>
        </div>
        <div class="card-body">
            <div style="height:320px; width:100%;">
                <canvas id="categoryDonutCanvas"></canvas>
            </div>
        </div>
    </div>
</div>

{{-- 12-Month Table Breakdown --}}
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <span>📋 الكشف المالي التفصيلي لشهور سنة {{ $year }}</span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="min-width:140px;">الشهر</th>
                    <th style="min-width:140px;">المداخيل المحصلة</th>
                    <th style="min-width:140px;">المصاريف والنفقات</th>
                    <th style="min-width:140px;">الصافي الشهري</th>
                    <th style="text-align:center; min-width:110px;">حالة الرصيد</th>
                </tr>
            </thead>
            <tbody>
                @foreach($monthlyBreakdown as $row)
                    <tr>
                        <td>
                            <strong>{{ $row['month_name'] }} {{ $year }}</strong>
                        </td>
                        <td>
                            <span class="num" style="font-weight:800; color:var(--color-success);">
                                {{ number_format($row['inflow'], 2) }} د.م
                            </span>
                        </td>
                        <td>
                            <span class="num" style="font-weight:800; color:var(--color-danger);">
                                {{ number_format($row['outflow'], 2) }} د.م
                            </span>
                        </td>
                        <td>
                            <span class="num" style="font-weight:800; color: {{ $row['net'] >= 0 ? 'var(--color-success)' : 'var(--color-danger)' }};">
                                {{ number_format($row['net'], 2) }} د.م
                            </span>
                        </td>
                        <td>
                            @if($row['inflow'] == 0 && $row['outflow'] == 0)
                                <span class="badge badge-neutral">لا توجد حركات</span>
                            @elseif($row['net'] >= 0)
                                <span class="badge badge-success">✓ فائض</span>
                            @else
                                <span class="badge badge-danger">⚠ عجز</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Compliance Breakdown --}}
<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <span>✓ الشقق المؤدية للشهر الحالي ({{ count($paidApartments) }})</span>
            </div>
            <span class="badge badge-success">ملتزمة بالأداء</span>
        </div>
        <div class="card-body" style="max-height:300px; overflow-y:auto; padding:1rem;">
            @forelse($paidApartments as $pApt)
                <div style="display:flex; align-items:center; justify-content:space-between; padding:0.6rem 0.85rem; border-bottom:1px solid var(--border-subtle);">
                    <div>
                        <strong class="num">شقة {{ $pApt->unit_number }}</strong>
                        <span style="font-size:0.8rem; color:var(--text-muted); margin-right:0.5rem;">({{ $pApt->residents->first()?->full_name ?? 'المالك' }})</span>
                    </div>
                    <span class="badge badge-success" style="font-size:0.75rem;">تم الأداء ✓</span>
                </div>
            @empty
                <p style="text-align:center; color:var(--text-muted); padding:1rem;">لم تسجل أي شقة أداء هذا الشهر بعد.</p>
            @endforelse
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <span>⏳ الشقق المتأخرة عن الشهر الحالي ({{ count($unpaidApartments) }})</span>
            </div>
            <span class="badge badge-warning">في الانتظار</span>
        </div>
        <div class="card-body" style="max-height:300px; overflow-y:auto; padding:1rem;">
            @forelse($unpaidApartments as $uApt)
                <div style="display:flex; align-items:center; justify-content:space-between; padding:0.6rem 0.85rem; border-bottom:1px solid var(--border-subtle);">
                    <div>
                        <strong class="num">شقة {{ $uApt->unit_number }}</strong>
                        <span style="font-size:0.8rem; color:var(--text-muted); margin-right:0.5rem;">({{ $uApt->residents->first()?->full_name ?? 'المالك' }})</span>
                    </div>
                    <span class="num" style="font-weight:700; color:var(--color-warning);">
                        {{ number_format($uApt->monthly_fee, 2) }} د.م
                    </span>
                </div>
            @empty
                <p style="text-align:center; color:var(--color-success); font-weight:700; padding:1rem;">🎉 رائع! جميع الشقق الخاضعة أدت واجب هذا الشهر.</p>
            @endforelse
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // 1. Annual Flow Chart
        const flowCtx = document.getElementById('annualFlowCanvas');
        if (flowCtx) {
            new Chart(flowCtx, {
                type: 'bar',
                data: {
                    labels: @json($chartLabels),
                    datasets: [
                        {
                            label: 'المداخيل (د.م)',
                            data: @json($chartInflows),
                            backgroundColor: 'rgba(16, 185, 129, 0.85)',
                            borderRadius: 6,
                        },
                        {
                            label: 'المصاريف (د.م)',
                            data: @json($chartOutflows),
                            backgroundColor: 'rgba(244, 63, 94, 0.85)',
                            borderRadius: 6,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { labels: { font: { family: 'Cairo', weight: 'bold' } } }
                    },
                    scales: {
                        x: { ticks: { font: { family: 'Cairo' } } },
                        y: { ticks: { font: { family: 'Cairo' } } }
                    }
                }
            });
        }

        // 2. Category Doughnut Chart
        const catCtx = document.getElementById('categoryDonutCanvas');
        if (catCtx) {
            const catLabels = @json($categoryLabels);
            const catData = @json($categoryData);
            const catColors = @json($categoryColors);

            new Chart(catCtx, {
                type: 'doughnut',
                data: {
                    labels: catLabels.length ? catLabels : ['لا توجد مصاريف مسجلة'],
                    datasets: [{
                        data: catData.length ? catData : [1],
                        backgroundColor: catColors.length ? catColors : ['#cbd5e1'],
                        borderWidth: 2,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { font: { family: 'Cairo' } } }
                    }
                }
            });
        }
    });
</script>
@endpush
