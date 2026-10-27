@extends('layouts.superadmin')

@section('title', 'تفاصيل الإقامة: ' . $tenant->name)

@section('content')

{{-- Header & Action Bar --}}
<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1.25rem;">
    <div>
        <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:0.4rem;">
            <a href="{{ route('superadmin.tenants.index') }}" class="btn btn-secondary btn-sm" style="padding:0.4rem 0.75rem;">
                &rarr; العودة لقائمة الإقامات
            </a>
            @if($tenant->status === 'ACTIVE')
                <span class="badge badge-success" style="font-size:0.8rem;">نشطة ومفعلة</span>
            @elseif($tenant->status === 'PENDING_APPROVAL')
                <span class="badge badge-warning" style="font-size:0.8rem;">قيد الموافقة</span>
            @elseif($tenant->status === 'REJECTED')
                <span class="badge badge-danger" style="font-size:0.8rem;">مرفوضة</span>
            @else
                <span class="badge badge-danger" style="font-size:0.8rem;">موقوفة</span>
            @endif
            <span class="badge badge-info" style="font-size:0.8rem;">خطة {{ $tenant->plan_type ?? 'TRIAL' }}</span>
        </div>
        <h1 class="page-title" style="display:flex; align-items:center; gap:0.5rem;">
            <span>🏢</span>
            <span>{{ $tenant->name }}</span>
        </h1>
        <p class="page-subtitle">
            المدينة: <strong>{{ $tenant->city ?? 'غير محددة' }}</strong> — 
            تاريخ التسجيل: <strong>{{ $tenant->created_at ? $tenant->created_at->format('Y/m/d') : '—' }}</strong> — 
            المعرف: <code style="font-size:0.8rem;">{{ $tenant->id }}</code>
        </p>
    </div>

    {{-- Quick Action Buttons --}}
    <div style="display:flex; gap:0.6rem; flex-wrap:wrap; align-items:center;">
        @if($tenant->status === 'ACTIVE')
            {{-- Impersonate Syndic --}}
            <form method="POST" action="{{ route('superadmin.tenants.impersonate', $tenant->id) }}"
                  data-confirm="هل تريد الدخول كمسير لإقامة {{ $tenant->name }} للدعم الفني؟ سيتم تسجيل الجلسة في سجل الرقابة."
                  data-confirm-title="الدخول كمسير للإقامة"
                  data-confirm-type="info"
                  data-confirm-btn="دخول كمسير">
                @csrf
                <button type="submit" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:0.4rem;">
                    <span>👤</span>
                    <span>الدخول بصفة سانديك (تقمص)</span>
                </button>
            </form>

            {{-- Suspend --}}
            <form method="POST" action="{{ route('superadmin.tenants.suspend', $tenant->id) }}"
                  data-confirm="هل تريد تعليق وتوقيف هذه الإقامة؟ لن يتمكن السانديك أو السكان من الدخول."
                  data-confirm-title="توقيف الإقامة"
                  data-confirm-type="danger"
                  data-confirm-btn="توقيف الإقامة">
                @csrf
                <button type="submit" class="btn btn-secondary" style="color:#ef4444; border-color:#ef4444;">
                    توقيف الإقامة
                </button>
            </form>
        @elseif($tenant->status === 'PENDING_APPROVAL')
            {{-- Approve --}}
            <form method="POST" action="{{ route('superadmin.tenants.approve', $tenant->id) }}">
                @csrf
                <button type="submit" class="btn btn-success" style="padding:0.6rem 1.25rem;">
                    ✓ موافقة وتفعيل الإقامة
                </button>
            </form>
            {{-- Reject --}}
            <form method="POST" action="{{ route('superadmin.tenants.reject', $tenant->id) }}"
                  data-confirm="هل تؤكد رفض تسجيل هذه الإقامة وحساب السانديك؟"
                  data-confirm-title="رفض الإقامة"
                  data-confirm-type="danger"
                  data-confirm-btn="رفض">
                @csrf
                <button type="submit" class="btn btn-secondary" style="color:#ef4444;">
                    رفض الطلب
                </button>
            </form>
        @else
            {{-- Unsuspend / Reactivate --}}
            <form method="POST" action="{{ route('superadmin.tenants.approve', $tenant->id) }}">
                @csrf
                <button type="submit" class="btn btn-success">
                    إعادة تفعيل الإقامة
                </button>
            </form>
        @endif

        {{-- Delete Tenant Permanently --}}
        <form method="POST" action="{{ route('superadmin.tenants.destroy', $tenant->id) }}"
              data-confirm="⚠️ تحذير حرج: سيتم حذف إقامة '{{ $tenant->name }}' وجميع شققها وحسابات السانديك والسكان ومصروفاتها ومداخيلها نهائياً! هل أنت متأكد بنسبة 100%؟"
              data-confirm-title="حذف الإقامة نهائياً"
              data-confirm-type="danger"
              data-confirm-btn="نعم، حذف نهائي">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm" style="padding:0.6rem 0.9rem;" title="حذف نهائي">
                🗑️ حذف الإقامة
            </button>
        </form>
    </div>
