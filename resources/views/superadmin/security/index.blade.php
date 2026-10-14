@extends('layouts.superadmin')

@section('title', 'مركز الأمان والحماية ورصد الاحتيال')

@section('content')
<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem; margin-bottom:1.5rem;">
    <div>
        <div class="page-pretitle" style="color:#ef4444; font-weight:700; font-size:0.85rem;">THREAT INTELLIGENCE & FRAUD PREVENTION</div>
        <h1 class="page-title" style="margin:0.25rem 0 0.5rem 0; font-size:1.85rem;">مركز الأمان ورصد محاولات الاختراق والنصب</h1>
        <p style="color:var(--text-muted); margin:0; font-size:0.95rem;">
            مراقبة التهديدات السيبرانية، تتبع محاولات التخمين (Brute-force)، رصد الشبهات المالية، وإدارة الجدار الناري للأرقام المعنونة (IPs).
        </p>
    </div>

    <div style="display:flex; gap:0.75rem; align-items:center;">
        <a href="#firewall-section" class="btn btn-secondary" style="border-color:#ef4444; color:#ef4444;">
            <span>🛡️ الجدار الناري وحظر العناوين</span>
        </a>
    </div>
</div>

{{-- Top Security Stat Cards --}}
<div class="kpi-grid" style="margin-bottom:1.5rem;">
    <div class="kpi-card" style="border-top:3px solid #ef4444;">
        <div class="kpi-header">
            <span class="kpi-label">البلاغات الأمنية المفتوحة</span>
            <span class="badge badge-danger">قيد المتابعة</span>
        </div>
        <div class="kpi-value" style="color:#ef4444;">{{ $counts['open'] }}</div>
        <div class="kpi-sub">
            تحتاج إلى مراجعة وتدقيق الإدارة
        </div>
    </div>

    <div class="kpi-card" style="border-top:3px solid #f97316;">
        <div class="kpi-header">
            <span class="kpi-label">تهديدات بالغة الخطورة (Critical)</span>
            <span class="badge badge-warning">عالي الحساسية</span>
        </div>
        <div class="kpi-value" style="color:#f97316;">{{ $counts['critical'] }}</div>
        <div class="kpi-sub">
            محاولات Brute-Force أو اختراق صلاحيات
        </div>
    </div>

    <div class="kpi-card" style="border-top:3px solid #6366f1;">
        <div class="kpi-header">
            <span class="kpi-label">عناوين IP محظورة بالجدار</span>
            <span class="badge badge-info">Active Bans</span>
        </div>
        <div class="kpi-value">{{ $counts['blocked'] }}</div>
        <div class="kpi-sub">
            عناوين ممنوعة نهائياً من الاتصال
        </div>
    </div>

    <div class="kpi-card" style="border-top:3px solid #dc2626;">
        <div class="kpi-header">
            <span class="kpi-label">حسابات محظورة (15 محاولة)</span>
            <span class="badge badge-danger">Locked Users</span>
        </div>
        <div class="kpi-value" style="color:#dc2626;">{{ $counts['blocked_users'] }}</div>
        <div class="kpi-sub">
            حظر دائم بسبب تخمين كلمات المرور
        </div>
    </div>

    <div class="kpi-card" style="border-top:3px solid #10b981;">
        <div class="kpi-header">
            <span class="kpi-label">إجمالي البلاغات المسجلة</span>
            <span class="kpi-icon" style="background:rgba(16,185,129,0.12); color:#10b981;">📜</span>
        </div>
        <div class="kpi-value">{{ $counts['total'] }}</div>
        <div class="kpi-sub">
            سجل الحوادث الكلي منذ إطلاق المنصة
        </div>
    </div>
</div>

