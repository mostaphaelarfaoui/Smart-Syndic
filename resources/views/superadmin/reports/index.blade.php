@extends('layouts.superadmin')

@section('title', 'تقارير المنصة ونمو الإقامات')

@section('content')
<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem; margin-bottom:1.5rem;">
    <div>
        <div class="page-pretitle" style="color:#6366f1; font-weight:700; font-size:0.85rem;">EXECUTIVE PLATFORM INTELLIGENCE</div>
        <h1 class="page-title" style="margin:0.25rem 0 0.5rem 0; font-size:1.85rem;">التقارير التنفيذية ونمو المنصة</h1>
        <p style="color:var(--text-muted); margin:0; font-size:0.95rem;">
            تحليل شامل لأداء كافة الإقامات المشتركة، التدفقات المالية الإجمالية، ورصد الإقامات النشطة والخاملة.
        </p>
    </div>

    <div style="display:flex; gap:0.75rem; align-items:center; flex-wrap:wrap;">
        <a href="{{ route('superadmin.reports.print', request()->query()) }}" target="_blank" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:0.5rem;">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            <span>معاينة وطباعة تقرير رسمي (Print View)</span>
        </a>

        <a href="{{ route('superadmin.reports.pdf', request()->query()) }}" class="btn btn-secondary" style="background:#dc2626; color:#ffffff; border:none; display:inline-flex; align-items:center; gap:0.5rem;">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
            </svg>
            <span>تحميل تقرير PDF رسمي</span>
        </a>

        <a href="{{ route('superadmin.reports.export-csv', request()->query()) }}" class="btn btn-primary" style="background:linear-gradient(135deg, #10b981 0%, #059669 100%); border:none; display:inline-flex; align-items:center; gap:0.5rem;">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span>تصدير Excel (CSV)</span>
        </a>
    </div>
</div>