</div>

{{-- Top Metrics Grid --}}
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); margin-bottom:1.5rem;">
    <div class="stat-card">
        <div class="stat-icon" style="background:rgba(99, 102, 241, 0.12); color:#6366f1;">🏢</div>
        <div>
            <div class="stat-label">الشقق والوحدات</div>
            <div class="stat-value num">{{ $totalApartments }}</div>
            <div style="font-size:0.75rem; color:var(--text-muted); margin-top:0.25rem;">
                <span style="color:#10b981;">{{ $occupiedApartments }} مسكونة</span> • 
                <span style="color:#f59e0b;">{{ $vacantApartments }} شاغرة</span>
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:rgba(14, 165, 233, 0.12); color:#0ea5e9;">👥</div>
        <div>
            <div class="stat-label">المستخدمون المسجلون</div>
            <div class="stat-value num">{{ $tenant->users->count() }}</div>
            <div style="font-size:0.75rem; color:var(--text-muted); margin-top:0.25rem;">
                <span>{{ $syndics->count() }} مسير</span> • 
                <span>{{ $residents->count() }} ساكن</span>
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:rgba(16, 185, 129, 0.12); color:#10b981;">💳</div>
        <div>
            <div class="stat-label">إجمالي المقبوضات المحصلة</div>
            <div class="stat-value num" style="color:#10b981;">{{ number_format($totalPayments, 2) }} <span style="font-size:0.8rem; font-weight:400;">DH</span></div>
            <div style="font-size:0.75rem; color:var(--text-muted); margin-top:0.25rem;">
                منذ إنشاء الإقامة
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:rgba(244, 63, 94, 0.12); color:#f43f5e;">🧾</div>
        <div>
            <div class="stat-label">إجمالي المصاريف والنفقات</div>
            <div class="stat-value num" style="color:#f43f5e;">{{ number_format($totalExpenses, 2) }} <span style="font-size:0.8rem; font-weight:400;">DH</span></div>
            <div style="font-size:0.75rem; color:var(--text-muted); margin-top:0.25rem;">
                الرصيد الصافي: <strong style="color:{{ ($totalPayments - $totalExpenses) >= 0 ? '#10b981' : '#ef4444' }};">{{ number_format($totalPayments - $totalExpenses, 2) }} DH</strong>
            </div>
        </div>
    </div>
</div>

