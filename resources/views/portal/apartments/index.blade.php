@extends('layouts.dashboard')

@section('title', 'الشقق والوحدات السكنية')

@section('content')

<div class="page-header" x-data="{ addModal: false, bulkModal: false }">
    <div>
        <h1 class="page-title">إدارة الشقق والوحدات السكنية</h1>
        <p class="page-subtitle">متابعة تفاصيل الشقق، الملاك المقيمين، الشقق الشاغرة وخيارات الأداء</p>
    </div>

    <div class="page-actions">
        <button type="button" class="btn btn-secondary" @click="bulkModal = true">
            <span>⚡ توليد جماعي للشقق</span>
        </button>
        <button type="button" class="btn btn-primary" @click="addModal = true">
            <span>+ إضافة شقة جديدة</span>
        </button>
    </div>

    {{-- Modal: Add Single Apartment --}}
    <div class="modal-overlay" x-show="addModal" x-cloak>
        <div class="modal-dialog" @click.away="addModal = false">
            <div class="modal-header">
                <h3 class="modal-title">إضافة شقة جديدة</h3>
                <button type="button" class="icon-btn" @click="addModal = false">✕</button>
            </div>
            <form method="POST" action="{{ route('portal.apartments.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">العمارة / البلوك</label>
                        <select name="building_id" class="form-select" required>
                            @foreach($buildings as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                        <div class="form-group">
                            <label class="form-label">رقم الشقة</label>
                            <input type="text" name="unit_number" class="form-control" placeholder="مثال: 12" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">رقم الطابق</label>
                            <input type="number" name="floor_number" class="form-control" placeholder="0 = أرضي" value="1">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">الواجب الشهري (درهم)</label>
                        <input type="number" step="0.01" name="monthly_fee" class="form-control" value="200" required>
                    </div>

                    <div class="form-group">
                        <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer;">
                            <input type="checkbox" name="is_billable" value="1" checked style="accent-color:var(--brand-primary);">
                            <span style="font-weight:700;">الشقة خاضعة لواجبات السانديك (Billable)</span>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="addModal = false">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ الشقة</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: Bulk Generate Apartments --}}
    <div class="modal-overlay" x-show="bulkModal" x-cloak>
        <div class="modal-dialog" @click.away="bulkModal = false">
            <div class="modal-header">
                <h3 class="modal-title">توليد شقق العمارة بشكل جماعي</h3>
                <button type="button" class="icon-btn" @click="bulkModal = false">✕</button>
            </div>
            <form method="POST" action="{{ route('portal.apartments.bulk-generate') }}">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">العمارة المستهدفة</label>
                        <select name="building_id" class="form-select" required>
                            @foreach($buildings as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                        <div class="form-group">
                            <label class="form-label">عدد الطوابق</label>
                            <input type="number" name="floors_count" class="form-control" value="4" min="1" max="30" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">عدد الشقق في كل طابق</label>
                            <input type="number" name="units_per_floor" class="form-control" value="4" min="1" max="20" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">الواجب الشهري الموحد (درهم)</label>
                        <input type="number" step="0.01" name="monthly_fee" class="form-control" value="200" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="bulkModal = false">إلغاء</button>
                    <button type="submit" class="btn btn-primary">بدء التوليد التلقائي</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Filters Card --}}
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-body" style="padding:1.25rem;">
        <form method="GET" action="{{ route('portal.apartments.index') }}" style="display:flex; align-items:center; gap:1rem; flex-wrap:wrap;">
            <div class="search-box" style="flex:1;">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="ابحث برقم الشقة...">
                <span class="search-icon">🔍</span>
            </div>

            <select name="building_id" class="form-select" style="width:auto; min-width:180px;">
                <option value="">جميع العمارات</option>
                @foreach($buildings as $b)
                    <option value="{{ $b->id }}" {{ request('building_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
            </select>

            <select name="status" class="form-select" style="width:auto; min-width:160px;">
                <option value="">جميع الحالات</option>
                <option value="OCCUPIED" {{ request('status') == 'OCCUPIED' ? 'selected' : '' }}>مسكونة (بها مقيم)</option>
                <option value="VACANT" {{ request('status') == 'VACANT' ? 'selected' : '' }}>شاغرة (بدون مقيم)</option>
            </select>

            <select name="billable" class="form-select" style="width:auto; min-width:160px;">
                <option value="">الأداء والسانديك</option>
                <option value="1" {{ request('billable') === '1' ? 'selected' : '' }}>خاضعة للأداء</option>
                <option value="0" {{ request('billable') === '0' ? 'selected' : '' }}>معفاة</option>
            </select>

            <button type="submit" class="btn btn-primary btn-sm" style="padding:0.75rem 1.25rem;">
                تطبيق الفلترة
            </button>

            @if(request()->hasAny(['search', 'building_id', 'status', 'billable']))
                <a href="{{ route('portal.apartments.index') }}" class="btn btn-secondary btn-sm" style="padding:0.75rem 1.25rem;">
                    إلغاء الفلاتر
                </a>
            @endif
        </form>
    </div>
</div>

{{-- Apartments Table Card --}}
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="min-width:110px;">رقم الشقة</th>
                    <th style="min-width:120px;">العمارة</th>
                    <th style="min-width:110px;">الطابق</th>
                    <th style="min-width:150px;">المقيم الحالي</th>
                    <th style="min-width:130px;">الواجب الشهري</th>
                    <th style="text-align:center; min-width:120px;">حالة الخضوع</th>
                    <th style="min-width:160px;">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($apartments as $apartment)
                    <tr>
                        <td>
                            <strong style="font-size:1.1rem; color:var(--text-primary);" class="num">
                                شقة {{ $apartment->unit_number }}
                            </strong>
                        </td>
                        <td>{{ $apartment->building?->name ?? '—' }}</td>
                        <td class="num">
                            {{ $apartment->floor_number == 0 ? 'الطابق الأرضي' : 'الطابق ' . $apartment->floor_number }}
                        </td>
                        <td>
                            @if($apartment->residents->isNotEmpty())
                                <div style="display:flex; align-items:center; gap:0.5rem;">
                                    <span class="badge badge-success">مأهولة</span>
                                    <strong>{{ $apartment->residents->first()->full_name }}</strong>
                                </div>
                            @else
                                <span class="badge badge-neutral">شاغرة</span>
                            @endif
                        </td>
                        <td>
                            <span class="num" style="font-weight:800; color:var(--brand-primary);">
                                {{ number_format($apartment->monthly_fee, 2) }} د.م
                            </span>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('portal.apartments.toggle-billable', $apartment->id) }}">
                                @csrf
                                <button type="submit" class="badge {{ $apartment->is_billable ? 'badge-success' : 'badge-neutral' }}" style="cursor:pointer; border:none;" title="انقر للتبديل">
                                    {{ $apartment->is_billable ? '✓ خاضعة للأداء' : 'معفاة من السانديك' }}
                                </button>
                            </form>
                        </td>
                        <td>
                            <div style="display:flex; align-items:center; gap:0.5rem;">
                                @if($apartment->residents->isNotEmpty())
                                    <form method="POST" action="{{ route('portal.apartments.vacate', $apartment->id) }}"
                                          data-confirm="هل تؤكد تفريغ الشقة {{ $apartment->unit_number }}؟ سيتم فك ارتباط الساكن وإعادة ضبط المبالغ والحسابات للصيغة الأولى."
                                          data-confirm-title="تفريغ الشقة"
                                          data-confirm-type="warning"
                                          data-confirm-btn="تفريغ الشقة">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-secondary" style="color:var(--color-warning);" title="تفريغ الشقة وتصفير السجلات">
                                            🚪 تفريغ الشقة
                                        </button>
                                    </form>
                                @endif

                                <form method="POST" action="{{ route('portal.apartments.destroy', $apartment->id) }}"
                                      data-confirm="هل أنت متأكد من حذف الشقة رقم {{ $apartment->unit_number }} نهائياً؟"
                                      data-confirm-title="تأكيد حذف الشقة"
                                      data-confirm-type="danger"
                                      data-confirm-btn="حذف الشقة نهائياً">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-secondary" style="color:var(--color-danger);" title="حذف الشقة">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center; padding:3rem; color:var(--text-muted);">
                            لا توجد شقق مطابقة للمعايير المحددة.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($apartments->hasPages())
        <div class="card-footer">
            {{ $apartments->links() }}
        </div>
    @endif
</div>

@endsection
