@extends('layouts.superadmin')

@section('title', 'إدارة الإقامات والمنظمات')

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">🏢 إدارة الإقامات والمنظمات</h1>
        <p class="page-subtitle">تفعيل الإقامات الجديدة، مراجعة بيانات المسيرين، وإدارة اشتراكات المنصة</p>
    </div>
</div>

{{-- Filters Card --}}
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-body" style="padding:1.25rem;">
        <form method="GET" action="{{ route('superadmin.tenants.index') }}" style="display:flex; align-items:center; gap:1rem; flex-wrap:wrap;">
            <div class="search-box" style="flex:1;">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="ابحث باسم الإقامة...">
                <span class="search-icon">🔍</span>
            </div>

            <select name="status" class="form-select" style="width:auto; min-width:180px;">
                <option value="">جميع الحالات</option>
                <option value="ACTIVE" {{ request('status') == 'ACTIVE' ? 'selected' : '' }}>مفعلة</option>
                <option value="PENDING_APPROVAL" {{ request('status') == 'PENDING_APPROVAL' ? 'selected' : '' }}>في انتظار التفعيل</option>
                <option value="SUSPENDED" {{ request('status') == 'SUSPENDED' ? 'selected' : '' }}>موقوفة</option>
                <option value="REJECTED" {{ request('status') == 'REJECTED' ? 'selected' : '' }}>مرفوضة</option>
            </select>

            <button type="submit" class="btn btn-primary btn-sm" style="padding:0.75rem 1.25rem;">
                تطبيق الفلترة
            </button>

            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('superadmin.tenants.index') }}" class="btn btn-secondary btn-sm" style="padding:0.75rem 1.25rem;">
                    إلغاء الفلاتر
                </a>
            @endif
        </form>
    </div>
</div>

{{-- Tenants Table Card --}}
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="min-width:170px;">اسم الإقامة</th>
                    <th style="min-width:120px;">نوع العقار</th>
                    <th style="min-width:110px;">المدينة</th>
                    <th style="text-align:center; min-width:120px;">كود الدعوة</th>
                    <th style="text-align:center; min-width:120px;">حالة الاشتراك</th>
                    <th style="min-width:110px;">تاريخ التسجيل</th>
                    <th style="min-width:140px;">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tenants as $tenant)
                    <tr>
                        <td>
                            <strong style="font-size:0.95rem;">{{ $tenant->name }}</strong>
                        </td>
                        <td>
                            <span class="badge badge-neutral">
                                {{ match($tenant->property_type) {
                                    'SINGLE_BUILDING' => 'عمارة منفردة',
                                    'RESIDENTIAL_COMPLEX' => 'مجمع سكني',
                                    'HOUSING_ESTATE_VILLAS' => 'فيلات',
                                    default => 'إقامة'
                                } }}
                            </span>
                        </td>
                        <td>{{ $tenant->city ?? 'غير محددة' }}</td>
                        <td>
                            <code class="num" style="font-weight:800; color:var(--brand-primary);">{{ $tenant->invitation_code }}</code>
                        </td>
                        <td>
                            @if($tenant->status === 'ACTIVE')
                                <span class="badge badge-success">✓ مفعلة</span>
                            @elseif($tenant->status === 'PENDING_APPROVAL')
                                <span class="badge badge-warning">⏳ في الانتظار</span>
                            @elseif($tenant->status === 'REJECTED')
                                <span class="badge badge-danger">✕ مرفوضة</span>
                            @else
                                <span class="badge badge-danger">⏸ موقوفة</span>
                            @endif
                        </td>
                        <td class="num" style="color:var(--text-muted); font-size:0.85rem;">
                            {{ $tenant->created_at->format('Y/m/d') }}
                        </td>
                        <td>
                            <div style="display:flex; align-items:center; gap:0.4rem; flex-wrap:wrap;">
                                @if($tenant->status === 'PENDING_APPROVAL')
                                    <form method="POST" action="{{ route('superadmin.tenants.approve', $tenant->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success" title="قبول وتفعيل الإقامة">
                                            تفعيل ✓
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('superadmin.tenants.reject', $tenant->id) }}"
                                          data-confirm="هل تؤكد رفض تسجيل سانديك وإقامة {{ $tenant->name }}؟ سيتم تعطيل كافة الحسابات المرتبطة بها."
                                          data-confirm-title="تأكيد رفض الإقامة"
                                          data-confirm-type="danger"
                                          data-confirm-btn="رفض التسجيل">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-secondary" style="color:var(--color-danger); border-color:rgba(244,63,94,0.3);" title="رفض طلب التسجيل">
                                            رفض ✕
                                        </button>
                                    </form>
                                @elseif($tenant->status === 'ACTIVE')
                                    <form method="POST" action="{{ route('superadmin.tenants.impersonate', $tenant->id) }}"
                                          data-confirm="هل تريد الدخول كمسير لإقامة {{ $tenant->name }} للدعم الفني؟ سيتم تسجيل الجلسة في سجل الرقابة."
                                          data-confirm-title="الدخول كمسير للإقامة"
                                          data-confirm-type="info"
                                          data-confirm-btn="دخول كمسير">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-secondary" style="color:var(--brand-primary); border-color:rgba(99,102,241,0.3);" title="الدخول كمسير للإقامة (تقمص للدعم الفني)">
                                            👤 دخول كمسير
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('superadmin.tenants.suspend', $tenant->id) }}"
                                          data-confirm="تحذير: هل تؤكد توقيف إقامة {{ $tenant->name }}؟ سيتم إيقاف كافة حسابات السانديك والسكان التابعة لها عن العمل فوراً."
                                          data-confirm-title="تأكيد توقيف الإقامة"
                                          data-confirm-type="warning"
                                          data-confirm-btn="توقيف الإقامة">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-secondary" style="color:var(--color-warning);" title="توقيف الإقامة والسكان">
                                            توقيف ⏸
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('superadmin.tenants.approve', $tenant->id) }}"
                                          data-confirm="هل تريد إعادة تفعيل إقامة {{ $tenant->name }} وكافة حسابات السانديك والسكان التابعة لها؟"
                                          data-confirm-title="تأكيد إعادة التفعيل"
                                          data-confirm-type="success"
                                          data-confirm-btn="إعادة التفعيل">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success" title="إعادة التفعيل لكافة الحسابات">
                                            تفعيل ✓
                                        </button>
                                    </form>
                                @endif

                                <form method="POST" action="{{ route('superadmin.tenants.destroy', $tenant->id) }}"
                                      data-confirm="تحذير شديد: هل أنت متأكد من حذف إقامة {{ $tenant->name }} نهائياً؟ سيتم حذف جميع حسابات السانديك والسكان التابعة لها، وكافة الشقق والدفعات والمصاريف بدون إمكانية استرجاع."
                                      data-confirm-title="حذف الإقامة نهائياً"
                                      data-confirm-type="danger"
                                      data-confirm-password="true"
                                      data-confirm-btn="حذف نهائي لا رجعة فيه">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" style="padding:0.4rem 0.6rem;" title="حذف نهائي للإقامة وكافة حساباتها">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center; padding:3rem; color:var(--text-muted);">
                            لا توجد إقامات مطابقة للمعايير.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($tenants->hasPages())
        <div class="card-footer">
            {{ $tenants->links() }}
        </div>
    @endif
</div>

@endsection