{{-- Heuristic Fraud Detection Engine Alerts --}}
<div class="card" style="margin-bottom:1.5rem; border-color:rgba(239,68,68,0.3);">
    <div class="card-header" style="background:rgba(239,68,68,0.06); display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h3 class="card-title" style="color:#ef4444; display:flex; align-items:center; gap:0.5rem;">
                <span>⚡</span>
                <span>محرك كشف النصب والشبهات المالية التلقائي (Automated Fraud Detection)</span>
            </h3>
            <p class="card-subtitle">خوارزميات ذكية ترصد تدفقات الأموال السريعة، تكرار الفواتير، والعمليات النقدية المشبوهة</p>
        </div>
        <span class="badge {{ count($fraudAlerts) > 0 ? 'badge-danger' : 'badge-success' }}">
            {{ count($fraudAlerts) }} تنبيه نشط
        </span>
    </div>
    <div class="card-body">
        @if(empty($fraudAlerts))
            <div style="text-align:center; padding:1.5rem; color:var(--text-muted);">
                <div style="font-size:2rem; margin-bottom:0.5rem;">✓ 🛡️</div>
                <div style="font-weight:700; color:var(--text-primary);">لم يتم رصد أي أنشطة احتيالية أو شبهات مالية في الوقت الراهن</div>
                <div style="font-size:0.85rem;">المعاملات المالية والتدفقات تعمل بانتظام طبيعي عبر كافة الإقامات.</div>
            </div>
        @else
            <div style="display:flex; flex-direction:column; gap:0.75rem;">
                @foreach($fraudAlerts as $alert)
                    <div style="background:var(--bg-main); border:1px solid rgba(239,68,68,0.3); border-right:4px solid #ef4444; border-radius:var(--radius-md); padding:1rem; display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem;">
                        <div>
                            <div style="display:flex; align-items:center; gap:0.6rem; margin-bottom:0.3rem;">
                                <span class="badge {{ $alert['severity'] === 'CRITICAL' ? 'badge-danger' : 'badge-warning' }}" style="font-size:0.75rem;">
                                    {{ $alert['severity'] }}
                                </span>
                                <span style="font-weight:800; font-size:1rem; color:var(--text-primary);">{{ $alert['title'] }}</span>
                                <span class="badge badge-info" style="font-size:0.75rem;">{{ $alert['tenant_name'] }}</span>
                            </div>
                            <div style="color:var(--text-secondary); font-size:0.9rem; line-height:1.5;">
                                {{ $alert['description'] }}
                            </div>
                        </div>
                        <div style="display:flex; gap:0.5rem; align-items:center;">
                            <a href="{{ route('superadmin.tenants.index', ['search' => $alert['tenant_name']]) }}" class="btn btn-sm btn-secondary" style="font-size:0.8rem;">
                                فحص الإقامة
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