{{-- Date Range & Period Filter (Priority 10) --}}
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-body" style="padding:1rem 1.25rem;">
        <form method="GET" action="{{ route('superadmin.reports.index') }}" style="display:flex; flex-wrap:wrap; align-items:flex-end; gap:1rem;">
            <div class="form-group" style="margin:0; min-width:180px;">
                <label class="form-label" style="font-size:0.8rem; margin-bottom:0.35rem;">النطاق الزمني للتقرير</label>
                <select name="period" class="form-select" onchange="this.form.submit()">
                    <option value="ALL" {{ request('period', 'ALL') === 'ALL' ? 'selected' : '' }}>كامل المدة (منذ البداية)</option>
                    <option value="THIS_MONTH" {{ request('period') === 'THIS_MONTH' ? 'selected' : '' }}>الشهر الحالي</option>
                    <option value="LAST_MONTH" {{ request('period') === 'LAST_MONTH' ? 'selected' : '' }}>الشهر الماضي</option>
                    <option value="THIS_YEAR" {{ request('period') === 'THIS_YEAR' ? 'selected' : '' }}>السنة الحالية</option>
                    <option value="CUSTOM" {{ (request('date_from') || request('date_to')) && request('period') !== 'THIS_MONTH' && request('period') !== 'LAST_MONTH' && request('period') !== 'THIS_YEAR' ? 'selected' : '' }}>نطاق مخصص</option>
                </select>
            </div>

            <div class="form-group" style="margin:0;">
                <label class="form-label" style="font-size:0.8rem; margin-bottom:0.35rem;">من تاريخ</label>
                <input type="date" name="date_from" value="{{ $summary['date_from'] ?? request('date_from') }}" class="form-input">
            </div>

            <div class="form-group" style="margin:0;">
                <label class="form-label" style="font-size:0.8rem; margin-bottom:0.35rem;">إلى تاريخ</label>
                <input type="date" name="date_to" value="{{ $summary['date_to'] ?? request('date_to') }}" class="form-input">
            </div>

            <div style="display:flex; gap:0.5rem;">
                <button type="submit" class="btn btn-primary btn-sm" style="padding:0.6rem 1.25rem;">
                    تطبيق
                </button>
                @if(request()->anyFilled(['period', 'date_from', 'date_to']) && request('period') !== 'ALL')
                    <a href="{{ route('superadmin.reports.index') }}" class="btn btn-secondary btn-sm" style="padding:0.6rem 1rem;">
                        إلغاء التصفية
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- Executive Summary KPIs --}}
<div class="kpi-grid" style="margin-bottom:1.5rem;">
    <div class="kpi-card" style="border-top:3px solid #10b981;">
        <div class="kpi-header">
            <span class="kpi-label">إجمالي السيولة المحصلة بالمنصة</span>
            <span class="badge badge-success">GTV</span>
        </div>
        <div class="kpi-value" style="color:#10b981;">
            {{ number_format($summary['total_collected'], 2) }} <span style="font-size:0.9rem; font-weight:400;">DH</span>
        </div>
        <div class="kpi-sub">
            مجموع واجبات السكني المدفوعة
        </div>
    </div>

    <div class="kpi-card" style="border-top:3px solid #ef4444;">
        <div class="kpi-header">
            <span class="kpi-label">إجمالي المصاريف المصروفة</span>
            <span class="badge badge-danger">Outflow</span>
        </div>
        <div class="kpi-value" style="color:#ef4444;">
            {{ number_format($summary['total_expenses'], 2) }} <span style="font-size:0.9rem; font-weight:400;">DH</span>
        </div>
        <div class="kpi-sub">
            فواتير الصيانة، النظافة، والخدمات
        </div>
    </div>

    <div class="kpi-card" style="border-top:3px solid #6366f1;">
        <div class="kpi-header">
            <span class="kpi-label">صافي صناديق الإقامات الإجمالي</span>
            <span class="kpi-icon" style="background:rgba(99,102,241,0.12); color:#6366f1;">🏦</span>
        </div>
        <div class="kpi-value" style="color:#6366f1;">
            {{ number_format($summary['platform_net'], 2) }} <span style="font-size:0.9rem; font-weight:400;">DH</span>
        </div>
        <div class="kpi-sub">
            المجموع الصافي للأرصدة البنكية والنقدية
        </div>
    </div>

    <div class="kpi-card" style="border-top:3px solid #f59e0b;">
        <div class="kpi-header">
            <span class="kpi-label">الإقامات الخاملة (Dormant)</span>
            <span class="badge {{ $summary['dormant_count'] > 0 ? 'badge-warning' : 'badge-success' }}">
                {{ $summary['dormant_count'] }} إقامة
            </span>
        </div>
        <div class="kpi-value">{{ $summary['dormant_count'] }} <span style="font-size:0.9rem; font-weight:400; color:var(--text-muted);">/ {{ $summary['total_tenants'] }}</span></div>
        <div class="kpi-sub">
            إقامات بلا تحصيلات خلال آخر 30 يوماً
        </div>
    </div>
</div>

