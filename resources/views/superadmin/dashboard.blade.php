@extends('layouts.superadmin')

@section('title', 'لوحة القيادة المركزية — مركز العمليات والأمان')

@section('content')
<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem; margin-bottom:1.5rem;">
    <div>
        <div class="page-pretitle" style="color:#6366f1; font-weight:700; font-size:0.85rem; display:flex; align-items:center; gap:0.4rem;">
            <span>🛡️</span>
            <span>SMART SYNDIC OPERATIONS & SECURITY CENTER</span>
        </div>
        <h1 class="page-title" style="margin:0.25rem 0 0.5rem 0; font-size:1.85rem;">مركز القيادة والعمليات المركزي</h1>
        <p style="color:var(--text-muted); margin:0; font-size:0.95rem;">
            رصد مباشر لصحة السيرفر، الإقامات المشتركة، التدفقات المالية، وتتبع التهديدات الأمنية ومحاولات الاختراق.
        </p>
    </div>

    <div style="display:flex; gap:0.75rem; align-items:center; flex-wrap:wrap;">
        <a href="{{ route('superadmin.server.backup') }}" class="btn btn-primary" style="background:linear-gradient(135deg, #10b981 0%, #059669 100%); border:none; box-shadow:0 4px 12px rgba(16,185,129,0.25);">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            <span>نسخ احتياطي فوري للبيانات</span>
        </a>

        <a href="{{ route('superadmin.security.index') }}" class="btn btn-secondary">
            <span>🛡️ جدار الحماية</span>
        </a>

        <a href="{{ route('superadmin.server.index') }}" class="btn btn-secondary">
            <span>🖥️ فحص السيرفر</span>
        </a>
    </div>
</div>

{{-- Real-time Fraud Alerts Banner if anomalies detected --}}
@if(!empty($fraudAlerts) && count($fraudAlerts) > 0)
    <div style="background:rgba(239, 68, 68, 0.12); border:1px solid rgba(239, 68, 68, 0.4); border-radius:var(--radius-lg); padding:1.25rem 1.5rem; margin-bottom:1.5rem;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:0.75rem;">
            <div style="display:flex; align-items:center; gap:0.5rem; color:#ef4444; font-weight:800; font-size:1.05rem;">
                <span style="font-size:1.3rem;">⚠️</span>
                <span>رصد أنشطة ومعاملات مشبوهة تتطلب تدقيق الإدارة ({{ count($fraudAlerts) }} تنبيه)</span>
            </div>
            <a href="{{ route('superadmin.security.index') }}" class="btn btn-sm btn-secondary" style="border-color:#ef4444; color:#ef4444; font-size:0.8rem;">
                عرض كافة التفاصيل والحظر &larr;
            </a>
        </div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:0.75rem;">
            @foreach(array_slice($fraudAlerts, 0, 3) as $alert)
                <div style="background:var(--bg-card); border:1px solid rgba(239,68,68,0.25); border-radius:var(--radius-md); padding:0.75rem 1rem;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.35rem;">
                        <span class="badge {{ $alert['severity'] === 'CRITICAL' ? 'badge-danger' : 'badge-warning' }}" style="font-size:0.7rem;">
                            {{ $alert['severity'] }}
                        </span>
                        <span style="font-size:0.8rem; color:var(--text-muted);">{{ $alert['tenant_name'] }}</span>
                    </div>
                    <div style="font-weight:700; font-size:0.9rem; color:var(--text-primary); margin-bottom:0.2rem;">{{ $alert['title'] }}</div>
                    <div style="font-size:0.8rem; color:var(--text-secondary); line-height:1.4;">{{ $alert['description'] }}</div>
                </div>
            @endforeach
        </div>
    </div>
@endif

