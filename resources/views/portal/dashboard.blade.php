@extends('layouts.dashboard')

@section('title', 'لوحة القيادة — ' . $tenant->name)

@section('content')

{{-- 1. Hero Banner --}}
<div class="hero-banner">
    <div class="hero-content">
        <div class="hero-top">
            <div class="hero-badges">
                <span class="hero-pill" style="display:inline-flex; align-items:center; gap:0.4rem;">
                    <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#10b981;"></span>
                    <span>نظام التسيير نشط</span>
                </span>
                <span class="hero-pill">
                    {{ match ($tenant->property_type) {
                        'SINGLE_BUILDING'        => 'عمارة منفردة',
                        'RESIDENTIAL_COMPLEX'    => 'مجمع سكني مغلق',
                        'HOUSING_ESTATE_VILLAS' => 'تجزئة فيلات',
                        'COMMERCIAL_RESIDENTIAL' => 'مجمع تجاري وسكني',
                        default                  => 'إقامة سكنية',
                    } }}
                </span>
            </div>

            {{-- Invitation Code --}}
            <div class="invite-chip" x-data="{ copied: false }">
                <span style="font-size:0.82rem; color:#cbd5e1; font-weight:600;">كود انضمام السكان:</span>
                <span class="invite-code">{{ $tenant->invitation_code }}</span>
                <button
                    type="button"
                    class="btn btn-sm btn-secondary"
                    style="padding:0.25rem 0.65rem; font-size:0.75rem;"
                    x-on:click="navigator.clipboard.writeText('{{ $tenant->invitation_code }}'); copied = true; setTimeout(() => copied = false, 2500)"
                >
                    <span x-show="!copied">نسخ الكود</span>
                    <span x-show="copied" x-cloak style="color:#10b981; font-weight:700;">تم النسخ! ✓</span>
                </button>
            </div>
        </div>

        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1.5rem;">
            <div>
                <h1 style="font-size:2rem; font-weight:900; letter-spacing:-0.02em; margin-bottom:0.4rem;">
                    مرحباً بك، {{ $user->full_name }} 👋
                </h1>
                <p style="color:#cbd5e1; font-size:1.05rem;">
                    لوحة المتابعة والمؤشرات الحية لإدارة <strong style="color:#5eead4;">{{ $tenant->name }}</strong>
                </p>
            </div>

            <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">
                <div class="hero-pill" style="padding:0.6rem 1.1rem; text-align:center;">
                    <div style="font-size:0.75rem; color:#94a3b8;">العمارات</div>
                    <div class="num" style="font-size:1.25rem; font-weight:800; color:#fff;">{{ $totalBuildings }}</div>
                </div>
                <div class="hero-pill" style="padding:0.6rem 1.1rem; text-align:center;">
                    <div style="font-size:0.75rem; color:#94a3b8;">الشقق</div>
                    <div class="num" style="font-size:1.25rem; font-weight:800; color:#fff;">{{ $totalApartments }}</div>
                </div>
                <div class="hero-pill" style="padding:0.6rem 1.1rem; text-align:center;">
                    <div style="font-size:0.75rem; color:#94a3b8;">السكان المفعلين</div>
                    <div class="num" style="font-size:1.25rem; font-weight:800; color:#5eead4;">{{ $activeResidents }}</div>
                </div>
            </div>
        </div>

        {{-- Action Buttons Launchpad --}}
        <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap; padding-top:0.75rem; border-top:1px solid rgba(255,255,255,0.12);">
            <button type="button" onclick="openFinancialReportModal('both')" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:0.5rem; font-weight:800; background:linear-gradient(135deg, #0d9488 0%, #059669 100%); border:none; box-shadow:0 4px 14px rgba(13,148,136,0.35); padding:0.6rem 1.1rem; border-radius:8px; color:#fff; cursor:pointer;">
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>📑 استخراج تقرير مالي PDF</span>
            </button>
            <a href="{{ route('portal.payments.index') }}" class="btn btn-success">
                <span>+ استخلاص دفعة</span>
            </a>
            <a href="{{ route('portal.expenses.index') }}" class="btn btn-danger">
                <span>- تسجيل مصروف</span>
            </a>
            <a href="{{ route('portal.apartments.index') }}" class="btn btn-secondary">
                <span>🏢 إدارة الشقق</span>
            </a>
            <a href="{{ route('portal.analytics.index') }}" class="btn btn-secondary">
                <span>📊 الإحصائيات الكاملة</span>
            </a>
        </div>
    </div>
</div>

{{-- 1.5 Financial Report Quick Export Banner --}}
<div class="card" style="margin-bottom:1.75rem; background:linear-gradient(135deg, rgba(13,148,136,0.07) 0%, rgba(16,185,129,0.03) 100%); border:1px solid rgba(13,148,136,0.22); border-radius:var(--radius-lg); padding:1.25rem 1.5rem; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1.25rem;">
    <div style="display:flex; align-items:center; gap:1rem;">
        <div style="width:48px; height:48px; border-radius:12px; background:linear-gradient(135deg, #0d9488 0%, #059669 100%); color:#fff; display:flex; align-items:center; justify-content:center; font-size:1.5rem; box-shadow:0 4px 12px rgba(13,148,136,0.25); flex-shrink:0;">
            📑
        </div>
        <div>
            <h4 style="margin:0 0 0.25rem 0; font-size:1.05rem; font-weight:800; color:var(--text-primary); display:flex; align-items:center; gap:0.5rem;">
                <span>التقارير المالية والمحاسبية الرسمية (PDF)</span>
                <span class="badge badge-success" style="font-size:0.72rem; padding:0.15rem 0.5rem;">جاهز للتحميل والطباعة</span>
            </h4>
            <p style="margin:0; font-size:0.875rem; color:var(--text-secondary);">
                استخرج تقرير مفصل ومعتمد لشهر واحد أو عدة أشهر مع خيارات الفرز (شامل، مداخيل فقط، أو مصاريف فقط) ومختوم بخاتم السانديك.
            </p>
        </div>
    </div>
    <div style="display:flex; align-items:center; gap:0.65rem; flex-wrap:wrap;">
        <a href="{{ route('portal.reports.financial-pdf', ['start_month' => date('Y-m'), 'end_month' => date('Y-m'), 'scope' => 'both', 'mode' => 'download']) }}" target="_blank" class="btn btn-secondary" style="font-weight:700; font-size:0.85rem; display:inline-flex; align-items:center; gap:0.4rem;" title="تحميل مباشر للتقرير المالي الشامل للشهر الحالي">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            <span>تحميل تقرير الشهر الجاري</span>
        </a>
        <button type="button" onclick="openFinancialReportModal('both')" class="btn btn-primary" style="font-weight:800; font-size:0.85rem; display:inline-flex; align-items:center; gap:0.45rem; background:linear-gradient(135deg, #0d9488 0%, #059669 100%); border:none; box-shadow:0 3px 8px rgba(13,148,136,0.3); color:#fff; cursor:pointer;">
            <span>⚙️ تخصيص وتنزيل تقرير PDF</span>
            <span>&larr;</span>
        </button>
        <a href="{{ route('portal.reports.index') }}" class="btn btn-secondary" style="font-weight:700; font-size:0.85rem;" title="الانتقال لمركز التقارير الكامل">
            <span>مركز التقارير 📊</span>
        </a>
    </div>
</div>

{{-- 2. Treasury & Financial KPIs --}}
<div class="kpi-grid">
    {{-- Total Treasury --}}
    <div class="kpi-card">
        <div class="kpi-card-header">
            <span class="kpi-label">إجمالي رصيد الخزينة</span>
            <div class="kpi-icon-box emerald">
                <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
            </div>
        </div>
        <div class="kpi-value num">
            {{ number_format($totalTreasury, 2) }} <span class="unit">د.م</span>
        </div>
        <div class="kpi-footer">
            <span>💵 الصندوق: <strong class="num">{{ number_format($cashBalance, 2) }}</strong></span>
            <span>🏦 البنك: <strong class="num">{{ number_format($bankBalance, 2) }}</strong></span>
        </div>
    </div>

    {{-- Monthly Revenue --}}
    <div class="kpi-card">
        <div class="kpi-card-header">
            <span class="kpi-label">مداخيل الشهر المحصلة</span>
            <div class="kpi-icon-box teal">
                <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
            </div>
        </div>
        <div class="kpi-value num" style="display:flex; align-items:baseline; justify-content:space-between;">
            <span>{{ number_format($monthRevenue, 2) }} <span class="unit">د.م</span></span>
            <span class="badge {{ $collectionRate >= 70 ? 'badge-success' : ($collectionRate >= 40 ? 'badge-warning' : 'badge-danger') }}">
                {{ $collectionRate }}%
            </span>
        </div>
        <div class="kpi-footer">
            <span>المستهدف: <strong class="num">{{ number_format($monthlyExpectedTotal, 2) }} د.م</strong></span>
            <span>{{ $paidUnitsCount }}/{{ $billableCount }} شقة</span>
        </div>
    </div>

    {{-- Monthly Expenses --}}
    <div class="kpi-card">
        <div class="kpi-card-header">
            <span class="kpi-label">مصاريف ونفقات الشهر</span>
            <div class="kpi-icon-box rose">
                <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                </svg>
            </div>
        </div>
        <div class="kpi-value num">
            {{ number_format($monthExpenses, 2) }} <span class="unit">د.م</span>
        </div>
        <div class="kpi-footer">
            <span>الرصيد الصافي للشهر:</span>
            <strong class="num" style="color: {{ $netMonthly >= 0 ? 'var(--color-success)' : 'var(--color-danger)' }};">
                {{ number_format($netMonthly, 2) }} د.م ({{ $netMonthly >= 0 ? 'فائض' : 'عجز' }})
            </strong>
        </div>
    </div>

    {{-- Arrears --}}
    <div class="kpi-card">
        <div class="kpi-card-header">
            <span class="kpi-label">المتأخرات غير المحصلة</span>
            <div class="kpi-icon-box amber">
                <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
        </div>
        <div class="kpi-value num" style="color: {{ $unpaidUnitsCount > 0 ? 'var(--color-warning)' : 'var(--color-success)' }};">
            {{ number_format($totalArrears, 2) }} <span class="unit">د.م</span>
        </div>
        <div class="kpi-footer">
            <span>الشقق المتأخرة في الانتظار:</span>
            <strong class="num">{{ $unpaidUnitsCount }} شقة</strong>
        </div>
    </div>
</div>

{{-- 3. Monthly Flow Interactive Chart --}}
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <span>📊 حركة المداخيل والمصاريف الشهرية (آخر 6 أشهر)</span>
        </div>
        <a href="{{ route('portal.analytics.index') }}" class="btn btn-sm btn-secondary">
            فتح شاشة التحليلات المعمقة ➔
        </a>
    </div>
    <div class="card-body">
        <div style="height:320px; width:100%;">
            <canvas id="monthlyFlowCanvas"></canvas>
        </div>
    </div>
</div>

{{-- 4. Recent Collections & Pending Alerts Grid --}}
<div class="grid-3">
    {{-- Recent Payments Table --}}
    <div class="card" style="margin-bottom:0;">
        <div class="card-header">
            <div class="card-title">
                <span>💳 آخر المقبوضات والتحصيلات المسجلة</span>
            </div>
            <a href="{{ route('portal.payments.index') }}" class="btn btn-sm btn-secondary">
                كل المقبوضات ({{ $paidUnitsCount }})
            </a>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="min-width:90px;">الشقة</th>
                        <th style="min-width:140px;">المؤدي / الساكن</th>
                        <th style="min-width:120px;">المبلغ</th>
                        <th style="min-width:110px;">طريقة الدفع</th>
                        <th style="min-width:100px;">التاريخ</th>
                        <th style="text-align:center; min-width:80px;">التوصيل</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentPayments as $payment)
                        <tr>
                            <td>
                                <span class="badge badge-neutral num" style="font-weight:800;">
                                    شقة {{ $payment->apartment?->unit_number ?? '—' }}
                                </span>
                            </td>
                            <td>
                                <strong style="font-size:0.9rem;">{{ $payment->apartment?->residents?->first()?->full_name ?? 'المالك' }}</strong>
                            </td>
                            <td>
                                <span class="num" style="font-weight:800; color:var(--color-success);">
                                    {{ number_format($payment->amount_paid, 2) }} د.م
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-info">
                                    {{ match($payment->payment_method) {
                                        'CASH' => '💵 نقداً',
                                        'BANK_TRANSFER' => '🏦 تحويل',
                                        'CHEQUE' => '📝 شيك',
                                        default => $payment->payment_method
                                    } }}
                                </span>
                            </td>
                            <td class="num" style="color:var(--text-muted); font-size:0.85rem;">
                                {{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('Y/m/d') : '—' }}
                            </td>
                            <td>
                                <a href="{{ route('receipts.download', $payment) }}" class="btn btn-sm btn-secondary" title="تحميل التوصيل PDF">
                                    📄 PDF
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center; padding:2rem; color:var(--text-muted);">
                                لا توجد مقبوضات مسجلة بعد لهذا الشهر.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pending Join Requests --}}
    <div class="card" style="margin-bottom:0;">
        <div class="card-header">
            <div class="card-title">
                <span>⏳ طلبات الانضمام المعلقة</span>
            </div>
            @if($pendingResidents > 0)
                <span class="badge badge-warning num">{{ $pendingResidents }} معلقة</span>
            @endif
        </div>
        <div class="card-body" style="padding:1.25rem;">
            @forelse($pendingUsers as $pUser)
                <div style="display:flex; align-items:center; justify-content:space-between; padding:0.85rem; border:1px solid var(--border-color); border-radius:var(--radius-md); margin-bottom:0.75rem; background:var(--bg-app);">
                    <div>
                        <div style="font-weight:800; font-size:0.92rem;">{{ $pUser->full_name }}</div>
                        <div style="font-size:0.8rem; color:var(--text-muted);">
                            <span>📞 {{ $pUser->phone }}</span>
                            @if($pUser->apartments->isNotEmpty())
                                <span> • شقة {{ $pUser->apartments->first()->unit_number }}</span>
                            @endif
                        </div>
                    </div>
                    <form method="POST" action="{{ route('portal.residents.approve', $pUser->id) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success">
                            موافقة ✓
                        </button>
                    </form>
                </div>
            @empty
                <div style="text-align:center; padding:2rem 1rem; color:var(--text-muted);">
                    <div style="font-size:2rem; margin-bottom:0.5rem;">🎉</div>
                    <p style="font-size:0.9rem;">لا توجد طلبات انضمام معلقة حالياً.</p>
                </div>
            @endforelse

            <div style="margin-top:1rem; text-align:center;">
                <a href="{{ route('portal.residents.index') }}" style="font-size:0.85rem; color:var(--brand-primary); font-weight:700;">
                    عرض كل السكان والطلبات ➔
                </a>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('monthlyFlowCanvas');
        if (!ctx) return;

        const labels = @json($chartLabels);
        const inflows = @json($chartInflows);
        const outflows = @json($chartOutflows);

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'المداخيل المحصلة (د.م)',
                        data: inflows,
                        backgroundColor: 'rgba(16, 185, 129, 0.85)',
                        borderColor: '#10b981',
                        borderWidth: 1,
                        borderRadius: 8,
                        barPercentage: 0.6,
                    },
                    {
                        label: 'المصاريف والنفقات (د.م)',
                        data: outflows,
                        backgroundColor: 'rgba(244, 63, 94, 0.85)',
                        borderColor: '#f43f5e',
                        borderWidth: 1,
                        borderRadius: 8,
                        barPercentage: 0.6,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: { family: 'Cairo', size: 13, weight: 'bold' },
                            color: getComputedStyle(document.documentElement).getPropertyValue('--text-primary').trim() || '#fff'
                        }
                    },
                    tooltip: {
                        rtl: true,
                        titleFont: { family: 'Cairo', weight: 'bold' },
                        bodyFont: { family: 'Cairo' }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { family: 'Cairo', size: 12 },
                            color: '#94a3b8'
                        }
                    },
                    y: {
                        grid: { color: 'rgba(255, 255, 255, 0.06)' },
                        ticks: {
                            font: { family: 'Cairo', size: 12 },
                            color: '#94a3b8'
                        }
                    }
                }
            }
        });
    });
</script>
@endpush
