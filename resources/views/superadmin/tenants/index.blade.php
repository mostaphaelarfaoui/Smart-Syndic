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
                    <th>اسم الإقامة</th>
                    <th>نوع العقار</th>
                    <th>المدينة</th>
                    <th>كود الدعوة</th>
                    <th>حالة الاشتراك</th>
                    <th>تاريخ التسجيل</th>
                    <th>الإجراءات</th>
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
                            @else
                                <span class="badge badge-danger">موقوفة</span>
                            @endif
                        </td>
                        <td class="num" style="color:var(--text-muted); font-size:0.85rem;">
                            {{ $tenant->created_at->format('Y/m/d') }}
                        </td>
                        <td>
                            <div style="display:flex; align-items:center; gap:0.5rem;">
                                @if($tenant->status === 'PENDING_APPROVAL')
                                    <form method="POST" action="{{ route('superadmin.tenants.approve', $tenant->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">
                                            تفعيل ✓
                                        </button>
                                    </form>
                                @elseif($tenant->status === 'ACTIVE')
                                    <form method="POST" action="{{ route('superadmin.tenants.suspend', $tenant->id) }}" onsubmit="return confirm('هل تؤكد توقيف هذه الإقامة؟');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-secondary" style="color:var(--color-danger);">
                                            توقيف
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('superadmin.tenants.approve', $tenant->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">
                                            إعادة التفعيل
                                        </button>
                                    </form>
                                @endif
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
