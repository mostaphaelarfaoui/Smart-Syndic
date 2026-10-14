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

        <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-blocked-accounts').style.display='flex'" style="border-color:{{ $blockedUsersCount > 0 ? '#ef4444' : 'var(--border-color)' }}; color:{{ $blockedUsersCount > 0 ? '#ef4444' : 'inherit' }}; font-weight:700;">
            <span>🔒 الحسابات المحظورة ({{ $blockedUsersCount }})</span>
        </button>
    </div>
</div>

{{-- Urgent Blocked Accounts Banner if any user is locked out due to 15 failed attempts --}}
@if($blockedUsersCount > 0)
    <div style="background:rgba(239, 68, 68, 0.12); border:1px solid rgba(239, 68, 68, 0.4); border-radius:var(--radius-lg); padding:1.25rem 1.5rem; margin-bottom:1.5rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
        <div style="display:flex; align-items:center; gap:0.75rem;">
            <div style="background:#ef4444; color:#fff; width:44px; height:44px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.4rem; box-shadow:0 0 15px rgba(239,68,68,0.5);">
                🔒
            </div>
            <div>
                <div style="font-weight:800; font-size:1.1rem; color:#ef4444;">
                    تنبيه أمني: يوجد {{ $blockedUsersCount }} حساب محظور بسبب تجاوز 15 محاولة دخول خاطئة
                </div>
                <div style="font-size:0.875rem; color:var(--text-secondary);">
                    بصفتك السوبر أدمن، يمكنك مراجعة الحسابات المحظورة وفك الحظر عنها وتصفير محاولات الدخول فوراً.
                </div>
            </div>
        </div>
        <button type="button" class="btn btn-danger" onclick="document.getElementById('modal-blocked-accounts').style.display='flex'" style="display:flex; align-items:center; gap:0.5rem; font-weight:700; padding:0.6rem 1.25rem; box-shadow:0 4px 12px rgba(239,68,68,0.3);">
            <span>🔓 فتح نافذة فك الحظر</span>
            <span>&larr;</span>
        </button>
    </div>
@endif

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
                                    @elseif($tenant->status === 'REJECTED')
                                        <span class="badge badge-danger" style="background:rgba(239,68,68,0.15); color:#ef4444;">مرفوضة</span>
                                    @else
                                        <span class="badge badge-danger">موقوفة</span>
                                    @endif
                                </td>
                                <td>
                                    <div style="display:flex; gap:0.4rem; flex-wrap:wrap; align-items:center;">
                                        @if($tenant->status === 'PENDING_APPROVAL')
                                            <form method="POST" action="{{ route('superadmin.tenants.approve', $tenant->id) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-primary" style="padding:0.25rem 0.6rem; font-size:0.75rem;">
                                                    موافقة
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('superadmin.tenants.reject', $tenant->id) }}" data-confirm="هل تؤكد رفض تسجيل هذه الإقامة وحساب السانديك التابع لها؟" data-confirm-title="رفض الإقامة" data-confirm-type="danger" data-confirm-btn="رفض التسجيل">
                                                @csrf
                                                <button type="submit" class="btn btn-sm" style="padding:0.25rem 0.6rem; font-size:0.75rem; background:rgba(239,68,68,0.1); color:#ef4444; border:1px solid rgba(239,68,68,0.25);">
                                                    رفض
                                                </button>
                                            </form>
                                        @elseif($tenant->status === 'ACTIVE')
                                            <form method="POST" action="{{ route('superadmin.tenants.impersonate', $tenant->id) }}" data-confirm="هل تريد الدخول كمسير لإقامة {{ $tenant->name }} للدعم الفني؟ سيتم تسجيل الجلسة في سجل الرقابة." data-confirm-title="الدخول كمسير للإقامة" data-confirm-type="info" data-confirm-btn="دخول كمسير">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-secondary" style="padding:0.25rem 0.5rem; font-size:0.75rem; color:#6366f1; border-color:rgba(99,102,241,0.3);" title="دخول كمسير للدعم الفني">
                                                    👤 دخول
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('superadmin.tenants.suspend', $tenant->id) }}" data-confirm="تنبيه: سيتم إيقاف الإقامة وكافة حسابات السانديك والسكان التابعة لها فوراً. هل تريد المتابعة؟" data-confirm-title="توقيف الإقامة" data-confirm-type="warning" data-confirm-btn="توقيف الإقامة">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-danger" style="padding:0.25rem 0.6rem; font-size:0.75rem;">
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

                                        <form method="POST" action="{{ route('superadmin.tenants.destroy', $tenant->id) }}"
                                              data-confirm="تحذير شديد: سيتم حذف الإقامة وكافة حسابات السانديك والسكان التابعة لها وجميع بياناتها نهائياً. هل أنت متأكد؟"
                                              data-confirm-title="حذف الإقامة نهائياً"
                                              data-confirm-type="danger"
                                              data-confirm-password="true"
                                              data-confirm-btn="حذف نهائي لا رجعة فيه">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm" style="padding:0.25rem 0.5rem; font-size:0.75rem; background:rgba(239,68,68,0.08); color:#ef4444; border:1px solid rgba(239,68,68,0.2);" title="حذف نهائي">
                                                🗑️
                                            </button>
                                        </form>
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