{{-- Two Columns: Firewall & IP Management / Incidents Table --}}
<div id="firewall-section" style="display:grid; grid-template-columns:1fr 2fr; gap:1.5rem; margin-bottom:1.5rem;">
    {{-- Left: Firewall IP Ban Manager --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">🛡️ الجدار الناري وحظر العناوين</h3>
            <p class="card-subtitle">إضافة عناوين IP للمنع الفوري</p>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('superadmin.security.block-ip') }}" style="margin-bottom:1.5rem;">
                @csrf
                <div class="form-group" style="margin-bottom:0.75rem;">
                    <label class="form-label">عنوان IP المستهدف</label>
                    <input type="text" name="ip_address" class="form-control" placeholder="مثال: 197.230.12.44" required style="font-family:monospace;">
                    @error('ip_address') <span style="color:#ef4444; font-size:0.75rem;">{{ $message }}</span> @enderror
                </div>
                <div class="form-group" style="margin-bottom:1rem;">
                    <label class="form-label">سبب الحظر الأمني</label>
                    <input type="text" name="reason" class="form-control" placeholder="مثال: محاولات تخمين كلمات مرور متكررة" required>
                    @error('reason') <span style="color:#ef4444; font-size:0.75rem;">{{ $message }}</span> @enderror
                </div>
                <button type="submit" class="btn btn-danger" style="width:100%; justify-content:center;">
                    ⛔ حظر هذا العنوان فوراً
                </button>
            </form>

            <h4 style="font-size:0.95rem; font-weight:700; margin:1rem 0 0.5rem 0;">العناوين المحظورة حالياً:</h4>
            <div style="max-height:300px; overflow-y:auto; display:flex; flex-direction:column; gap:0.5rem;">
                @forelse($blockedIps as $bip)
                    <div style="background:var(--bg-main); border:1px solid var(--border-color); border-radius:var(--radius-sm); padding:0.6rem 0.8rem; display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <div style="font-family:monospace; font-weight:700; color:#ef4444; font-size:0.85rem;">{{ $bip->ip_address }}</div>
                            <div style="font-size:0.75rem; color:var(--text-muted);">{{ $bip->reason }}</div>
                        </div>
                        <form method="POST" action="{{ route('superadmin.security.unblock-ip', $bip->id) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-secondary" style="padding:0.2rem 0.5rem; font-size:0.75rem;" title="فك الحظر">
                                فك الحظر
                            </button>
                        </form>
                    </div>
                @empty
                    <div style="font-size:0.8rem; color:var(--text-muted); text-align:center; padding:1rem;">
                        لا توجد عناوين محظورة حالياً.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Right: Security Incidents Explorer --}}
    <div class="card">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem;">
            <div>
                <h3 class="card-title">📜 سجل التهديدات والحوادث الأمنية</h3>
                <p class="card-subtitle">تتبع كافة محاولات الدخول الفاشلة والأنشطة المرصودة</p>
            </div>

            {{-- Filter Pills --}}
            <form method="GET" action="{{ route('superadmin.security.index') }}" style="display:flex; gap:0.4rem; align-items:center;">
                <select name="severity" class="form-control" style="font-size:0.8rem; padding:0.3rem 0.6rem;" onchange="this.form.submit()">
                    <option value="">كافة مستويات الخطورة</option>
                    <option value="CRITICAL" {{ request('severity') === 'CRITICAL' ? 'selected' : '' }}>حرج (Critical)</option>
                    <option value="HIGH" {{ request('severity') === 'HIGH' ? 'selected' : '' }}>عالي (High)</option>
                    <option value="MEDIUM" {{ request('severity') === 'MEDIUM' ? 'selected' : '' }}>متوسط (Medium)</option>
                    <option value="LOW" {{ request('severity') === 'LOW' ? 'selected' : '' }}>منخفض (Low)</option>
                </select>

                <select name="status" class="form-control" style="font-size:0.8rem; padding:0.3rem 0.6rem;" onchange="this.form.submit()">
                    <option value="">كافة الحالات</option>
                    <option value="OPEN" {{ request('status') === 'OPEN' ? 'selected' : '' }}>مفتوح (Open)</option>
                    <option value="RESOLVED" {{ request('status') === 'RESOLVED' ? 'selected' : '' }}>تم الحل (Resolved)</option>
                </select>
            </form>
        </div>
        <div class="card-body" style="padding:0;">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>نوع الحادث</th>
                            <th>الخطورة</th>
                            <th>العنوان (IP)</th>
                            <th>التفاصيل والمستهدف</th>
                            <th>الوقت</th>
                            <th>الحالة والعمليات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($incidents as $inc)
                            <tr>
                                <td>
                                    <div style="font-weight:700; font-size:0.85rem;">{{ $inc->incident_type }}</div>
                                </td>
                                <td>
                                    @if($inc->severity === 'CRITICAL')
                                        <span class="badge badge-danger">حرج</span>
                                    @elseif($inc->severity === 'HIGH')
                                        <span class="badge badge-danger">عالي</span>
                                    @elseif($inc->severity === 'MEDIUM')
                                        <span class="badge badge-warning">متوسط</span>
                                    @else
                                        <span class="badge badge-info">منخفض</span>
                                    @endif
                                </td>
                                <td>
                                    <span style="font-family:monospace; font-size:0.85rem;">{{ $inc->ip_address ?? '—' }}</span>
                                </td>
                                <td>
                                    <div style="font-size:0.8rem; line-height:1.4;">{{ $inc->details }}</div>
                                    @if($inc->target_email)
                                        <div style="font-size:0.75rem; color:var(--text-muted); font-family:monospace;">المستهدف: {{ $inc->target_email }}</div>
                                    @endif
                                </td>
                                <td style="font-size:0.8rem; color:var(--text-muted); white-space:nowrap;">
                                    {{ $inc->created_at->format('Y-m-d H:i') }}
                                </td>
                                <td>
                                    <div style="display:flex; gap:0.4rem; align-items:center;">
                                        @if($inc->status === 'OPEN')
                                            <form method="POST" action="{{ route('superadmin.security.incidents.resolve', $inc->id) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success" style="padding:0.25rem 0.5rem; font-size:0.75rem;">
                                                    إغلاق البلاغ
                                                </button>
                                            </form>
                                        @else
                                            <span class="badge badge-success" style="font-size:0.75rem;">محلول ✓</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align:center; padding:2rem; color:var(--text-muted);">
                                    لا توجد بلاغات أمنية مسجلة تطابق خيارات الفلترة.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($incidents->hasPages())
                <div style="padding:1rem;">
                    {{ $incidents->links() }}
                </div>
            @endif
        </div>
    </div>
{{-- Blocked User Accounts Section (15+ failed attempts) --}}
<div class="card" style="margin-bottom:1.5rem; border-color:rgba(220,38,38,0.3);">
    <div class="card-header" style="background:rgba(220,38,38,0.06); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem;">
        <div>
            <h3 class="card-title" style="color:#ef4444; display:flex; align-items:center; gap:0.5rem;">
                <span>🔒</span>
                <span>الحسابات المحظورة أمنياً بسبب تجاوز 15 محاولة دخول خاطئة</span>
            </h3>
            <p class="card-subtitle">تطبيق الحظر الأوتوماتيكي وحصر حق فك الحظر للسوبر أدمن فقط</p>
        </div>
        <span class="badge {{ $blockedUsers->count() > 0 ? 'badge-danger' : 'badge-success' }}">
            {{ $blockedUsers->count() }} حساب محظور
        </span>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>المستخدم</th>
                        <th>الإقامة</th>
                        <th>الدور</th>
                        <th>عدد المحاولات</th>
                        <th>تاريخ وسبب الحظر</th>
                        <th>الإجراء الأمني</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($blockedUsers as $bUser)
                        <tr>
                            <td>
                                <div style="font-weight:700;">{{ $bUser->full_name }}</div>
                                <div style="font-size:0.75rem; color:var(--text-muted); font-family:monospace;">{{ $bUser->email }} • {{ $bUser->phone }}</div>
                            </td>
                            <td>
                                {{ $bUser->tenant?->name ?? 'إدارة النظام' }}
                            </td>
                            <td>
                                @if($bUser->role === 'ADMIN_SYNDIC')
                                    <span class="badge badge-warning" style="font-size:0.75rem;">👔 سانديك</span>
                                @else
                                    <span class="badge badge-info" style="font-size:0.75rem;">🏠 ساكن</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-danger" style="font-size:0.75rem;">
                                    ⛔ {{ $bUser->failed_login_attempts }} محاولة
                                </span>
                            </td>
                            <td>
                                <div style="font-size:0.8rem; color:var(--text-muted);">{{ $bUser->blocked_at ? $bUser->blocked_at->format('Y-m-d H:i') : '—' }}</div>
                                <div style="font-size:0.75rem; color:#ef4444;">{{ $bUser->blocked_reason ?? 'تجاوز 15 محاولة فاشلة' }}</div>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('superadmin.users.unblock', $bUser->id) }}"
                                      data-confirm="هل تؤكد فك الحظر الأمني عن حساب {{ $bUser->full_name }} وتصفير محاولات الدخول؟"
                                      data-confirm-title="تأكيد فك الحظر الأمني"
                                      data-confirm-type="success"
                                      data-confirm-btn="فك الحظر وتفعيل الحساب">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success" style="padding:0.3rem 0.75rem; font-size:0.8rem; font-weight:700;">
                                        🔓 فك الحظر
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center; padding:2rem; color:var(--text-muted);">
                                ✓ لا توجد أي حسابات محظورة حالياً. جميع الحسابات تعمل بسلامة تامة.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