{{-- System & Security Quick Status Strip --}}
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
    <div class="kpi-card" style="border-top:3px solid #6366f1;">
        <div class="kpi-header">
            <span class="kpi-label">حالة السيرفر وقاعدة البيانات</span>
            <span class="badge badge-success" style="font-size:0.7rem;">{{ $systemHealth['db_status'] }}</span>
        </div>
        <div class="kpi-value" style="font-size:1.4rem;">
            {{ $systemHealth['db_latency_ms'] }} <span style="font-size:0.9rem; color:var(--text-muted);">ms استجابة</span>
        </div>
        <div class="kpi-sub" style="display:flex; justify-content:space-between; margin-top:0.5rem; font-size:0.8rem;">
            <span>حجم DB: {{ $systemHealth['db_size_mb'] }} MB</span>
            <span>سلامة DB: {{ $systemHealth['db_integrity'] }}</span>
        </div>
    </div>

    <div class="kpi-card" style="border-top:3px solid #f59e0b;">
        <div class="kpi-header">
            <span class="kpi-label">مساحة التخزين والذاكرة</span>
            <span class="badge badge-warning" style="font-size:0.7rem;">{{ $systemHealth['disk_percentage'] }}% مستهلك</span>
        </div>
        <div class="kpi-value" style="font-size:1.4rem;">
            {{ $systemHealth['disk_free_gb'] }} <span style="font-size:0.9rem; color:var(--text-muted);">GB مساحة فارغة</span>
        </div>
        <div class="kpi-sub" style="display:flex; justify-content:space-between; margin-top:0.5rem; font-size:0.8rem;">
            <span>استهلاك RAM: {{ $systemHealth['memory_usage_mb'] }} MB</span>
            <span>الحد: {{ $systemHealth['memory_limit'] }}</span>
        </div>
    </div>

    <div class="kpi-card" style="border-top:3px solid #ef4444;">
        <div class="kpi-header">
            <span class="kpi-label">رصد الاختراق والحماية</span>
            <span class="badge {{ $openIncidentsCount > 0 ? 'badge-danger' : 'badge-success' }}" style="font-size:0.7rem;">
                {{ $openIncidentsCount }} بلاغات مفتوحة
            </span>
        </div>
        <div class="kpi-value" style="font-size:1.4rem; color:{{ $criticalIncidentsCount > 0 ? '#ef4444' : 'var(--text-primary)' }};">
            {{ $criticalIncidentsCount }} <span style="font-size:0.9rem; color:var(--text-muted);">تهديد حرج</span>
        </div>
        <div class="kpi-sub" style="display:flex; justify-content:space-between; margin-top:0.5rem; font-size:0.8rem;">
            <span>عناوين محظورة بالجدار: {{ $blockedIpsCount }}</span>
            <a href="{{ route('superadmin.security.index') }}" style="color:#6366f1; text-decoration:none;">إدارة الحظر &larr;</a>
        </div>
    </div>

    <div class="kpi-card" style="border-top:3px solid #10b981;">
        <div class="kpi-header">
            <span class="kpi-label">إجمالي الأموال المدارة عبر التطبيق</span>
            <span class="badge badge-info" style="font-size:0.7rem;">GTV</span>
        </div>
        <div class="kpi-value" style="font-size:1.4rem; color:#10b981;">
            {{ number_format($totalPlatformPayments, 2) }} <span style="font-size:0.85rem; font-weight:400;">درهم</span>
        </div>
        <div class="kpi-sub" style="display:flex; justify-content:space-between; margin-top:0.5rem; font-size:0.8rem;">
            <span>إجمالي المصاريف: {{ number_format($totalPlatformExpenses, 2) }} DH</span>
        </div>
    </div>
</div>

{{-- SaaS Core Platform KPIs --}}
<div class="kpi-grid" style="margin-bottom:1.5rem;">
    <div class="kpi-card">
        <div class="kpi-header">
            <span class="kpi-label">إجمالي الإقامات المسجلة</span>
            <span class="kpi-icon" style="background:rgba(99,102,241,0.12); color:#6366f1;">🏢</span>
        </div>
        <div class="kpi-value">{{ $totalTenants }}</div>
        <div class="kpi-sub" style="display:flex; gap:0.5rem; align-items:center;">
            <span class="badge badge-success">{{ $activeTenants }} نشطة</span>
            <span class="badge badge-warning">{{ $pendingTenants }} قيد الموافقة</span>
            @if($suspendedTenants > 0)
                <span class="badge badge-danger">{{ $suspendedTenants }} موقوفة</span>
            @endif
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-header">
            <span class="kpi-label">نسبة الإقامات النشطة</span>
            <span class="kpi-icon" style="background:rgba(16,185,129,0.12); color:#10b981;">📈</span>
        </div>
        <div class="kpi-value">{{ $activeRate }}%</div>
        <div class="kpi-sub">
            معدل التشغيل الفعلي للإقامات المشتركة في المنصة
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-header">
            <span class="kpi-label">إجمالي السانديك والمسيرين</span>
            <span class="kpi-icon" style="background:rgba(245,158,11,0.12); color:#f59e0b;">👔</span>
        </div>
        <div class="kpi-value">{{ $totalSyndics }}</div>
        <div class="kpi-sub">
            مديرو الإقامات المعتمدون
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-header">
            <span class="kpi-label">إجمالي السكان والملاك</span>
            <span class="kpi-icon" style="background:rgba(14,165,233,0.12); color:#0ea5e9;">👥</span>
        </div>
        <div class="kpi-value">{{ $totalResidents }}</div>
        <div class="kpi-sub">
            المستخدمون المسجلون عبر تطبيق الهاتف
        </div>
    </div>