{{-- ─── Dedicated Modal Window: Blocked Accounts Unban Center ────────────────── --}}
<div id="modal-blocked-accounts" style="display:none;" class="modal-backdrop">
    <div class="modal-dialog" style="max-width:820px; width:95%;">
        <div class="modal-header" style="background:rgba(239,68,68,0.06); border-bottom:1px solid rgba(239,68,68,0.2); padding:1.25rem 1.5rem;">
            <div style="display:flex; align-items:center; gap:0.75rem;">
                <div style="background:rgba(239,68,68,0.15); color:#ef4444; width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
                    🛡️
                </div>
                <div>
                    <h3 class="modal-title" style="color:#ef4444; font-size:1.2rem; margin:0; font-weight:800;">
                        نافذة فك الحظر الأمني عن الحسابات
                    </h3>
                    <div style="font-size:0.82rem; color:var(--text-muted); margin-top:0.2rem;">
                        إدارة الحسابات المحظورة أوتوماتيكياً بسبب تجاوز 15 محاولة دخول فاشلة متتالية (خاص بالسوبر أدمن فقط)
                    </div>
                </div>
            </div>
            <button type="button" class="modal-close" onclick="document.getElementById('modal-blocked-accounts').style.display='none'" title="إغلاق">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="modal-body" style="padding:1.5rem; max-height:70vh; overflow-y:auto;">
            @if($blockedUsers->isEmpty())
                <div style="text-align:center; padding:3rem 1.5rem;">
                    <div style="font-size:3rem; margin-bottom:0.75rem;">✓ 🛡️</div>
                    <h4 style="color:var(--text-primary); font-weight:800; font-size:1.15rem; margin-bottom:0.35rem;">لا توجد أي حسابات محظورة حالياً</h4>
                    <p style="color:var(--text-muted); font-size:0.9rem; margin:0;">
                        كافة حسابات المشرفين، السانديك، والسكان تعمل بانتظام وأمان تام دون أي حظر أمني.
                    </p>
                </div>
            @else
                <div style="margin-bottom:1rem; padding:0.75rem 1rem; border-radius:8px; background:rgba(99,102,241,0.08); border:1px solid rgba(99,102,241,0.2); font-size:0.85rem; color:var(--text-secondary);">
                    💡 <strong>توضيح أمني:</strong> عند الضغط على "فك الحظر"، سيتم فوراً إعادة تفعيل الحساب وتصفير عداد المحاولات الفاشلة (`failed_login_attempts = 0`) مع إزالة قفل الدخول وتسجيل العملية في سجل التدقيق الأمني.
                </div>
                <div style="display:flex; flex-direction:column; gap:0.9rem;">
                    @foreach($blockedUsers as $bUser)
                        <div style="background:var(--bg-main); border:1px solid rgba(239,68,68,0.25); border-right:4px solid #ef4444; border-radius:var(--radius-md); padding:1.1rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
                            <div style="flex:1; min-width:260px;">
                                <div style="display:flex; align-items:center; gap:0.6rem; margin-bottom:0.4rem;">
                                    <span style="font-weight:800; font-size:1.05rem; color:var(--text-primary);">{{ $bUser->full_name }}</span>
                                    @if($bUser->role === 'ADMIN_SYNDIC')
                                        <span class="badge badge-warning" style="font-size:0.72rem;">👔 سانديك</span>
                                    @elseif($bUser->role === 'SUPER_ADMIN')
                                        <span class="badge badge-danger" style="font-size:0.72rem;">⭐ سوبر أدمن</span>
                                    @else
                                        <span class="badge badge-info" style="font-size:0.72rem;">🏠 ساكن</span>
                                    @endif
                                    <span class="badge badge-danger" style="font-size:0.72rem; background:rgba(239,68,68,0.15); color:#ef4444; border:1px solid rgba(239,68,68,0.3);">
                                        ⛔ {{ $bUser->failed_login_attempts }} محاولة فاشلة
                                    </span>
                                </div>
                                <div style="font-size:0.85rem; color:var(--text-muted); font-family:monospace; margin-bottom:0.3rem;">
                                    📧 {{ $bUser->email }} • 📱 {{ $bUser->phone }}
                                </div>
                                <div style="font-size:0.82rem; color:var(--text-secondary); line-height:1.4;">
                                    الإقامة التابع لها: <strong>{{ $bUser->tenant?->name ?? 'غير مرتبط بإقامة (إدارة عليا)' }}</strong>
                                    @if($bUser->blocked_at)
                                        <br>
                                        <span style="color:#ef4444;">تاريخ الحظر:</span> {{ $bUser->blocked_at->format('Y-m-d H:i') }} ({{ $bUser->blocked_at->diffForHumans() }})
                                    @endif
                                </div>
                                @if($bUser->blocked_reason)
                                    <div style="font-size:0.78rem; color:#ef4444; margin-top:0.35rem; font-weight:600;">
                                        ⚠️ السبب: {{ $bUser->blocked_reason }}
                                    </div>
                                @endif
                            </div>
                            <div>
                                <form method="POST" action="{{ route('superadmin.users.unblock', $bUser->id) }}"
                                      data-confirm="هل تؤكد فك الحظر الأمني عن حساب {{ $bUser->full_name }} وتصفير محاولات الدخول؟"
                                      data-confirm-title="تأكيد فك الحظر الأمني"
                                      data-confirm-type="success"
                                      data-confirm-btn="فك الحظر الآن">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success" style="display:flex; align-items:center; gap:0.45rem; padding:0.55rem 1.1rem; font-weight:700; font-size:0.875rem; box-shadow:0 3px 10px rgba(16,185,129,0.35);">
                                        <span>🔓</span>
                                        <span>فك الحظر وإعادة التفعيل</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="modal-footer" style="padding:1rem 1.5rem; display:flex; justify-content:space-between; align-items:center; background:var(--bg-main);">
            <div style="font-size:0.82rem; color:var(--text-muted); display:flex; align-items:center; gap:0.4rem;">
                <span>🔒</span>
                <span>حق فك الحظر الأمني محصور بالسوبر أدمن حصراً وفقاً للبروتوكول الأمني.</span>
            </div>
            <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('modal-blocked-accounts').style.display='none'">
                إغلاق النافذة
            </button>
        </div>
    </div>
</div>

@endsection
