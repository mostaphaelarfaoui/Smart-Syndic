@extends('layouts.superadmin')
@section('title', 'دليل المستخدمين الشامل')

@section('content')
<div class="page-header" style="margin-bottom:1.5rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
    <div>
        <h1 class="page-title">👥 دليل المستخدمين الشامل</h1>
        <p class="page-subtitle">البحث والتحكم في جميع مستخدمي المنصة — سانديك، ملاك، وسكان</p>
    </div>
    <div>
        <button type="button" class="btn btn-primary" onclick="document.getElementById('modal-create-superadmin').style.display='flex'" style="display:inline-flex; align-items:center; gap:0.4rem; font-weight:700;">
            <span>➕</span>
            <span>إضافة مشرف عام (سوبر أدمن) جديد</span>
        </button>
    </div>
</div>

{{-- Alerts --}}
@if(session('success'))
    <div class="alert alert-success" style="margin-bottom:1rem; padding:0.9rem 1.2rem; border-radius:10px; background:rgba(16,185,129,0.12); border:1px solid rgba(16,185,129,0.3); color:#10b981; display:flex; align-items:center; gap:0.6rem;">
        ✓ {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger" style="margin-bottom:1rem; padding:0.9rem 1.2rem; border-radius:10px; background:rgba(239,68,68,0.12); border:1px solid rgba(239,68,68,0.3); color:#ef4444; display:flex; align-items:center; gap:0.6rem;">
        ⚠ {{ session('error') }}
    </div>
@endif

{{-- KPI Row --}}
<div class="kpi-grid" style="grid-template-columns:repeat(4,1fr); margin-bottom:1.5rem;">
    <div class="kpi-card" style="border-top:3px solid #6366f1;">
        <div class="kpi-header">
            <span class="kpi-label">إجمالي المستخدمين</span>
            <span class="kpi-icon" style="background:rgba(99,102,241,0.12); color:#6366f1;">👥</span>
        </div>
        <div class="kpi-value">{{ $totalUsersCount }}</div>
    </div>
    <div class="kpi-card" style="border-top:3px solid #f59e0b;">
        <div class="kpi-header">
            <span class="kpi-label">السانديك والمسيرون</span>
            <span class="kpi-icon" style="background:rgba(245,158,11,0.12); color:#f59e0b;">👔</span>
        </div>
        <div class="kpi-value">{{ $totalSyndicsCount }}</div>
    </div>
    <div class="kpi-card" style="border-top:3px solid #3b82f6;">
        <div class="kpi-header">
            <span class="kpi-label">السكان والمقيمون</span>
            <span class="kpi-icon" style="background:rgba(59,130,246,0.12); color:#3b82f6;">🏠</span>
        </div>
        <div class="kpi-value">{{ $totalResidentsCount }}</div>
    </div>
    <div class="kpi-card" style="border-top:3px solid #10b981;">
        <div class="kpi-header">
            <span class="kpi-label">الحسابات النشطة</span>
            <span class="kpi-icon" style="background:rgba(16,185,129,0.12); color:#10b981;">✓</span>
        </div>
        <div class="kpi-value">{{ $activeUsersCount }}</div>
    </div>
</div>

{{-- Filters --}}
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-body" style="padding:1rem 1.5rem;">
        <form method="GET" action="{{ route('superadmin.users.index') }}" style="display:flex; flex-wrap:wrap; gap:0.75rem; align-items:flex-end;">
            <div style="flex:1; min-width:200px;">
                <label style="font-size:0.8rem; color:var(--text-muted); display:block; margin-bottom:0.3rem;">بحث بالاسم، الإيميل أو الهاتف</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="ابحث هنا..." class="form-input" style="width:100%;">
            </div>
            <div style="min-width:150px;">
                <label style="font-size:0.8rem; color:var(--text-muted); display:block; margin-bottom:0.3rem;">الدور</label>
                <select name="role" class="form-select">
                    <option value="">كل الأدوار</option>
                    <option value="SYNDIC" {{ request('role') === 'SYNDIC' ? 'selected' : '' }}>سانديك</option>
                    <option value="ADMIN_SYNDIC" {{ request('role') === 'ADMIN_SYNDIC' ? 'selected' : '' }}>مسير الإقامة</option>
                    <option value="RESIDENT" {{ request('role') === 'RESIDENT' ? 'selected' : '' }}>ساكن</option>
                </select>
            </div>
            <div style="min-width:150px;">
                <label style="font-size:0.8rem; color:var(--text-muted); display:block; margin-bottom:0.3rem;">الحالة</label>
                <select name="status" class="form-select">
                    <option value="">كل الحالات</option>
                    <option value="ACTIVE" {{ request('status') === 'ACTIVE' ? 'selected' : '' }}>نشط</option>
                    <option value="SUSPENDED" {{ request('status') === 'SUSPENDED' ? 'selected' : '' }}>موقوف</option>
                    <option value="BLOCKED" {{ request('status') === 'BLOCKED' ? 'selected' : '' }}>محظور أمنياً (15 محاولة)</option>
                    <option value="PENDING_APPROVAL" {{ request('status') === 'PENDING_APPROVAL' ? 'selected' : '' }}>في الانتظار</option>
                    <option value="REJECTED" {{ request('status') === 'REJECTED' ? 'selected' : '' }}>مرفوض</option>
                </select>
            </div>
            <div style="min-width:180px;">
                <label style="font-size:0.8rem; color:var(--text-muted); display:block; margin-bottom:0.3rem;">الإقامة</label>
                <select name="tenant_id" class="form-select">
                    <option value="">كل الإقامات</option>
                    @foreach($tenants as $tenant)
                        <option value="{{ $tenant->id }}" {{ request('tenant_id') === $tenant->id ? 'selected' : '' }}>{{ $tenant->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm" style="padding:0.65rem 1.2rem;">🔍 بحث</button>
            @if(request()->hasAny(['search','role','status','tenant_id']))
                <a href="{{ route('superadmin.users.index') }}" class="btn btn-secondary btn-sm" style="padding:0.65rem 1rem;">✕ إلغاء</a>
            @endif
        </form>
    </div>
</div>

{{-- Users Table --}}
<div class="card">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h3 class="card-title">قائمة المستخدمين</h3>
            <p class="card-subtitle">{{ $users->total() }} مستخدم — صفحة {{ $users->currentPage() }} من {{ $users->lastPage() }}</p>
        </div>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>المستخدم</th>
                        <th>الإقامة</th>
                        <th>الدور</th>
                        <th>الحالة</th>
                        <th>تاريخ التسجيل</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>
                                <div style="font-weight:700;">{{ $user->full_name }}</div>
                                <div style="font-size:0.78rem; color:var(--text-muted);">{{ $user->email }}</div>
                                <div style="font-size:0.78rem; color:var(--text-muted); font-family:monospace;">{{ $user->phone }}</div>
                            </td>
                            <td>
                                @if($user->tenant)
                                    <div style="font-size:0.85rem;">{{ $user->tenant->name }}</div>
                                    <div style="font-size:0.75rem; color:var(--text-muted);">{{ $user->tenant->city ?? '—' }}</div>
                                @else
                                    <span style="color:var(--text-muted);">—</span>
                                @endif
                            </td>
                            <td>
                                @if($user->role === 'SUPER_ADMIN')
                                    <span class="badge" style="background:rgba(99,102,241,0.15); color:#6366f1; font-size:0.75rem;">⭐ سوبر أدمن</span>
                                @elseif(in_array($user->role, ['SYNDIC','ADMIN_SYNDIC']))
                                    <span class="badge badge-warning" style="font-size:0.75rem;">👔 سانديك</span>
                                @else
                                    <span class="badge badge-info" style="font-size:0.75rem;">🏠 ساكن</span>
                                @endif
                            </td>
                            <td>
                                @if($user->status === 'ACTIVE')
                                    <span class="badge badge-success">نشط ✓</span>
                                @elseif($user->status === 'SUSPENDED')
                                    <span class="badge badge-danger">موقوف ⏸</span>
                                @elseif($user->status === 'BLOCKED')
                                    <span class="badge badge-danger" style="background:rgba(239,68,68,0.2); color:#ef4444; border:1px solid #ef4444;" title="{{ $user->blocked_reason ?? 'محظور أمنياً' }}">
                                        ⛔ محظور أمنياً ({{ $user->failed_login_attempts }} محاولة)
                                    </span>
                                @elseif($user->status === 'PENDING_APPROVAL')
                                    <span class="badge badge-warning">في الانتظار ⏳</span>
                                @else
                                    <span class="badge badge-danger">مرفوض ✕</span>
                                @endif
                            </td>
                            <td class="num" style="font-size:0.82rem; color:var(--text-muted);">
                                {{ $user->created_at->format('Y/m/d') }}
                            </td>
                            <td>
                                <div style="display:flex; gap:0.4rem; flex-wrap:wrap; align-items:center;">
                                    @if($user->role !== 'SUPER_ADMIN')
                                        {{-- If blocked due to 15 attempts, provide direct Unblock action --}}
                                        @if($user->status === 'BLOCKED')
                                            <form method="POST" action="{{ route('superadmin.users.unblock', $user->id) }}"
                                                  data-confirm="هل تؤكد فك الحظر الأمني عن حساب {{ $user->full_name }} وتصفير عداد المحاولات الفاشلة؟"
                                                  data-confirm-title="تأكيد فك الحظر الأمني"
                                                  data-confirm-type="success"
                                                  data-confirm-btn="فك الحظر وتفعيل الحساب">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success"
                                                    style="padding:0.25rem 0.6rem; font-size:0.75rem; font-weight:700;"
                                                    title="فك الحظر وتصفير عداد المحاولات الفاشلة">
                                                    🔓 فك الحظر
                                                </button>
                                            </form>
                                        @else
                                            {{-- Toggle status --}}
                                            <form method="POST" action="{{ route('superadmin.users.toggle-status', $user->id) }}"
                                                  data-confirm="{{ $user->status === 'ACTIVE' ? 'هل تريد توقيف حساب ' . $user->full_name . '؟ سيتم منعه من تسجيل الدخول فوراً.' : 'هل تريد تنشيط حساب ' . $user->full_name . ' وإعادته للعمل؟' }}"
                                                  data-confirm-title="{{ $user->status === 'ACTIVE' ? 'تأكيد توقيف الحساب' : 'تأكيد تنشيط الحساب' }}"
                                                  data-confirm-type="{{ $user->status === 'ACTIVE' ? 'warning' : 'success' }}"
                                                  data-confirm-btn="{{ $user->status === 'ACTIVE' ? 'توقيف الحساب' : 'تنشيط الحساب' }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm"
                                                    style="padding:0.25rem 0.6rem; font-size:0.75rem;
                                                    {{ $user->status === 'ACTIVE'
                                                        ? 'background:rgba(239,68,68,0.1); color:#ef4444; border:1px solid rgba(239,68,68,0.25);'
                                                        : 'background:rgba(16,185,129,0.1); color:#10b981; border:1px solid rgba(16,185,129,0.25);' }}"
                                                    title="{{ $user->status === 'ACTIVE' ? 'توقيف الحساب' : 'تنشيط الحساب' }}">
                                                    {{ $user->status === 'ACTIVE' ? '⏸ توقيف' : '▶ تنشيط' }}
                                                </button>
                                            </form>
                                        @endif

                                        {{-- Impersonate user --}}
                                        @if($user->status === 'ACTIVE')
                                            <form method="POST" action="{{ route('superadmin.users.impersonate', $user->id) }}"
                                                  data-confirm="هل تريد الدخول بصفتك {{ $user->full_name }} للدعم الفني؟ سيتم تسجيل الجلسة في سجل الرقابة."
                                                  data-confirm-title="تقمص الحساب للدعم الفني"
                                                  data-confirm-type="info"
                                                  data-confirm-btn="دخول كمسير">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-secondary"
                                                    style="padding:0.25rem 0.5rem; font-size:0.75rem; color:#6366f1; border-color:rgba(99,102,241,0.3);"
                                                    title="تقمص الحساب للدعم الفني">
                                                    👤
                                                </button>
                                            </form>
                                        @endif

                                        {{-- Reset password modal trigger --}}
                                        <button type="button" class="btn btn-sm btn-secondary"
                                            style="padding:0.25rem 0.5rem; font-size:0.75rem;"
                                            title="تغيير كلمة المرور"
                                            onclick="document.getElementById('modal-reset-{{ $user->id }}').style.display='flex'">
                                            🔑
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        {{-- Password Reset Modal --}}
                        <div id="modal-reset-{{ $user->id }}" style="display:none;" class="modal-backdrop">
                            <div class="modal-dialog" style="max-width:440px;">
                                <div class="modal-header">
                                    <h3 class="modal-title">🔑 تغيير كلمة المرور</h3>
                                    <button type="button" class="modal-close" onclick="document.getElementById('modal-reset-{{ $user->id }}').style.display='none'" title="إغلاق">
                                        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                <form method="POST" action="{{ route('superadmin.users.reset-password', $user->id) }}">
                                    @csrf
                                    <div class="modal-body" style="padding:1.5rem;">
                                        <p style="color:var(--text-muted); font-size:0.875rem; margin-bottom:1.25rem;">
                                            أدخل كلمة المرور الحالية وكلمة المرور الجديدة للمستخدم: <strong>{{ $user->full_name }}</strong>
                                        </p>
                                        <div style="margin-bottom:1rem;">
                                            <label class="form-label" style="font-size:0.85rem; margin-bottom:0.4rem;">كلمة المرور الحالية</label>
                                            <input type="password" name="current_password" required
                                                class="form-input" placeholder="••••••••">
                                        </div>
                                        <div>
                                            <label class="form-label" style="font-size:0.85rem; margin-bottom:0.4rem;">كلمة المرور الجديدة (6 أحرف على الأقل)</label>
                                            <input type="password" name="new_password" required minlength="6"
                                                class="form-input" placeholder="••••••••">
                                        </div>
                                    </div>
                                    <div class="modal-footer" style="padding:1rem 1.5rem;">
                                        <button type="button" class="btn btn-secondary"
                                            onclick="document.getElementById('modal-reset-{{ $user->id }}').style.display='none'">
                                            إلغاء
                                        </button>
                                        <button type="submit" class="btn btn-primary" style="padding:0.6rem 1.25rem;">حفظ كلمة المرور</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center; padding:3rem; color:var(--text-muted);">
                                لا يوجد مستخدمون مطابقون لمعايير البحث.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($users->hasPages())
        <div style="padding:1rem 1.5rem; border-top:1px solid var(--border-color);">
            {{ $users->links() }}
        </div>
    @endif
</div>

{{-- Modal Create Super Admin (Priority 7) --}}
<div id="modal-create-superadmin" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.65); backdrop-filter:blur(4px); z-index:99999; align-items:center; justify-content:center; padding:1.5rem;">
    <div class="card" style="width:100%; max-width:540px; box-shadow:0 20px 40px rgba(0,0,0,0.4); max-height:90vh; overflow-y:auto;">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border-color); padding:1.25rem 1.5rem;">
            <div>
                <h3 class="card-title" style="margin:0; display:flex; align-items:center; gap:0.5rem;">
                    <span>🛡️</span>
                    <span>إنشاء حساب مشرف عام (سوبر أدمن) جديد</span>
                </h3>
                <p class="card-subtitle" style="margin-top:0.25rem;">يمنح هذا الحساب صلاحيات تحكم كاملة في المنصة والخوادم</p>
            </div>
            <button type="button" class="icon-btn" onclick="document.getElementById('modal-create-superadmin').style.display='none'" style="border:none; background:transparent; font-size:1.25rem; color:var(--text-muted); cursor:pointer;">
                ✕
            </button>
        </div>

        <form method="POST" action="{{ route('superadmin.users.create-superadmin') }}" style="padding:1.5rem;">
            @csrf

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">
                <div class="form-group" style="margin:0;">
                    <label class="form-label" style="font-size:0.85rem; margin-bottom:0.35rem;">الاسم الشخصي *</label>
                    <input type="text" name="first_name" required class="form-input" placeholder="محمد">
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label" style="font-size:0.85rem; margin-bottom:0.35rem;">الاسم العائلي *</label>
                    <input type="text" name="last_name" required class="form-input" placeholder="العلوي">
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">
                <div class="form-group" style="margin:0;">
                    <label class="form-label" style="font-size:0.85rem; margin-bottom:0.35rem;">البريد الإلكتروني *</label>
                    <input type="email" name="email" required class="form-input" placeholder="admin@smartsyndic.ma">
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label" style="font-size:0.85rem; margin-bottom:0.35rem;">رقم الهاتف</label>
                    <input type="tel" name="phone" class="form-input" placeholder="+212 600 000000">
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1.25rem;">
                <div class="form-group" style="margin:0;">
                    <label class="form-label" style="font-size:0.85rem; margin-bottom:0.35rem;">كلمة المرور * (8 أحرف+)</label>
                    <input type="password" name="password" required minlength="8" class="form-input">
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label" style="font-size:0.85rem; margin-bottom:0.35rem;">تأكيد كلمة المرور *</label>
                    <input type="password" name="password_confirmation" required minlength="8" class="form-input">
                </div>
            </div>

            <div style="background:rgba(239, 68, 68, 0.08); border:1px solid rgba(239,68,68,0.25); border-radius:var(--radius-md); padding:1rem; margin-bottom:1.5rem;">
                <label class="form-label" style="color:#ef4444; font-weight:700; margin-bottom:0.35rem; font-size:0.85rem;">
                    🔒 تأكيد أمني: كلمة مرورك الحالية (السوبر أدمن الحالي) *
                </label>
                <input type="password" name="current_admin_password" required class="form-input" placeholder="أدخل كلمة مرورك الحالية لتفويض العملية" style="width:100%; border-color:rgba(239,68,68,0.4);">
                <div style="font-size:0.75rem; color:var(--text-muted); margin-top:0.35rem;">
                    إجراء إلزامي لحماية أمان المنصة ومنع إنشاء حسابات إدارية دون تفويض.
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:0.75rem;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-create-superadmin').style.display='none'">
                    إلغاء
                </button>
                <button type="submit" class="btn btn-primary" style="padding:0.6rem 1.5rem; font-weight:700;">
                    ✓ إنشاء وتفعيل السوبر أدمن
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