</div>

{{-- Two Columns: Left = Recent Organizations / Right = Security & Threat Stream --}}
<div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:1.5rem;">
    {{-- Left: Organizations --}}
    <div class="card" style="min-width:0;">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h3 class="card-title">🏢 أحدث الإقامات المسجلة</h3>
                <p class="card-subtitle">متابعة طلبات الانضمام والموافقة على الإقامات الجديدة</p>
            </div>
            <a href="{{ route('superadmin.tenants.index') }}" class="btn btn-sm btn-secondary">
                عرض الكل &larr;
            </a>
        </div>
        <div class="card-body" style="padding:0;">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>الإقامة</th>
                            <th>المدينة</th>
                            <th>الحالة</th>
                            <th>الإجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTenants as $tenant)
                            <tr>
                                <td>
                                    <div style="font-weight:700;">{{ $tenant->name }}</div>
                                    <div style="font-size:0.75rem; color:var(--text-muted); font-family:monospace;">{{ substr($tenant->id, 0, 8) }}...</div>
                                </td>
                                <td>{{ $tenant->city ?? '—' }}</td>
                                <td>
                                    @if($tenant->status === 'ACTIVE')
                                        <span class="badge badge-success">نشطة</span>
                                    @elseif($tenant->status === 'PENDING_APPROVAL')
                                        <span class="badge badge-warning">قيد الموافقة</span>
                                    @else
                                        <span class="badge badge-danger">موقوفة</span>
                                    @endif
                                </td>
                                <td>
                                    <div style="display:flex; gap:0.4rem;">
                                        @if($tenant->status === 'PENDING_APPROVAL')
                                            <form method="POST" action="{{ route('superadmin.tenants.approve', $tenant->id) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-primary" style="padding:0.25rem 0.6rem; font-size:0.75rem;">
                                                    موافقة
                                                </button>
                                            </form>
                                        @elseif($tenant->status === 'ACTIVE')
                                            <form method="POST" action="{{ route('superadmin.tenants.suspend', $tenant->id) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-danger" style="padding:0.25rem 0.6rem; font-size:0.75rem;" onclick="return confirm('هل تريد توقيف الإقامة؟')">
                                                    توقيف
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('superadmin.tenants.approve', $tenant->id) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-secondary" style="padding:0.25rem 0.6rem; font-size:0.75rem;">
                                                    إعادة التفعيل
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align:center; padding:2rem; color:var(--text-muted);">
                                    لا توجد إقامات مسجلة حالياً.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Right: Security Incidents & Threat Log --}}
    <div class="card" style="min-width:0;">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h3 class="card-title">🛡️ أحدث التهديدات والبلاغات الأمنية</h3>
                <p class="card-subtitle">رصد محاولات الدخول الفاشلة والهجمات وحالات الحظر</p>
            </div>
            <a href="{{ route('superadmin.security.index') }}" class="btn btn-sm btn-secondary">
                مركز الأمان &larr;
            </a>
        </div>
        <div class="card-body" style="padding:0;">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>نوع التهديد</th>
                            <th>المصدر (IP)</th>
                            <th>الخطورة</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentIncidents as $incident)
                            <tr>
                                <td>
                                    <div style="font-weight:700; font-size:0.85rem;">{{ $incident->incident_type }}</div>
                                    <div style="font-size:0.75rem; color:var(--text-muted); max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                        {{ $incident->details ?? $incident->target_email }}
                                    </div>
                                </td>
                                <td>
                                    <span style="font-family:monospace; font-size:0.8rem;">{{ $incident->ip_address ?? '—' }}</span>
                                </td>
                                <td>
                                    @if($incident->severity === 'CRITICAL')
                                        <span class="badge badge-danger">حرج</span>
                                    @elseif($incident->severity === 'HIGH')
                                        <span class="badge badge-danger">عالي</span>
                                    @elseif($incident->severity === 'MEDIUM')
                                        <span class="badge badge-warning">متوسط</span>
                                    @else
                                        <span class="badge badge-info">منخفض</span>
                                    @endif
                                </td>
                                <td>
                                    @if($incident->status === 'OPEN')
                                        <span class="badge badge-warning">مفتوح</span>
                                    @else
                                        <span class="badge badge-success">تم الحل</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align:center; padding:2rem; color:var(--text-muted);">
                                    🛡️ النظام في حالة أمان ممتازة — لا توجد بلاغات أمنية حديثة.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