{{-- Two Top Panels: Top Active vs Dormant Alerts --}}
<div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:1.5rem;">
    {{-- Top Performers --}}
    <div class="card" style="min-width:0;">
        <div class="card-header">
            <h3 class="card-title">🏆 الإقامات الأكثر نشاطاً وتحصيلاً</h3>
            <p class="card-subtitle">أعلى الإقامات من حيث حجم الأموال المدارة والانتظام</p>
        </div>
        <div class="card-body" style="padding:0;">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="min-width:140px;">الإقامة</th>
                            <th style="text-align:center; min-width:70px;">الشقق</th>
                            <th style="min-width:130px;">المجموع المحصل</th>
                            <th style="min-width:130px;">صافي الصندوق</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topCollectedTenants as $t)
                            <tr>
                                <td style="font-weight:700; color:var(--text-primary);">{{ $t->name }}</td>
                                <td style="text-align:center;">
                                    <span class="badge badge-info" style="font-size:0.8rem;">{{ $t->apartments_count }}</span>
                                </td>
                                <td style="font-weight:700; color:#10b981; font-family:monospace; font-size:0.95rem;">
                                    {{ number_format($t->total_collected, 2) }} <span class="currency-unit">DH</span>
                                </td>
                                <td style="font-weight:700; color:#6366f1; font-family:monospace; font-size:0.95rem;">
                                    {{ number_format($t->net_treasury, 2) }} <span class="currency-unit">DH</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align:center; padding:2rem; color:var(--text-muted);">
                                    لا توجد بيانات متاحة حالياً.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Dormant Tenants Alert --}}
    <div class="card" style="min-width:0; border-color:{{ $dormantTenants->count() > 0 ? 'rgba(245,158,11,0.4)' : 'var(--border-color)' }};">
        <div class="card-header">
            <h3 class="card-title" style="color:#f59e0b; display:flex; align-items:center; gap:0.5rem;">
                <span>⚠️</span>
                <span>الإقامات الخاملة (بحاجة لمتابعة فريق المبيعات/الدعم)</span>
            </h3>
            <p class="card-subtitle">إقامات لم تسجل أي مدفوعات خلال الشهر المنصرم</p>
        </div>
        <div class="card-body" style="padding:0;">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="min-width:150px;">الإقامة</th>
                            <th style="text-align:center; min-width:110px;">الحالة</th>
                            <th style="min-width:150px;">آخر نشاط مسجل</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dormantTenants as $dt)
                            <tr>
                                <td style="font-weight:700; color:var(--text-primary);">{{ $dt->name }}</td>
                                <td style="text-align:center;">
                                    <span class="badge {{ $dt->status === 'ACTIVE' ? 'badge-warning' : 'badge-danger' }}">
                                        {{ $dt->status === 'PENDING_APPROVAL' ? 'قيد الموافقة' : ($dt->status === 'ACTIVE' ? 'نشطة' : 'موقوفة') }}
                                    </span>
                                </td>
                                <td style="color:var(--text-muted); font-size:0.85rem;">
                                    {{ $dt->last_payment_date ? $dt->last_payment_date->diffForHumans() : 'لا توجد حركات سابقة' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" style="text-align:center; padding:2rem; color:var(--text-muted);">
                                    ✓ كافّة الإقامات نشطة ومسجلة لحركات مالية حديثة!
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Master Matrix of all residences --}}
<div class="card">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h3 class="card-title">🏢 جدول الأداء المالي والتشغيلي الشامل للإقامات</h3>
            <p class="card-subtitle">التقرير التفصيلي لجميع الإقامات والمنظمات المشتركة في المنصة</p>
        </div>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="min-width:180px;">الإقامة</th>
                        <th style="min-width:100px;">المدينة</th>
                        <th style="text-align:center; min-width:110px;">الحالة</th>
                        <th style="text-align:center; min-width:80px;">الشقق</th>
                        <th style="text-align:center; min-width:90px;">المستخدمون</th>
                        <th style="min-width:130px;">المقبوضات</th>
                        <th style="min-width:130px;">المصاريف</th>
                        <th style="min-width:130px;">الرصيد الصافي</th>
                        <th style="text-align:center; min-width:110px;">حالة النشاط</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tenants as $tenant)
                        <tr>
                            <td>
                                <div style="font-weight:700; color:var(--text-primary);">{{ $tenant->name }}</div>
                            </td>
                            <td style="color:var(--text-secondary);">{{ $tenant->city ?? '—' }}</td>
                            <td style="text-align:center;">
                                @if($tenant->status === 'ACTIVE')
                                    <span class="badge badge-success">نشطة</span>
                                @elseif($tenant->status === 'PENDING_APPROVAL')
                                    <span class="badge badge-warning">قيد الموافقة</span>
                                @else
                                    <span class="badge badge-danger">موقوفة</span>
                                @endif
                            </td>
                            <td style="text-align:center;">
                                <span class="badge badge-info" style="font-size:0.85rem;">{{ $tenant->apartments_count }}</span>
                            </td>
                            <td style="text-align:center;">
                                <span class="badge badge-secondary" style="font-size:0.85rem;">{{ $tenant->users_count }}</span>
                            </td>
                            <td style="font-weight:700; color:#10b981; font-family:monospace; font-size:0.95rem;">
                                {{ number_format($tenant->total_collected, 2) }} <span class="currency-unit">DH</span>
                            </td>
                            <td style="font-weight:700; color:#ef4444; font-family:monospace; font-size:0.95rem;">
                                {{ number_format($tenant->total_expenses, 2) }} <span class="currency-unit">DH</span>
                            </td>
                            <td style="font-weight:800; color:#6366f1; font-family:monospace; font-size:0.95rem;">
                                {{ number_format($tenant->net_treasury, 2) }} <span class="currency-unit">DH</span>
                            </td>
                            <td style="text-align:center;">
                                @if($tenant->is_dormant)
                                    <span class="badge badge-warning" style="font-size:0.75rem;">خاملة</span>
                                @else
                                    <span class="badge badge-success" style="font-size:0.75rem;">نشطة جداً</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
