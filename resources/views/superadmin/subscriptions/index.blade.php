@extends('layouts.superadmin')
@section('title', 'باقات واشتراكات الـ SaaS')

@section('content')
<div x-data="{ 
    editModalOpen: false, 
    extendModalOpen: false,
    selectedTenant: null,
    openEdit(tenant) {
        this.selectedTenant = tenant;
        this.editModalOpen = true;
    },
    openExtend(tenant) {
        this.selectedTenant = tenant;
        this.extendModalOpen = true;
    }
}">
    <div class="page-header" style="margin-bottom:1.5rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
        <div>
            <h1 class="page-title">💳 باقات واشتراكات الـ SaaS والفوترة</h1>
            <p class="page-subtitle">إدارة خطط اشتراك الإقامات، متابعة تواريخ التجديد والانتهاء، وتمديد الفترات التجريبية.</p>
        </div>
        <div style="display:flex; gap:0.5rem;">
            <a href="{{ route('superadmin.tenants.index') }}" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:0.4rem;">
                <span>🏢 دليل الإقامات</span>
            </a>
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div style="background:rgba(16, 185, 129, 0.15); border:1px solid #10b981; color:#10b981; padding:0.85rem 1.25rem; border-radius:10px; margin-bottom:1.5rem; font-weight:600; display:flex; align-items:center; gap:0.6rem;">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div style="background:rgba(239, 68, 68, 0.15); border:1px solid #ef4444; color:#ef4444; padding:0.85rem 1.25rem; border-radius:10px; margin-bottom:1.5rem; font-weight:600; display:flex; align-items:center; gap:0.6rem;">
            <span>⚠️</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- SaaS Metrics Cards --}}
    <div class="kpi-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom:1.75rem;">
        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-title">إجمالي الاشتراكات</span>
                <span class="kpi-icon" style="background:rgba(99, 102, 241, 0.15); color:#6366f1;">🏢</span>
            </div>
            <div class="kpi-value">{{ number_format($stats['totalTenants']) }}</div>
            <div class="kpi-subtitle">منظمات مسجلة بالمنصة</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-title">باقات احترافية (PRO)</span>
                <span class="kpi-icon" style="background:rgba(168, 85, 247, 0.15); color:#a855f7;">⭐</span>
            </div>
            <div class="kpi-value" style="color:#a855f7;">{{ number_format($stats['proTenants']) }}</div>
            <div class="kpi-subtitle">299 درهم / شهرياً لكل إقامة</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-title">باقات ENTERPRISE</span>
                <span class="kpi-icon" style="background:rgba(245, 158, 11, 0.15); color:#f59e0b;">👑</span>
            </div>
            <div class="kpi-value" style="color:#f59e0b;">{{ number_format($stats['enterpriseTenants']) }}</div>
            <div class="kpi-subtitle">799 درهم / شهرياً للمجمعات الكبرى</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-title">فترات تجريبية (TRIAL)</span>
                <span class="kpi-icon" style="background:rgba(59, 130, 246, 0.15); color:#3b82f6;">⏳</span>
            </div>
            <div class="kpi-value" style="color:#3b82f6;">{{ number_format($stats['trialTenants']) }}</div>
            <div class="kpi-subtitle">تحت التجربة المجانية</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-title">تنتهي خلال 14 يوماً</span>
                <span class="kpi-icon" style="background:rgba(239, 68, 68, 0.15); color:#ef4444;">⚠️</span>
            </div>
            <div class="kpi-value" style="color:{{ $stats['expiringSoonCount'] > 0 ? '#ef4444' : 'var(--text-main)' }};">{{ number_format($stats['expiringSoonCount']) }}</div>
            <div class="kpi-subtitle">تحتاج تجديد أو تمديد فوري</div>
        </div>

        <div class="kpi-card" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(6, 95, 70, 0.15)); border:1px solid rgba(16, 185, 129, 0.3);">
            <div class="kpi-header">
                <span class="kpi-title" style="color:#10b981;">تقدير الـ MRR الشهري</span>
                <span class="kpi-icon" style="background:rgba(16, 185, 129, 0.2); color:#10b981;">💰</span>
            </div>
            <div class="kpi-value" style="color:#10b981; font-weight:800;">{{ number_format($stats['estimatedMRR'], 2) }} <span style="font-size:0.8rem; font-weight:normal;">درهم</span></div>
            <div class="kpi-subtitle" style="color:var(--text-muted);">الدخل الشهري التقديري المتكرر</div>
        </div>
    </div>

    {{-- Filters Card --}}
    <div class="card" style="margin-bottom:1.5rem; padding:1.25rem;">
        <form method="GET" action="{{ route('superadmin.subscriptions.index') }}" style="display:flex; gap:1rem; flex-wrap:wrap; align-items:flex-end;">
            <div style="flex:1; min-width:220px;">
                <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:0.4rem; color:var(--text-muted);">بحث باسم الإقامة</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="اكتب اسم الإقامة..." class="form-input">
            </div>

            <div style="flex:1; min-width:160px;">
                <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:0.4rem; color:var(--text-muted);">نوع الباقة</label>
                <select name="plan_type" class="form-input">
                    <option value="">جميع الباقات</option>
                    <option value="TRIAL" {{ request('plan_type') === 'TRIAL' ? 'selected' : '' }}>⏳ فترة تجريبية (TRIAL)</option>
                    <option value="PRO" {{ request('plan_type') === 'PRO' ? 'selected' : '' }}>⭐ باقة احترافية (PRO)</option>
                    <option value="ENTERPRISE" {{ request('plan_type') === 'ENTERPRISE' ? 'selected' : '' }}>👑 باقة متقدمة (ENTERPRISE)</option>
                </select>
            </div>

            <div style="flex:1; min-width:160px;">
                <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:0.4rem; color:var(--text-muted);">حالة الإقامة</label>
                <select name="status" class="form-input">
                    <option value="">كافة الحالات</option>
                    <option value="ACTIVE" {{ request('status') === 'ACTIVE' ? 'selected' : '' }}>نشطة (ACTIVE)</option>
                    <option value="PENDING_APPROVAL" {{ request('status') === 'PENDING_APPROVAL' ? 'selected' : '' }}>في انتظار الموافقة</option>
                    <option value="SUSPENDED" {{ request('status') === 'SUSPENDED' ? 'selected' : '' }}>موقوفة (SUSPENDED)</option>
                    <option value="EXPIRED" {{ request('status') === 'EXPIRED' ? 'selected' : '' }}>منتهية (EXPIRED)</option>
                </select>
            </div>

            <div style="display:flex; align-items:center; margin-bottom:0.5rem;">
                <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer; font-size:0.85rem; font-weight:600;">
                    <input type="checkbox" name="expiring_soon" value="1" {{ request('expiring_soon') ? 'checked' : '' }} style="width:16px; height:16px; accent-color:#ef4444;">
                    <span style="color:#ef4444;">تنتهي خلال 14 يوماً فقط</span>
                </label>
            </div>

            <div>
                <button type="submit" class="btn btn-primary" style="padding:0.6rem 1.25rem;">تصفية</button>
                @if(request()->hasAny(['search', 'plan_type', 'status', 'expiring_soon']))
                    <a href="{{ route('superadmin.subscriptions.index') }}" class="btn btn-secondary" style="padding:0.6rem 1rem;">إلغاء</a>
                @endif
            </div>
        </form>
    </div>

    {{-- Subscriptions Table --}}
    <div class="card" style="padding:0; overflow:hidden;">
        <div style="padding:1.25rem 1.5rem; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0; font-size:1.05rem; font-weight:700;">دليل اشتراكات المنظمات</h3>
            <span class="badge badge-neutral">{{ $tenants->total() }} إقامة</span>
        </div>

        <div class="table-responsive">
            <table class="data-table" style="width:100%;">
                <thead>
                    <tr>
                        <th>الإقامة والنوع</th>
                        <th>الباقة الحالية</th>
                        <th>الحالة</th>
                        <th>سعة الشقق والمستخدمين</th>
                        <th>تاريخ الانتهاء والمدة المتبقية</th>
                        <th style="text-align:left;">إجراءات سريعة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tenants as $t)
                        @php
                            $targetDate = $t->plan_type === 'TRIAL' ? $t->trial_ends_at : $t->subscription_expires_at;
                            $daysLeft = $targetDate ? now()->diffInDays(\Carbon\Carbon::parse($targetDate), false) : null;
                        @endphp
                        <tr>
                            <td>
                                <strong style="color:var(--text-main); font-size:0.95rem;">{{ $t->name }}</strong>
                                <div style="font-size:0.75rem; color:var(--text-muted); margin-top:0.2rem;">
                                    كود الدعوة: <code style="background:rgba(99,102,241,0.1); color:#6366f1; padding:0.1rem 0.3rem; border-radius:4px;">{{ $t->invitation_code }}</code>
                                    • مسجل منذ: {{ $t->created_at->format('Y-m-d') }}
                                </div>
                            </td>
                            <td>
                                @if($t->plan_type === 'ENTERPRISE')
                                    <span class="badge" style="background:rgba(245, 158, 11, 0.2); color:#f59e0b; border:1px solid #f59e0b; font-weight:700;">👑 ENTERPRISE</span>
                                @elseif($t->plan_type === 'PRO')
                                    <span class="badge" style="background:rgba(168, 85, 247, 0.2); color:#a855f7; border:1px solid #a855f7; font-weight:700;">⭐ PRO</span>
                                @else
                                    <span class="badge" style="background:rgba(59, 130, 246, 0.15); color:#3b82f6; border:1px solid #3b82f6;">⏳ TRIAL</span>
                                @endif
                            </td>
                            <td>
                                @if($t->status === 'ACTIVE')
                                    <span class="badge badge-success">نشطة</span>
                                @elseif($t->status === 'PENDING_APPROVAL')
                                    <span class="badge badge-warning">في انتظار الموافقة</span>
                                @elseif($t->status === 'SUSPENDED')
                                    <span class="badge badge-danger">موقوفة</span>
                                @elseif($t->status === 'EXPIRED')
                                    <span class="badge badge-neutral" style="color:#ef4444;">منتهية</span>
                                @else
                                    <span class="badge badge-neutral">{{ $t->status }}</span>
                                @endif
                            </td>
                            <td>
                                <div style="font-size:0.875rem;">
                                    <span>🏢 <strong>{{ $t->apartments_count }}</strong> شقة</span>
                                    <span style="color:var(--text-muted); margin:0 0.3rem;">|</span>
                                    <span>👥 <strong>{{ $t->users_count }}</strong> مستخدم</span>
                                </div>
                            </td>
                            <td>
                                @if($targetDate)
                                    <div style="font-size:0.85rem; font-weight:600;">
                                        {{ \Carbon\Carbon::parse($targetDate)->format('Y-m-d') }}
                                    </div>
                                    @if($daysLeft !== null)
                                        @if($daysLeft > 14)
                                            <span style="font-size:0.75rem; color:#10b981;">(متبقي {{ round($daysLeft) }} يوم)</span>
                                        @elseif($daysLeft >= 0)
                                            <span style="font-size:0.75rem; color:#f59e0b; font-weight:700;">(⚠️ ينتهي خلال {{ round($daysLeft) }} يوم)</span>
                                        @else
                                            <span style="font-size:0.75rem; color:#ef4444; font-weight:700;">(🚨 انتهى منذ {{ abs(round($daysLeft)) }} يوم)</span>
                                        @endif
                                    @endif
                                @else
                                    <span style="font-size:0.8rem; color:var(--text-muted);">غير محدد (غير مقيد)</span>
                                @endif
                            </td>
                            <td style="text-align:left;">
                                <div style="display:flex; align-items:center; justify-content:flex-end; gap:0.5rem;">
                                    <button type="button" @click="openExtend({{ json_encode($t) }})" class="btn btn-sm btn-success" title="تمديد الاشتراك السريع">
                                        ➕ تمديد
                                    </button>
                                    <button type="button" @click="openEdit({{ json_encode($t) }})" class="btn btn-sm btn-secondary" title="تعديل الباقة والتواريخ">
                                        ⚙️ تعديل
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center; padding:3rem; color:var(--text-muted);">
                                <div style="font-size:2.5rem; margin-bottom:0.5rem;">💳</div>
                                <div style="font-weight:600; font-size:1.05rem;">لا توجد اشتراكات تطابق معايير البحث</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tenants->hasPages())
            <div style="padding:1rem 1.5rem; border-top:1px solid var(--border-color);">
                {{ $tenants->links() }}
            </div>
        @endif
    </div>

    {{-- Edit Subscription Modal --}}
    <div x-show="editModalOpen" style="display:none;" class="modal-backdrop" @keydown.escape.window="editModalOpen = false">
        <div class="modal-dialog" style="max-width:550px;" @click.away="editModalOpen = false">
            <div class="modal-header">
                <h3 class="modal-title">⚙️ تعديل باقة واشتراك الإقامة</h3>
                <button type="button" class="modal-close" @click="editModalOpen = false" title="إغلاق">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form :action="'{{ url('platform/subscriptions') }}/' + (selectedTenant ? selectedTenant.id : '') + '/update'" method="POST">
                @csrf
                <div class="modal-body" style="padding:1.5rem; display:flex; flex-direction:column; gap:1.2rem;">
                    <div>
                        <div style="font-size:0.85rem; color:var(--text-muted); margin-bottom:0.25rem;">اسم الإقامة:</div>
                        <strong style="font-size:1.1rem; color:var(--text-main);" x-text="selectedTenant ? selectedTenant.name : ''"></strong>
                    </div>

                    <div>
                        <label class="form-label" style="font-weight:600; margin-bottom:0.4rem;">نوع باقة الاشتراك *</label>
                        <select name="plan_type" :value="selectedTenant ? selectedTenant.plan_type : 'TRIAL'" class="form-input" required>
                            <option value="TRIAL">⏳ TRIAL — فترة تجريبية مجانية</option>
                            <option value="PRO">⭐ PRO — باقة احترافية (299 درهم/شهر)</option>
                            <option value="ENTERPRISE">👑 ENTERPRISE — باقة متميزة شاملة (799 درهم/شهر)</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label" style="font-weight:600; margin-bottom:0.4rem;">حالة الحساب *</label>
                        <select name="status" :value="selectedTenant ? selectedTenant.status : 'ACTIVE'" class="form-input" required>
                            <option value="ACTIVE">نشطة (ACTIVE)</option>
                            <option value="PENDING_APPROVAL">في انتظار الموافقة (PENDING)</option>
                            <option value="SUSPENDED">موقوفة (SUSPENDED)</option>
                            <option value="EXPIRED">منتهية (EXPIRED)</option>
                        </select>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                        <div>
                            <label class="form-label" style="font-size:0.8rem; font-weight:600; margin-bottom:0.4rem;">تاريخ انتهاء التجربة (Trial)</label>
                            <input type="date" name="trial_ends_at" :value="selectedTenant && selectedTenant.trial_ends_at ? selectedTenant.trial_ends_at.substring(0, 10) : ''" class="form-input">
                        </div>
                        <div>
                            <label class="form-label" style="font-size:0.8rem; font-weight:600; margin-bottom:0.4rem;">تاريخ انتهاء الاشتراك (Sub)</label>
                            <input type="date" name="subscription_expires_at" :value="selectedTenant && selectedTenant.subscription_expires_at ? selectedTenant.subscription_expires_at.substring(0, 10) : ''" class="form-input">
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="padding:1rem 1.5rem; display:flex; justify-content:flex-end; gap:0.75rem; border-top:1px solid var(--border-color);">
                    <button type="button" @click="editModalOpen = false" class="btn btn-secondary">إلغاء</button>
                    <button type="submit" class="btn btn-primary" style="padding:0.6rem 1.5rem;">حفظ التغييرات</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Quick Extend Subscription Modal --}}
    <div x-show="extendModalOpen" style="display:none;" class="modal-backdrop" @keydown.escape.window="extendModalOpen = false">
        <div class="modal-dialog" style="max-width:500px;" @click.away="extendModalOpen = false">
            <div class="modal-header">
                <h3 class="modal-title">➕ تمديد اشتراك الإقامة السريع</h3>
                <button type="button" class="modal-close" @click="extendModalOpen = false" title="إغلاق">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form :action="'{{ url('platform/subscriptions') }}/' + (selectedTenant ? selectedTenant.id : '') + '/extend'" method="POST">
                @csrf
                <div class="modal-body" style="padding:1.5rem; display:flex; flex-direction:column; gap:1.2rem;">
                    <div>
                        <div style="font-size:0.85rem; color:var(--text-muted); margin-bottom:0.25rem;">اسم الإقامة:</div>
                        <strong style="font-size:1.1rem; color:var(--text-main);" x-text="selectedTenant ? selectedTenant.name : ''"></strong>
                    </div>

                    <div>
                        <label class="form-label" style="font-weight:600; margin-bottom:0.4rem;">حدد مدة التمديد الإضافية *</label>
                        <select name="days" class="form-input" required>
                            <option value="30">➕ شهر إضافي (30 يوماً)</option>
                            <option value="90">➕ 3 أشهر إضافية (90 يوماً)</option>
                            <option value="180">➕ 6 أشهر إضافية (180 يوماً)</option>
                            <option value="365">➕ سنة كاملة (365 يوماً)</option>
                        </select>
                        <p style="font-size:0.75rem; color:var(--text-muted); margin-top:0.4rem;">
                            سيتم إضافة هذه المدة مباشرة إلى تاريخ الانتهاء الحالي (أو بدءاً من اليوم إذا كان الاشتراك منتهياً).
                        </p>
                    </div>
                </div>
                <div class="modal-footer" style="padding:1rem 1.5rem; display:flex; justify-content:flex-end; gap:0.75rem; border-top:1px solid var(--border-color);">
                    <button type="button" @click="extendModalOpen = false" class="btn btn-secondary">إلغاء</button>
                    <button type="submit" class="btn btn-success" style="padding:0.6rem 1.5rem;">تأكيد التمديد الفوري</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