{{-- Two-Column Layout --}}
<div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.5rem; margin-bottom:1.5rem;">
    
    {{-- Left: Syndics & Residence Settings --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">👔 مسيرو الإقامة والاتصال</h3>
            <p class="card-subtitle">بيانات السانديك المشرفين على تدبير هذه الإقامة</p>
        </div>
        <div class="card-body" style="padding:1.25rem;">
            @forelse($syndics as $syndic)
                <div style="display:flex; justify-content:space-between; align-items:center; padding:0.75rem; background:var(--bg-main); border-radius:var(--radius-md); margin-bottom:0.75rem; border:1px solid var(--border-color);">
                    <div style="display:flex; align-items:center; gap:0.75rem;">
                        <div style="width:40px; height:40px; border-radius:50%; background:var(--brand-primary); color:#ffffff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:1rem;">
                            {{ mb_substr($syndic->first_name, 0, 1) }}
                        </div>
                        <div>
                            <div style="font-weight:700;">{{ $syndic->full_name }}</div>
                            <div style="font-size:0.8rem; color:var(--text-muted);">{{ $syndic->email }} • {{ $syndic->phone ?? 'بدون هاتف' }}</div>
                        </div>
                    </div>
                    <div>
                        <form method="POST" action="{{ route('superadmin.users.impersonate', $syndic->id) }}">
                            @csrf
                            <button type="submit" class="btn btn-secondary btn-sm" style="font-size:0.75rem;">
                                تقمص الحساب
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div style="color:var(--text-muted); text-align:center; padding:1.5rem;">
                    لا يوجد حساب سانديك مسجل لهذه الإقامة حالياً.
                </div>
            @endforelse

            <hr style="border:0; border-top:1px solid var(--border-color); margin:1.25rem 0;">

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; font-size:0.85rem;">
                <div>
                    <span style="color:var(--text-muted); display:block; font-size:0.75rem;">رمز دعوة السكان (Invitation Code):</span>
                    <strong class="num" style="font-size:1.1rem; color:var(--brand-primary);">{{ $tenant->invitation_code ?? 'غير مفعل' }}</strong>
                </div>
                <div>
                    <span style="color:var(--text-muted); display:block; font-size:0.75rem;">الواجب الشهري المرجعي:</span>
                    <strong class="num">{{ number_format($tenant->default_monthly_fee ?? 0, 2) }} DH</strong>
                </div>
                <div>
                    <span style="color:var(--text-muted); display:block; font-size:0.75rem;">رقم الحساب البنكي (RIB):</span>
                    <code style="font-size:0.8rem;">{{ $tenant->bank_rib ?? 'غير مسجل' }}</code>
                </div>
                <div>
                    <span style="color:var(--text-muted); display:block; font-size:0.75rem;">نهاية الفترة التجريبية / الاشتراك:</span>
                    <strong>{{ $tenant->subscription_expires_at ? $tenant->subscription_expires_at->format('Y/m/d') : ($tenant->trial_ends_at ? $tenant->trial_ends_at->format('Y/m/d') : '—') }}</strong>
                </div>
            </div>
        </div>
    </div>

    {{-- Right: Recent Payments in this Residence --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">💳 آخر المقبوضات المسجلة</h3>
            <p class="card-subtitle">أحدث 10 مدفوعات تمت داخل هذه الإقامة</p>
        </div>
        <div class="card-body" style="padding:0;">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>الشقة</th>
                            <th>المبلغ</th>
                            <th>طريقة الدفع</th>
                            <th>التاريخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentPayments as $payment)
                            <tr>
                                <td>
                                    <strong>{{ $payment->apartment?->unit_number ? 'شقة ' . $payment->apartment->unit_number : '—' }}</strong>
                                    <div style="font-size:0.75rem; color:var(--text-muted);">{{ $payment->resident?->full_name }}</div>
                                </td>
                                <td class="num" style="font-weight:700; color:#10b981;">
                                    {{ number_format($payment->amount_paid, 2) }} DH
                                </td>
                                <td>
                                    <span class="badge badge-neutral" style="font-size:0.75rem;">{{ $payment->payment_method }}</span>
                                </td>
                                <td class="num" style="font-size:0.8rem;">
                                    {{ $payment->created_at ? $payment->created_at->format('Y/m/d') : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align:center; padding:2rem; color:var(--text-muted);">
                                    لا توجد مدفوعات مسجلة بعد في هذه الإقامة.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Full Width: Audit Trail for this Tenant --}}
<div class="card">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h3 class="card-title">🛡️ سجل التدقيق الخاص بهذه الإقامة</h3>
            <p class="card-subtitle">تتبع العمليات الحساسة التي أُجريت على الإقامة</p>
        </div>
        <a href="{{ route('superadmin.audit.index', ['tenant_id' => $tenant->id]) }}" class="btn btn-secondary btn-sm">
            عرض السجل الكامل للإقامة &larr;
        </a>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>المسؤول</th>
                    <th>نوع العملية</th>
                    <th>التفاصيل</th>
                    <th>عنوان IP</th>
                    <th>التاريخ والوقت</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentAuditLogs as $log)
                    <tr>
                        <td>
                            <strong>{{ $log->actor?->full_name ?? 'النظام الآلي' }}</strong>
                            <div style="font-size:0.75rem; color:var(--text-muted);">{{ $log->actor?->email }}</div>
                        </td>
                        <td>
                            <span class="badge badge-info" style="font-size:0.75rem; font-family:monospace;">{{ $log->action }}</span>
                        </td>
                        <td>
                            @if(is_array($log->metadata) && !empty($log->metadata))
                                <div style="display:flex; flex-wrap:wrap; gap:0.3rem;">
                                    @foreach(array_slice($log->metadata, 0, 3) as $k => $v)
                                        <span class="badge badge-neutral num" style="font-size:0.7rem;">
                                            {{ $k }}: {{ is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span style="color:var(--text-muted); font-size:0.8rem;">—</span>
                            @endif
                        </td>
                        <td class="num" style="font-size:0.8rem; color:var(--text-muted);">{{ $log->ip_address ?? '—' }}</td>
                        <td class="num" style="font-size:0.8rem;">{{ $log->created_at ? $log->created_at->format('Y/m/d H:i') : '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align:center; padding:2rem; color:var(--text-muted);">
                            لا توجد سجلات تدقيق مسجلة لهذه الإقامة.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
