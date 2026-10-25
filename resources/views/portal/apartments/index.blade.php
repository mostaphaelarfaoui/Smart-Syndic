@extends('layouts.dashboard')

@section('title', 'الشقق والوحدات السكنية')

@section('content')

<div x-data="apartmentsPage()" x-init="init()">
    <div class="page-header">
        <div>
            <h1 class="page-title">إدارة الشقق والوحدات السكنية</h1>
            <p class="page-subtitle">متابعة تفاصيل الشقق، الملاك المقيمين، سجلات الأداء والوصولات مصنفة بالسنوات</p>
        </div>

        <div class="page-actions" style="display:flex; align-items:center; gap:0.5rem;">
            <button type="button" class="btn btn-secondary" @click="bulkModal = true">
                <span>⚡ توليد جماعي للشقق</span>
            </button>
            <button type="button" class="btn btn-primary" @click="addModal = true">
                <span>+ إضافة شقة جديدة</span>
            </button>
        </div>
    </div>

    {{-- Modal: Add Single Apartment --}}
    <div class="modal-overlay" x-show="addModal" x-cloak style="z-index:99990;">
        <div class="modal-dialog" @click.away="addModal = false">
            <div class="modal-header">
                <h3 class="modal-title">إضافة شقة جديدة</h3>
                <button type="button" class="icon-btn" @click="addModal = false">✕</button>
            </div>
            <form method="POST" action="{{ route('portal.apartments.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" style="font-weight:700;">العمارة / البلوك</label>
                        <select name="building_id" class="form-select" required>
                            @foreach($buildings as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}{{ $b->block ? ' (' . $b->block . ')' : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-weight:700;">رقم أو اسم الشقة</label>
                        <input type="text" name="unit_number" class="form-control" placeholder="مثال: 12 أو شقة 1" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-weight:700;">الواجب الشهري (درهم)</label>
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

    {{-- Modal: Bulk Generate Apartments (No Floors - Pure Units & Prefix) --}}
    <div class="modal-overlay" x-show="bulkModal" x-cloak style="z-index:99990;">
        <div class="modal-dialog" style="max-width:550px;" @click.away="bulkModal = false">
            <div class="modal-header">
                <h3 class="modal-title">توليد شقق العمارة بشكل جماعي</h3>
                <button type="button" class="icon-btn" @click="bulkModal = false">✕</button>
            </div>
            <form method="POST" action="{{ route('portal.apartments.bulk-generate') }}">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" style="font-weight:700;">العمارة المستهدفة</label>
                        <select name="building_id" class="form-select" required>
                            @foreach($buildings as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}{{ $b->block ? ' (' . $b->block . ')' : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="display:grid; grid-template-columns: 3fr 2fr; gap:1rem;">
                        <div class="form-group">
                            <label class="form-label" style="font-weight:700;">عدد الشقق المراد إنشاؤها</label>
                            <input type="number" name="total_units" class="form-control" value="10" min="1" max="500" required placeholder="مثال: 10">
                        </div>
                        <div class="form-group">
                            <label class="form-label">بادئة الترقيم (اختياري)</label>
                            <input type="text" name="unit_prefix" class="form-control" placeholder="مثال: شقة أو A-">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-weight:700;">الواجب الشهري الموحد (درهم)</label>
                        <input type="number" step="0.01" name="monthly_fee" class="form-control" value="200" required>
                    </div>

                    <div style="background:var(--bg-secondary); padding:0.85rem 1.1rem; border-radius:10px; border:1px solid var(--border-color); font-size:0.84rem; color:var(--text-secondary);">
                        💡 سيتم توليد أرقام الشقق تلقائياً بالتسلسل بدءاً من آخر شقة موجودة بالعمارة.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="bulkModal = false">إلغاء</button>
                    <button type="submit" class="btn btn-primary" style="font-weight:700;">بدء التوليد التلقائي ✓</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Filters Card --}}
    <div class="card" style="margin-bottom:1.5rem;">
        <div class="card-body" style="padding:1.25rem;">
            <form method="GET" action="{{ route('portal.apartments.index') }}" style="display:flex; align-items:center; gap:1rem; flex-wrap:wrap;">
                <div class="search-box" style="flex:1; min-width:200px;">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="ابحث برقم الشقة...">
                    <span class="search-icon">🔍</span>
                </div>

                <select name="building_id" class="form-select" style="width:auto; min-width:180px;">
                    <option value="">جميع العمارات</option>
                    @foreach($buildings as $b)
                        <option value="{{ $b->id }}" {{ request('building_id') == $b->id ? 'selected' : '' }}>
                            {{ $b->name }}{{ $b->block ? ' (' . $b->block . ')' : '' }}
                        </option>
                    @endforeach
                </select>

                <select name="status" class="form-select" style="width:auto; min-width:160px;">
                    <option value="">جميع الحالات</option>
                    <option value="OCCUPIED" {{ request('status') == 'OCCUPIED' ? 'selected' : '' }}>مأهولة (بها مقيم)</option>
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
                        <th style="min-width:140px;">العمارة / البلوك</th>
                        <th style="min-width:160px;">المقيم الحالي</th>
                        <th style="min-width:130px;">الواجب الشهري</th>
                        <th style="text-align:center; min-width:120px;">حالة الخضوع</th>
                        <th style="min-width:240px; text-align:center;">الإجراءات والتفاصيل</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($apartments as $apartment)
                        @php
                            $cleanUnit = str_starts_with(trim($apartment->unit_number), 'شقة') ? $apartment->unit_number : 'شقة ' . $apartment->unit_number;
                        @endphp
                        <tr>
                            <td>
                                <strong style="font-size:1.05rem; color:var(--text-primary);" class="num">
                                    {{ $cleanUnit }}
                                </strong>
                            </td>
                            <td>
                                <span style="font-weight:600;">{{ $apartment->building?->name ?? '—' }}</span>
                                @if($apartment->building?->block)
                                    <span class="badge badge-neutral" style="font-size:0.72rem; margin-right:4px;">{{ $apartment->building->block }}</span>
                                @endif
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
                            <td style="text-align:center;">
                                <form method="POST" action="{{ route('portal.apartments.toggle-billable', $apartment->id) }}">
                                    @csrf
                                    <button type="submit" class="badge {{ $apartment->is_billable ? 'badge-success' : 'badge-neutral' }}" style="cursor:pointer; border:none;" title="انقر للتبديل">
                                        {{ $apartment->is_billable ? '✓ خاضعة للأداء' : 'معفاة من السانديك' }}
                                    </button>
                                </form>
                            </td>
                            <td style="text-align:center;">
                                <div style="display:flex; align-items:center; justify-content:center; gap:0.4rem;">
                                    <button type="button" 
                                            class="btn btn-sm btn-secondary" 
                                            style="display:inline-flex; align-items:center; gap:0.3rem; font-weight:700;"
                                            @click="openApartmentDetails('{{ $apartment->id }}')">
                                        <span>📋 سجل الدفعات</span>
                                    </button>

                                    @if($apartment->residents->isNotEmpty())
                                        <form method="POST" action="{{ route('portal.apartments.vacate', $apartment->id) }}"
                                              data-confirm="هل تؤكد تفريغ الشقة {{ $apartment->unit_number }}؟ سيتم فك ارتباط الساكن وإعادة ضبط المبالغ والحسابات للصيغة الأولى."
                                              data-confirm-title="تفريغ الشقة وتصفير السجلات"
                                              data-confirm-type="warning"
                                              data-confirm-password="true"
                                              data-confirm-btn="تفريغ الشقة">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-secondary" style="color:var(--color-warning);" title="تفريغ الشقة وتصفير السجلات">
                                                🚪 تفريغ
                                            </button>
                                        </form>
                                    @endif

                                    <form method="POST" action="{{ route('portal.apartments.destroy', $apartment->id) }}"
                                          data-confirm="هل أنت متأكد من حذف الشقة رقم {{ $apartment->unit_number }} نهائياً؟ سيتم حذف كافة السجلات المرتبطة بها."
                                          data-confirm-title="تأكيد حذف الشقة نهائياً"
                                          data-confirm-type="danger"
                                          data-confirm-password="true"
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
                            <td colspan="6" style="text-align:center; padding:3rem; color:var(--text-muted);">
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

    {{-- Modal: Apartment Details & Payments History Grouped by Year --}}
    <div class="modal-overlay" x-show="detailsModal" x-cloak style="z-index:99995;">
        <div class="modal-dialog" style="max-width:850px; width:95%;" @click.away="detailsModal = false">
            <div class="modal-header" style="display:flex; align-items:center; justify-content:space-between;">
                <div>
                    <h3 class="modal-title" style="margin:0;" x-text="detailsData.apartment ? detailsData.apartment.clean_unit + ' — تفاصيل وسجل الدفعات' : 'جاري التحميل...'"></h3>
                    <p style="margin:0; font-size:0.82rem; color:var(--text-secondary); margin-top:3px;" x-text="detailsData.apartment ? '🏢 ' + detailsData.apartment.building_name + (detailsData.apartment.block ? ' (بلوك ' + detailsData.apartment.block + ')' : '') : ''"></p>
                </div>
                <button type="button" class="icon-btn" @click="detailsModal = false">✕</button>
            </div>

            <div class="modal-body" style="max-height:75vh; overflow-y:auto; padding:1.25rem;">
                <template x-if="detailsLoading">
                    <div style="text-align:center; padding:3rem; color:var(--text-muted);">
                        <div style="font-size:2rem; margin-bottom:0.5rem;">⏳</div>
                        <div>جاري تحميل بيانات الشقة وسجل الدفعات والوصولات...</div>
                    </div>
                </template>

                <template x-if="!detailsLoading && detailsData.apartment">
                    <div>
                        {{-- Apartment Summary Bar --}}
                        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:1rem; background:var(--bg-secondary); border:1px solid var(--border-color); border-radius:12px; padding:1.1rem; margin-bottom:1.5rem;">
                            <div>
                                <div style="font-size:0.75rem; color:var(--text-muted); font-weight:700;">الساكن المقيم</div>
                                <div style="font-weight:800; color:var(--text-primary); margin-top:4px; font-size:0.95rem;">
                                    <span x-text="detailsData.apartment.residents.length > 0 ? detailsData.apartment.residents[0].name : 'شاغرة (بدون ساكن)'"></span>
                                </div>
                            </div>
                            <div>
                                <div style="font-size:0.75rem; color:var(--text-muted); font-weight:700;">رقم الهاتف</div>
                                <div class="num" style="font-weight:800; color:var(--text-primary); margin-top:4px; font-size:0.95rem;">
                                    <span x-text="detailsData.apartment.residents.length > 0 && detailsData.apartment.residents[0].phone ? detailsData.apartment.residents[0].phone : '—'"></span>
                                </div>
                            </div>
                            <div>
                                <div style="font-size:0.75rem; color:var(--text-muted); font-weight:700;">الواجب الشهري</div>
                                <div class="num" style="font-weight:900; color:var(--brand-primary); margin-top:4px; font-size:1.05rem;">
                                    <span x-text="detailsData.apartment.monthly_fee.toFixed(2) + ' د.م'"></span>
                                </div>
                            </div>
                            <div>
                                <div style="font-size:0.75rem; color:var(--text-muted); font-weight:700;">حالة الأداء</div>
                                <div style="margin-top:4px;">
                                    <span class="badge" :class="detailsData.apartment.is_billable ? 'badge-success' : 'badge-neutral'" x-text="detailsData.apartment.is_billable ? 'خاضعة للأداء' : 'معفاة'"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Payments History Grouped by Year --}}
                        <div style="margin-bottom:1rem; display:flex; align-items:center; justify-content:space-between;">
                            <h4 style="margin:0; font-size:1.05rem; font-weight:800; color:var(--text-primary); display:flex; align-items:center; gap:0.4rem;">
                                <span>💰 سجل الدفعات والتوصيلات الرسمية</span>
                            </h4>
                        </div>

                        <template x-if="!detailsData.payments_by_year || detailsData.payments_by_year.length === 0">
                            <div style="text-align:center; padding:3rem 1.5rem; background:var(--bg-secondary); border:1px dashed var(--border-color); border-radius:12px; color:var(--text-secondary);">
                                <div style="font-size:2.2rem; margin-bottom:0.6rem; opacity:0.8;">📭</div>
                                <div style="font-weight:700; font-size:0.95rem; color:var(--text-primary); margin-bottom:0.25rem;">لا توجد دفعات أو وصولات مسجلة لهذه الشقة بعد</div>
                                <div style="font-size:0.82rem; color:var(--text-muted);">عند قيام الساكن بأداء الواجبات أو تسجيل استخلاص جديد، ستظهر جميع التوصيلات هنا مصنفة حسب السنوات.</div>
                            </div>
                        </template>

                        <template x-for="yearGroup in detailsData.payments_by_year" :key="yearGroup.year">
                            <div style="margin-bottom:1.5rem; border:1px solid var(--border-color); border-radius:12px; overflow:hidden; background:var(--bg-surface);">
                                {{-- Year Header --}}
                                <div style="display:flex; align-items:center; justify-content:space-between; background:var(--bg-secondary); padding:0.85rem 1.2rem; border-bottom:1px solid var(--border-color);">
                                    <div style="display:flex; align-items:center; gap:0.5rem; font-weight:800; color:var(--text-primary);">
                                        <span>📅 سنة</span>
                                        <span class="num" x-text="yearGroup.year"></span>
                                        <span class="badge badge-neutral num" style="font-size:0.75rem;" x-text="yearGroup.count + ' وصل'"></span>
                                    </div>
                                    <div style="font-weight:800; color:var(--color-success); font-size:0.95rem;">
                                        المجموع: <span class="num" x-text="yearGroup.total_amount.toFixed(2) + ' د.م'"></span>
                                    </div>
                                </div>

                                {{-- Year Payments List --}}
                                <div class="table-responsive">
                                    <table class="table" style="margin:0; font-size:0.85rem;">
                                        <thead>
                                            <tr style="background:var(--bg-surface-elevated); border-bottom:1px solid var(--border-color);">
                                                <th style="color:var(--text-secondary); font-size:0.8rem; font-weight:700;">رقم الوصل</th>
                                                <th style="color:var(--text-secondary); font-size:0.8rem; font-weight:700;">تاريخ الأداء</th>
                                                <th style="color:var(--text-secondary); font-size:0.8rem; font-weight:700;">الشهر المستخلص</th>
                                                <th style="color:var(--text-secondary); font-size:0.8rem; font-weight:700;">المبلغ</th>
                                                <th style="color:var(--text-secondary); font-size:0.8rem; font-weight:700;">طريقة الأداء</th>
                                                <th style="color:var(--text-secondary); font-size:0.8rem; font-weight:700; text-align:center;">إجراءات الوصل</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="p in yearGroup.payments" :key="p.id">
                                                <tr>
                                                    <td class="num" style="font-weight:800; color:var(--brand-primary);" x-text="'#' + p.receipt_number"></td>
                                                    <td class="num" style="color:var(--text-secondary);" x-text="p.payment_date"></td>
                                                    <td>
                                                        <template x-for="m in p.covered_months" :key="m">
                                                            <span class="badge badge-neutral num" style="font-size:0.72rem; font-weight:700; margin-left:3px;" x-text="m"></span>
                                                        </template>
                                                    </td>
                                                    <td>
                                                        <strong class="num" style="color:var(--color-success);" x-text="p.amount_paid.toFixed(2) + ' د.م'"></strong>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-info" x-text="p.payment_method"></span>
                                                    </td>
                                                    <td style="text-align:center;">
                                                        <div style="display:flex; align-items:center; justify-content:center; gap:0.3rem;">
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-secondary" 
                                                                    style="padding:0.25rem 0.6rem; font-size:0.78rem;" 
                                                                    @click="openPreview(p.id, p.receipt_number, p.preview_url)">
                                                                👁️ معاينة
                                                            </button>
                                                            <a :href="p.download_url" 
                                                               class="btn btn-sm btn-primary" 
                                                               style="padding:0.25rem 0.6rem; font-size:0.78rem;" 
                                                               title="تحميل مباشر للوصل بصيغة PDF">
                                                                ⬇️ تحميل
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
            <div class="modal-footer" style="display:flex; justify-content:flex-end;">
                <button type="button" class="btn btn-secondary" @click="detailsModal = false">إغلاق</button>
            </div>
        </div>
    </div>

    {{-- In-Browser Receipt Preview Modal --}}
    <div class="modal-overlay" x-show="previewModal" x-cloak style="z-index:99999;">
        <div class="modal-dialog" style="max-width:850px; width:95%;" @click.away="previewModal = false">
            <div class="modal-header" style="display:flex; align-items:center; justify-content:space-between;">
                <div style="display:flex; align-items:center; gap:0.75rem;">
                    <span style="font-size:1.4rem;">📄</span>
                    <div>
                        <h3 class="modal-title" style="margin:0;" x-text="previewModalTitle"></h3>
                        <p style="margin:0; font-size:0.8rem; color:var(--text-muted);">معاينة الوصل الرسمي الإلكتروني المعتمد</p>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:0.5rem;">
                    <button type="button" class="btn btn-sm btn-secondary" @click="printPreview()" title="طباعة">
                        🖨️ طباعة
                    </button>
                    <a :href="previewDownloadUrl" class="btn btn-sm btn-primary" title="تحميل مباشر">
                        ⬇️ تحميل PDF
                    </a>
                    <button type="button" class="icon-btn" @click="previewModal = false">✕</button>
                </div>
            </div>
            <div class="modal-body" style="padding:0; background:var(--bg-app);">
                <iframe id="apartmentReceiptIframe" :src="previewModalUrl" style="width:100%; height:75vh; border:none; display:block;"></iframe>
            </div>
            <div class="modal-footer" style="display:flex; justify-content:flex-end;">
                <button type="button" class="btn btn-secondary" @click="previewModal = false">إغلاق</button>
            </div>
        </div>
    </div>
</div>

<script>
function apartmentsPage() {
    return {
        addModal: false,
        bulkModal: false,
        detailsModal: false,
        detailsLoading: false,
        detailsData: {},

        previewModal: false,
        previewModalUrl: '',
        previewDownloadUrl: '',
        previewModalTitle: '',

        init() {},

        openApartmentDetails(apartmentId) {
            this.detailsModal = true;
            this.detailsLoading = true;
            this.detailsData = {};

            fetch('{{ url('/portal/apartments') }}/' + apartmentId + '/history', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                this.detailsData = data;
                this.detailsLoading = false;
            })
            .catch(err => {
                console.error(err);
                this.detailsLoading = false;
                alert('تعذر تحميل تفاصيل وسجل دفعات الشقة. يرجى المحاولة لاحقاً.');
            });
        },

        openPreview(id, receiptNumber, url) {
            this.previewModalTitle = 'معاينة الوصل الرسمي رقم #' + receiptNumber;
            this.previewModalUrl = url;
            this.previewDownloadUrl = url + (url.includes('?') ? '&' : '?') + 'download=1';
            this.previewModal = true;
        },

        printPreview() {
            const iframe = document.getElementById('apartmentReceiptIframe');
            if (iframe && iframe.contentWindow) {
                iframe.contentWindow.print();
            }
        }
    };
}
</script>

@endsection
