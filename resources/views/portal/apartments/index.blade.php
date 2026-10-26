@extends('layouts.dashboard')

@section('title', 'الشقق والتحصيل')

@section('content')

<div x-data="apartmentsPage()" x-init="init()">
    {{-- Page Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">
                <span>🏢</span>
                <span>الشقق والواجبات والتحصيل</span>
            </h1>
            <p class="page-subtitle">إدارة شقق الإقامة، متابعة حالات الأداء الشهرية، استخلاص التوصيلات وسجل المقبوضات</p>
        </div>

        <div class="page-actions" style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
            <button type="button" class="btn btn-secondary" onclick="openFinancialReportModal('revenues')" style="display:inline-flex; align-items:center; gap:0.35rem; font-weight:700;">
                <span>📑</span>
                <span>تقرير المداخيل (PDF)</span>
            </button>
            <button type="button" class="btn btn-secondary" @click="bulkModal = true" style="display:inline-flex; align-items:center; gap:0.35rem; font-weight:700;">
                <span>⚡</span>
                <span>توليد جماعي</span>
            </button>
            <button type="button" class="btn btn-secondary" @click="addModal = true" style="display:inline-flex; align-items:center; gap:0.35rem; font-weight:700;">
                <span>+</span>
                <span>إضافة شقة</span>
            </button>
            <button type="button" class="btn btn-success" @click="openQuickPayment()" style="display:inline-flex; align-items:center; gap:0.4rem; font-weight:800; background:linear-gradient(135deg, #10b981 0%, #059669 100%); border:none; box-shadow:0 3px 10px rgba(16,185,129,0.35); color:#fff;">
                <span>+</span>
                <span>استخلاص دفعة جديدة</span>
            </button>
        </div>
    </div>

    {{-- Unified Financial & Operational KPI Grid --}}
    <div class="kpi-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom:1.5rem;">
        {{-- KPI 1: Total Units --}}
        <div class="kpi-card" style="padding:1.15rem 1.35rem;">
            <div class="kpi-card-header" style="margin-bottom:0.4rem;">
                <span class="kpi-label">إجمالي الشقق</span>
                <div class="kpi-icon-box teal" style="width:34px; height:34px; font-size:1rem;">🏢</div>
            </div>
            <div class="kpi-value num" style="font-size:1.6rem; margin-bottom:0;">
                {{ $totalCount }} <span class="unit">شقة</span>
            </div>
            <div class="kpi-footer" style="padding-top:0.5rem; margin-top:0.4rem; border-top:1px dashed var(--border-color); font-size:0.8rem; display:flex; justify-content:space-between;">
                <span>مأهولة: <strong class="num">{{ $occupiedCount }}</strong></span>
                <span>خاضعة للواجب: <strong class="num">{{ $billableCount }}</strong></span>
            </div>
        </div>

        {{-- KPI 2: Collection Rate --}}
        <div class="kpi-card" style="padding:1.15rem 1.35rem;">
            <div class="kpi-card-header" style="margin-bottom:0.4rem;">
                <span class="kpi-label">نسبة تحصيل ({{ now()->locale('ar')->translatedFormat('F Y') }})</span>
                <div class="kpi-icon-box emerald" style="width:34px; height:34px; font-size:1rem;">📈</div>
            </div>
            <div class="kpi-value num" style="font-size:1.6rem; margin-bottom:0; display:flex; align-items:baseline; justify-content:space-between;">
                <span>{{ $collectionRate }}%</span>
                <span class="badge {{ $collectionRate >= 70 ? 'badge-success' : ($collectionRate >= 40 ? 'badge-warning' : 'badge-danger') }}" style="font-size:0.75rem;">
                    {{ $paidUnitsCount }}/{{ $billableCount }} شقة
                </span>
            </div>
            <div style="background:var(--bg-secondary); height:6px; border-radius:999px; overflow:hidden; margin:0.5rem 0 0.35rem 0;">
                <div style="background:linear-gradient(90deg, #0d9488, #10b981); height:100%; border-radius:999px; width:{{ min(100, $collectionRate) }}%;"></div>
            </div>
        </div>

        {{-- KPI 3: Collected This Month --}}
        <div class="kpi-card" style="padding:1.15rem 1.35rem;">
            <div class="kpi-card-header" style="margin-bottom:0.4rem;">
                <span class="kpi-label">المداخيل المحصلة هذا الشهر</span>
                <div class="kpi-icon-box emerald" style="width:34px; height:34px; font-size:1rem;">💳</div>
            </div>
            <div class="kpi-value num" style="font-size:1.6rem; margin-bottom:0; color:var(--color-success);">
                {{ number_format($thisMonthCollected, 2) }} <span class="unit">د.م</span>
            </div>
            <div class="kpi-footer" style="padding-top:0.5rem; margin-top:0.4rem; border-top:1px dashed var(--border-color); font-size:0.8rem;">
                <span>المستهدف الشهري: <strong class="num">{{ number_format($expectedMonthly, 2) }} د.م</strong></span>
            </div>
        </div>

        {{-- KPI 4: Pending Arrears --}}
        <div class="kpi-card" style="padding:1.15rem 1.35rem;">
            <div class="kpi-card-header" style="margin-bottom:0.4rem;">
                <span class="kpi-label">المتأخرات غير المحصلة</span>
                <div class="kpi-icon-box amber" style="width:34px; height:34px; font-size:1rem;">⏳</div>
            </div>
            <div class="kpi-value num" style="font-size:1.6rem; margin-bottom:0; color:{{ $unpaidUnitsCount > 0 ? 'var(--color-warning)' : 'var(--color-success)' }};">
                {{ number_format($totalArrears, 2) }} <span class="unit">د.م</span>
            </div>
            <div class="kpi-footer" style="padding-top:0.5rem; margin-top:0.4rem; border-top:1px dashed var(--border-color); font-size:0.8rem;">
                <span>في انتظار الأداء: <strong class="num">{{ $unpaidUnitsCount }}</strong> شقة</span>
            </div>
        </div>
    </div>

    {{-- Tabs Switcher & View Mode Toggle --}}
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem; margin-bottom:1.1rem;">
        <div style="display:inline-flex; align-items:center; gap:0.4rem; background:var(--bg-surface); padding:0.35rem; border:1px solid var(--border-color); border-radius:var(--radius-lg);">
            <a href="{{ route('portal.apartments.index', ['tab' => 'apartments']) }}" 
               class="btn btn-sm {{ $tab === 'apartments' ? 'btn-primary' : 'btn-secondary' }}"
               style="font-weight:800; font-size:0.88rem; padding:0.45rem 1rem;">
                <span>🏢 الشقق والواجبات</span>
                <span class="badge badge-neutral num" style="font-size:0.75rem; margin-right:4px;">{{ $totalCount }}</span>
            </a>
            <a href="{{ route('portal.apartments.index', ['tab' => 'receipts']) }}" 
               class="btn btn-sm {{ $tab === 'receipts' ? 'btn-primary' : 'btn-secondary' }}"
               style="font-weight:800; font-size:0.88rem; padding:0.45rem 1rem;">
                <span>🧾 سجل المقبوضات والوصولات</span>
                <span class="badge badge-neutral num" style="font-size:0.75rem; margin-right:4px;">{{ $payments->total() }}</span>
            </a>
        </div>

        {{-- Flutter Cards vs Table Toggle --}}
        <div class="view-toggle-group">
            <button type="button" 
                    class="view-toggle-btn" 
                    :class="{ 'active': viewMode === 'grid' }" 
                    @click="viewMode = 'grid'; localStorage.setItem('smart_syndic_apt_view', 'grid');"
                    title="عرض بطاقات فلاتر الحديثة">
                <span>🔲 بطاقات</span>
            </button>
            <button type="button" 
                    class="view-toggle-btn" 
                    :class="{ 'active': viewMode === 'table' }" 
                    @click="viewMode = 'table'; localStorage.setItem('smart_syndic_apt_view', 'table');"
                    title="عرض الجدول الكلاسيكي">
                <span>📋 جدول</span>
            </button>
        </div>
    </div>

    {{-- ================= TAB 1: APARTMENTS & QUICK COLLECTION ================= --}}
    @if($tab === 'apartments')
        {{-- Flutter-Style Filter Chips Bar --}}
        <div class="flutter-chips-bar" style="margin-bottom:1.1rem;">
            @php
                $currPaymentStatus = request('payment_status');
                $currStatus = request('status');
                $currBillable = request('billable');
                $isAll = empty($currPaymentStatus) && empty($currStatus) && !request()->has('billable');
            @endphp
            <a href="{{ route('portal.apartments.index', array_merge(request()->except(['payment_status', 'status', 'billable', 'apts_page']), ['tab' => 'apartments'])) }}" 
               class="flutter-chip {{ $isAll ? 'active' : '' }}">
                <span>الكل</span>
                <span class="chip-count">{{ $totalCount }}</span>
            </a>
            <a href="{{ route('portal.apartments.index', array_merge(request()->except(['status', 'billable', 'apts_page']), ['tab' => 'apartments', 'payment_status' => 'PAID'])) }}" 
               class="flutter-chip {{ $currPaymentStatus === 'PAID' ? 'active' : '' }}">
                <span>✓ خالصة</span>
                <span class="chip-count">{{ $paidUnitsCount }}</span>
            </a>
            <a href="{{ route('portal.apartments.index', array_merge(request()->except(['status', 'apts_page']), ['tab' => 'apartments', 'payment_status' => 'UNPAID', 'billable' => '1'])) }}" 
               class="flutter-chip {{ ($currPaymentStatus === 'UNPAID' && $currBillable === '1') ? 'active' : '' }}">
                <span>⏳ متأخرة</span>
                <span class="chip-count">{{ $unpaidUnitsCount }}</span>
            </a>
            <a href="{{ route('portal.apartments.index', array_merge(request()->except(['payment_status', 'billable', 'apts_page']), ['tab' => 'apartments', 'status' => 'VACANT'])) }}" 
               class="flutter-chip {{ $currStatus === 'VACANT' ? 'active' : '' }}">
                <span>🚪 شاغرة</span>
                <span class="chip-count">{{ $vacantCount }}</span>
            </a>
            <a href="{{ route('portal.apartments.index', array_merge(request()->except(['payment_status', 'status', 'apts_page']), ['tab' => 'apartments', 'billable' => '0'])) }}" 
               class="flutter-chip {{ $currBillable === '0' ? 'active' : '' }}">
                <span>🛡️ معفاة</span>
                <span class="chip-count">{{ $exemptedCount }}</span>
            </a>
        </div>

        {{-- Filters Card --}}
        <div class="filter-card">
            <form method="GET" action="{{ route('portal.apartments.index') }}" class="filter-form">
                <input type="hidden" name="tab" value="apartments">
                @if(request('payment_status'))
                    <input type="hidden" name="payment_status" value="{{ request('payment_status') }}">
                @endif
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                @if(request()->has('billable'))
                    <input type="hidden" name="billable" value="{{ request('billable') }}">
                @endif

                <div class="search-box" style="flex:1; min-width:200px;">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="ابحث برقم الشقة أو اسم الساكن...">
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

                <button type="submit" class="btn btn-primary">
                    <span>بحث</span>
                </button>

                @if(request()->hasAny(['search', 'building_id', 'status', 'payment_status', 'billable']))
                    <a href="{{ route('portal.apartments.index', ['tab' => 'apartments']) }}" class="btn btn-secondary">
                        <span>إلغاء الفلاتر</span>
                    </a>
                @endif
            </form>
        </div>

        {{-- VIEW 1: FLUTTER-STYLE APARTMENT CARDS GRID --}}
        <div x-show="viewMode === 'grid'" x-cloak>
            <div class="apartment-cards-grid">
                @forelse($apartments as $apartment)
                    @php
                        $cleanUnit = str_starts_with(trim($apartment->unit_number), 'شقة') ? $apartment->unit_number : 'شقة ' . $apartment->unit_number;
                        $isPaidThisMonth = in_array($apartment->id, $paidApartmentIdsThisMonth);
                        $hasResident = $apartment->residents->isNotEmpty();
                        $resident = $hasResident ? $apartment->residents->first() : null;
                    @endphp
                    <div class="apt-flutter-card {{ !$apartment->is_billable ? 'status-exempted' : ($isPaidThisMonth ? 'status-settled' : 'status-arrears') }}">
                        <div>
                            {{-- Top Header: Badge Box + Info + Status Tag --}}
                            <div style="display:flex; align-items:flex-start; gap:0.75rem;">
                                <div class="apt-badge-box {{ !$apartment->is_billable ? 'apt-badge-exempted' : (!$hasResident ? 'apt-badge-vacant' : ($isPaidThisMonth ? 'apt-badge-settled' : 'apt-badge-arrears')) }}">
                                    <div style="font-size:1.15rem; line-height:1;">
                                        {{ !$apartment->is_billable ? '🛡️' : (!$hasResident ? '🚪' : ($isPaidThisMonth ? '🏢' : '⚠️')) }}
                                    </div>
                                    <div class="num" style="font-size:0.8rem; margin-top:2px;">{{ $apartment->unit_number }}</div>
                                </div>
                                
                                <div style="flex:1; min-width:0;">
                                    <div style="display:flex; align-items:center; justify-content:space-between; gap:0.4rem;">
                                        <h4 style="margin:0; font-size:1rem; font-weight:800; color:var(--text-primary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                            {{ $cleanUnit }}
                                        </h4>
                                        @if(!$apartment->is_billable)
                                            <span class="badge badge-warning" style="font-size:0.7rem; font-weight:700;">معفاة</span>
                                        @elseif(!$hasResident)
                                            <span class="badge badge-neutral" style="font-size:0.7rem;">شاغرة</span>
                                        @elseif($isPaidThisMonth)
                                            <span class="badge badge-success" style="font-size:0.7rem; font-weight:800;">✓ خالصة</span>
                                        @else
                                            <span class="badge badge-danger" style="font-size:0.7rem; font-weight:800;">⏳ متأخرة</span>
                                        @endif
                                    </div>

                                    <div style="font-size:0.78rem; color:var(--text-secondary); margin-top:3px; display:flex; align-items:center; gap:0.35rem; flex-wrap:wrap;">
                                        <span>{{ $apartment->building?->name ?? 'العمارة' }}</span>
                                        @if($apartment->building?->block)
                                            <span>• بلوك {{ $apartment->building->block }}</span>
                                        @endif
                                        <span>• الواجب: <strong class="num" style="color:var(--brand-primary);">{{ number_format($apartment->monthly_fee, 0) }} د.م</strong></span>
                                    </div>

                                    <div style="margin-top:5px; font-size:0.85rem; font-weight:700; color:var(--text-primary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                        @if($hasResident)
                                            👤 {{ $resident->full_name }}
                                        @else
                                            <span style="color:var(--text-muted); font-size:0.8rem;">(لا يوجد ساكن مسجل)</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Contact Row if Phone Exists --}}
                            @if($hasResident && $resident->phone)
                                @php
                                    $rawPhone = $resident->phone;
                                    $cleanPhone = preg_replace('/[^0-9]/', '', $rawPhone);
                                    if (str_starts_with($cleanPhone, '0')) {
                                        $cleanPhone = '212' . substr($cleanPhone, 1);
                                    }
                                @endphp
                                <div style="display:flex; align-items:center; gap:0.4rem; margin-top:0.75rem;">
                                    <a href="https://wa.me/{{ $cleanPhone }}?text={{ urlencode('السلام عليكم بخصوص الشقة ' . $apartment->unit_number) }}" 
                                       target="_blank" 
                                       class="apt-contact-btn apt-whatsapp-btn" 
                                       title="مراسلة فورية عبر الواتساب">
                                        <span>💬 واتساب</span>
                                    </a>
                                    <a href="tel:{{ $rawPhone }}" class="apt-contact-btn apt-phone-btn" title="اتصال هاتفي">
                                        <span>📞 اتصال</span>
                                    </a>
                                    <span style="font-size:0.78rem; color:var(--text-muted); margin-right:auto;" class="num">{{ $rawPhone }}</span>
                                </div>
                            @endif
                        </div>

                        {{-- Action Buttons --}}
                        <div style="margin-top:0.9rem; padding-top:0.75rem; border-top:1px solid var(--border-color); display:flex; align-items:center; justify-content:space-between; gap:0.4rem;">
                            <button type="button" 
                                    class="btn btn-sm {{ $isPaidThisMonth ? 'btn-secondary' : 'btn-success' }}" 
                                    style="flex:1; font-weight:800; font-size:0.82rem; padding:0.4rem 0.65rem; border-radius:8px; display:inline-flex; align-items:center; justify-content:center; gap:0.35rem;"
                                    @click="openPaymentForApartment('{{ $apartment->id }}', {{ (float) $apartment->monthly_fee }})">
                                <span>+</span>
                                <span>استخلاص الواجب</span>
                            </button>

                            <button type="button" 
                                    class="btn btn-sm btn-secondary" 
                                    style="font-weight:700; font-size:0.82rem; padding:0.4rem 0.75rem; border-radius:8px; display:inline-flex; align-items:center; gap:0.35rem;"
                                    @click="openApartmentDetails('{{ $apartment->id }}')">
                                <span>📋 السجل</span>
                            </button>

                            @if($hasResident)
                                <form method="POST" action="{{ route('portal.apartments.vacate', $apartment->id) }}"
                                      data-confirm="هل تؤكد تفريغ الشقة {{ $apartment->unit_number }}؟ سيتم فك ارتباط الساكن."
                                      data-confirm-title="تفريغ الشقة"
                                      data-confirm-type="warning"
                                      data-confirm-password="true"
                                      data-confirm-btn="تفريغ الشقة"
                                      style="margin:0;">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-secondary" style="padding:0.4rem 0.55rem; color:var(--color-warning);" title="تفريغ الشقة">
                                        🚪
                                    </button>
                                </form>
                            @endif

                            <form method="POST" action="{{ route('portal.apartments.destroy', $apartment->id) }}"
                                  data-confirm="هل أنت متأكد من حذف الشقة رقم {{ $apartment->unit_number }} نهائياً؟"
                                  data-confirm-title="تأكيد حذف الشقة"
                                  data-confirm-type="danger"
                                  data-confirm-password="true"
                                  data-confirm-btn="حذف الشقة نهائياً"
                                  style="margin:0;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-secondary" style="padding:0.4rem 0.55rem; color:var(--color-danger);" title="حذف الشقة">
                                    🗑️
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div style="grid-column: 1 / -1; text-align:center; padding:3rem; background:var(--bg-surface); border-radius:14px; border:1px dashed var(--border-color); color:var(--text-muted);">
                        لا توجد شقق مطابقة للمعايير المحددة.
                    </div>
                @endforelse
            </div>

            @if($apartments->hasPages())
                <div style="margin-top:1.25rem;">
                    {{ $apartments->links() }}
                </div>
            @endif
        </div>

        {{-- VIEW 2: CLASSIC TABLE VIEW --}}
        <div x-show="viewMode === 'table'" x-cloak class="card">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="min-width:110px;">رقم الشقة</th>
                            <th style="min-width:140px;">العمارة / البلوك</th>
                            <th style="min-width:160px;">المقيم الحالي</th>
                            <th style="min-width:120px;">الواجب الشهري</th>
                            <th style="text-align:center; min-width:140px;">حالة أداء الشهر الحالي</th>
                            <th style="min-width:240px; text-align:center;">إجراءات واستخلاص</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($apartments as $apartment)
                            @php
                                $cleanUnit = str_starts_with(trim($apartment->unit_number), 'شقة') ? $apartment->unit_number : 'شقة ' . $apartment->unit_number;
                                $isPaidThisMonth = in_array($apartment->id, $paidApartmentIdsThisMonth);
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
                                        <div>
                                            <strong style="font-size:0.9rem; color:var(--text-primary);">{{ $apartment->residents->first()->full_name }}</strong>
                                            @if($apartment->residents->first()->phone)
                                                <div style="font-size:0.75rem; color:var(--text-muted);">📞 {{ $apartment->residents->first()->phone }}</div>
                                            @endif
                                        </div>
                                    @else
                                        <span class="badge badge-neutral">شاغرة</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="num" style="font-weight:900; color:var(--brand-primary); font-size:0.95rem;">
                                        {{ number_format($apartment->monthly_fee, 2) }} د.م
                                    </span>
                                </td>
                                <td style="text-align:center;">
                                    @if(!$apartment->is_billable)
                                        <span class="badge badge-neutral" style="font-size:0.78rem;">معفاة من الواجبات</span>
                                    @elseif($isPaidThisMonth)
                                        <span class="badge badge-success" style="font-size:0.8rem; font-weight:700; padding:0.25rem 0.65rem;">
                                            ✓ تم السداد
                                        </span>
                                    @else
                                        <span class="badge badge-warning" style="font-size:0.8rem; font-weight:700; padding:0.25rem 0.65rem;">
                                            ⏳ في انتظار الأداء
                                        </span>
                                    @endif
                                </td>
                                <td style="text-align:center;">
                                    <div style="display:flex; align-items:center; justify-content:center; gap:0.4rem;">
                                        <button type="button" 
                                                class="btn btn-sm {{ $isPaidThisMonth ? 'btn-secondary' : 'btn-success' }}" 
                                                style="display:inline-flex; align-items:center; gap:0.3rem; font-weight:800; font-size:0.78rem; padding:0.3rem 0.65rem; border-radius:6px;"
                                                @click="openPaymentForApartment('{{ $apartment->id }}', {{ (float) $apartment->monthly_fee }})"
                                                title="استخلاص وإصدار وصل فوري">
                                            <span>+</span>
                                            <span>استخلاص</span>
                                        </button>

                                        <button type="button" 
                                                class="btn btn-sm btn-secondary" 
                                                style="display:inline-flex; align-items:center; gap:0.25rem; font-weight:700; font-size:0.78rem; padding:0.3rem 0.6rem; border-radius:6px;"
                                                @click="openApartmentDetails('{{ $apartment->id }}')"
                                                title="عرض سجل الوصولات السنوية">
                                            <span>📋 السجل</span>
                                        </button>

                                        @if($apartment->residents->isNotEmpty())
                                            <form method="POST" action="{{ route('portal.apartments.vacate', $apartment->id) }}"
                                                  data-confirm="هل تؤكد تفريغ الشقة {{ $apartment->unit_number }}؟ سيتم فك ارتباط الساكن."
                                                  data-confirm-title="تفريغ الشقة"
                                                  data-confirm-type="warning"
                                                  data-confirm-password="true"
                                                  data-confirm-btn="تفريغ الشقة">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-secondary" style="color:var(--color-warning); padding:0.3rem 0.55rem; font-size:0.78rem; border-radius:6px;" title="تفريغ الشقة">
                                                    🚪 تفريغ
                                                </button>
                                            </form>
                                        @endif

                                        <form method="POST" action="{{ route('portal.apartments.destroy', $apartment->id) }}"
                                              data-confirm="هل أنت متأكد من حذف الشقة رقم {{ $apartment->unit_number }} نهائياً؟"
                                              data-confirm-title="تأكيد حذف الشقة نهائياً"
                                              data-confirm-type="danger"
                                              data-confirm-password="true"
                                              data-confirm-btn="حذف الشقة نهائياً">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-secondary" style="color:var(--color-danger); padding:0.3rem 0.5rem; font-size:0.78rem; border-radius:6px;" title="حذف الشقة">
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
    @endif

    {{-- ================= TAB 2: RECEIPTS & COLLECTIONS HISTORY ================= --}}
    @if($tab === 'receipts')
        <div class="filter-card">
            <form method="GET" action="{{ route('portal.apartments.index') }}" class="filter-form">
                <input type="hidden" name="tab" value="receipts">

                <div class="search-box" style="flex:1; min-width:220px;">
                    <input type="text" name="receipt_search" value="{{ request('receipt_search') }}" placeholder="ابحث برقم الوصل أو رقم الشقة...">
                    <span class="search-icon">🔍</span>
                </div>

                <select name="receipt_apt_id" class="form-select" style="width:auto; min-width:180px;">
                    <option value="">جميع الشقق</option>
                    @foreach($allApartments as $apt)
                        @php
                            $cUnit = str_starts_with(trim($apt->unit_number), 'شقة') ? $apt->unit_number : 'شقة ' . $apt->unit_number;
                        @endphp
                        <option value="{{ $apt->id }}" {{ request('receipt_apt_id') == $apt->id ? 'selected' : '' }}>
                            {{ $cUnit }} ({{ $apt->building?->name }})
                        </option>
                    @endforeach
                </select>

                <select name="payment_method" class="form-select" style="width:auto; min-width:160px;">
                    <option value="">طريقة الأداء</option>
                    <option value="CASH" {{ request('payment_method') === 'CASH' ? 'selected' : '' }}>💵 نقداً</option>
                    <option value="BANK_TRANSFER" {{ request('payment_method') === 'BANK_TRANSFER' ? 'selected' : '' }}>🏦 تحويل بنكي</option>
                    <option value="CHEQUE" {{ request('payment_method') === 'CHEQUE' ? 'selected' : '' }}>📝 شيك</option>
                </select>

                <button type="submit" class="btn btn-primary">
                    <span>تطبيق الفلترة</span>
                </button>

                @if(request()->hasAny(['receipt_search', 'receipt_apt_id', 'payment_method']))
                    <a href="{{ route('portal.apartments.index', ['tab' => 'receipts']) }}" class="btn btn-secondary">
                        <span>إلغاء الفلتر</span>
                    </a>
                @endif
            </form>
        </div>

        {{-- VIEW 1: FLUTTER-STYLE RECEIPTS CARDS GRID --}}
        <div x-show="viewMode === 'grid'" x-cloak>
            <div class="apartment-cards-grid">
                @forelse($payments as $payment)
                    @php
                        $resident = $payment->apartment?->residents?->first();
                        $phone = $resident?->phone;
                        $cleanPhone = $phone ? preg_replace('/[^0-9]/', '', $phone) : '';
                        if ($cleanPhone && str_starts_with($cleanPhone, '0')) {
                            $cleanPhone = '212' . substr($cleanPhone, 1);
                        }
                    @endphp
                    <div class="apt-flutter-card" style="border-color:rgba(13, 148, 136, 0.25);">
                        <div>
                            {{-- Top Row: Month badge & Receipt Number on right, Amount on left --}}
                            <div style="display:flex; align-items:center; justify-content:space-between; gap:0.5rem; flex-wrap:wrap;">
                                <div style="display:flex; align-items:center; gap:0.4rem; flex-wrap:wrap;">
                                    @if(is_array($payment->covered_months) && !empty($payment->covered_months))
                                        <span class="badge badge-success num" style="font-size:0.75rem; font-weight:800; padding:0.25rem 0.6rem;">
                                            شهر {{ implode(', ', $payment->covered_months) }}
                                        </span>
                                    @else
                                        <span class="badge badge-success" style="font-size:0.75rem; font-weight:800;">أداء</span>
                                    @endif
                                    <span style="font-weight:800; font-size:0.85rem; color:var(--text-primary);">
                                        #{{ $payment->receipt_number }}
                                    </span>
                                </div>
                                <div class="num" style="font-weight:900; font-size:1.1rem; color:var(--color-success);">
                                    {{ number_format($payment->amount_paid, 0) }} د.م
                                </div>
                            </div>

                            {{-- Apartment and Resident info --}}
                            <div style="margin-top:0.6rem; display:flex; align-items:center; justify-content:space-between; gap:0.5rem;">
                                <div>
                                    <span class="badge badge-neutral num" style="font-size:0.8rem; font-weight:800;">
                                        🏢 شقة {{ $payment->apartment?->unit_number ?? '—' }}
                                    </span>
                                    @if($payment->apartment?->building)
                                        <span style="font-size:0.78rem; color:var(--text-secondary); margin-right:4px;">
                                            ({{ $payment->apartment->building->name }})
                                        </span>
                                    @endif
                                </div>
                                <div style="font-size:0.85rem; font-weight:700; color:var(--text-primary);">
                                    👤 {{ $resident?->full_name ?? 'المالك' }}
                                </div>
                            </div>

                            {{-- Date & Payment Method Row --}}
                            <div style="margin-top:0.5rem; font-size:0.8rem; color:var(--text-secondary); display:flex; align-items:center; justify-content:space-between;">
                                <span>📅 تاريخ: <strong class="num">{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('Y-m-d') : '—' }}</strong></span>
                                <span class="badge badge-info" style="font-size:0.75rem;">
                                    {{ match($payment->payment_method) {
                                        'CASH' => '💵 نقداً',
                                        'BANK_TRANSFER' => '🏦 تحويل بنكي',
                                        'CHEQUE' => '📝 شيك',
                                        default => $payment->payment_method
                                    } }}
                                </span>
                            </div>
                        </div>

                        {{-- Action Buttons Footer --}}
                        <div style="margin-top:0.85rem; padding-top:0.75rem; border-top:1px solid var(--border-color); display:flex; align-items:center; justify-content:space-between; gap:0.4rem;">
                            @if($cleanPhone)
                                <a href="https://wa.me/{{ $cleanPhone }}?text={{ urlencode('السلام عليكم تم إصدار وصل أداء رقم #' . $payment->receipt_number . ' بمبلغ ' . number_format($payment->amount_paid, 0) . ' درهم لشقتكم رقم ' . ($payment->apartment?->unit_number ?? '')) }}" 
                                   target="_blank" 
                                   class="btn btn-sm btn-secondary" 
                                   style="color:#15803d; border-color:rgba(37,211,102,0.4); font-size:0.78rem; padding:0.35rem 0.65rem;" 
                                   title="إرسال إشعار الوصل للساكن عبر واتساب">
                                    <span>💬 واتساب</span>
                                </a>
                            @endif

                            <div style="display:flex; align-items:center; gap:0.4rem; margin-right:auto;">
                                <button type="button" 
                                        class="btn btn-sm btn-primary" 
                                        style="padding:0.35rem 0.75rem; font-size:0.8rem; font-weight:700;"
                                        @click="openPreview('{{ $payment->id }}', '{{ $payment->receipt_number }}', '{{ route('receipts.download', ['payment' => $payment->id]) }}')">
                                    <span>👁️ معاينة</span>
                                </button>
                                <a href="{{ route('receipts.download', ['payment' => $payment->id, 'download' => 1]) }}" 
                                   class="btn btn-sm btn-secondary" 
                                   style="padding:0.35rem 0.65rem; font-size:0.8rem; font-weight:700;" 
                                   title="تحميل مباشر (PDF)">
                                    <span>⬇️ تحميل</span>
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div style="grid-column: 1 / -1; text-align:center; padding:3rem; background:var(--bg-surface); border-radius:14px; border:1px dashed var(--border-color); color:var(--text-muted);">
                        لا توجد مقبوضات مطابقة للمعايير المحددة.
                    </div>
                @endforelse
            </div>

            @if($payments->hasPages())
                <div style="margin-top:1.25rem;">
                    {{ $payments->links() }}
                </div>
            @endif
        </div>

        {{-- VIEW 2: CLASSIC TABLE VIEW --}}
        <div x-show="viewMode === 'table'" x-cloak class="card">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="min-width:130px;">رقم الوصل</th>
                            <th style="min-width:110px;">الشقة</th>
                            <th style="min-width:160px;">الساكن / المؤدي</th>
                            <th style="min-width:120px;">المبلغ المؤدى</th>
                            <th style="min-width:140px;">الشهور المغطاة</th>
                            <th style="min-width:110px;">طريقة الدفع</th>
                            <th style="min-width:110px;">تاريخ الأداء</th>
                            <th style="text-align:center; min-width:130px;">الوصل والتحميل</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                            <tr>
                                <td class="num" style="font-weight:800; color:var(--brand-primary);">
                                    #{{ $payment->receipt_number }}
                                </td>
                                <td>
                                    <span class="badge badge-neutral num" style="font-weight:800;">
                                        شقة {{ $payment->apartment?->unit_number ?? '—' }}
                                    </span>
                                </td>
                                <td>
                                    <strong style="color:var(--text-primary); font-size:0.9rem;">
                                        {{ $payment->apartment?->residents?->first()?->full_name ?? 'المالك' }}
                                    </strong>
                                </td>
                                <td>
                                    <span class="num" style="font-weight:900; color:var(--color-success); font-size:0.95rem;">
                                        {{ number_format($payment->amount_paid, 2) }} د.م
                                    </span>
                                </td>
                                <td>
                                    @if(is_array($payment->covered_months) && !empty($payment->covered_months))
                                        @foreach($payment->covered_months as $m)
                                            <span class="badge badge-neutral num" style="font-size:0.72rem; font-weight:700;">{{ $m }}</span>
                                        @endforeach
                                    @else
                                        <span style="color:var(--text-muted);">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-info" style="font-size:0.75rem;">
                                        {{ match($payment->payment_method) {
                                            'CASH' => '💵 نقداً',
                                            'BANK_TRANSFER' => '🏦 تحويل',
                                            'CHEQUE' => '📝 شيك',
                                            default => $payment->payment_method
                                        } }}
                                    </span>
                                </td>
                                <td class="num" style="color:var(--text-muted); font-size:0.85rem;">
                                    {{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('Y/m/d') : '—' }}
                                </td>
                                <td style="text-align:center;">
                                    <div style="display:flex; align-items:center; justify-content:center; gap:0.35rem;">
                                        <button type="button" 
                                                class="btn btn-sm btn-secondary" 
                                                style="padding:0.25rem 0.55rem; font-size:0.75rem; font-weight:700;"
                                                @click="openPreview('{{ $payment->id }}', '{{ $payment->receipt_number }}', '{{ route('receipts.download', ['payment' => $payment->id]) }}')">
                                            👁️ معاينة
                                        </button>
                                        <a href="{{ route('receipts.download', ['payment' => $payment->id, 'download' => 1]) }}" 
                                           class="btn btn-sm btn-primary" 
                                           style="padding:0.25rem 0.55rem; font-size:0.75rem; font-weight:700;">
                                            📥 PDF
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align:center; padding:3rem; color:var(--text-muted);">
                                    لا توجد مقبوضات مطابقة للمعايير المحددة.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($payments->hasPages())
                <div class="card-footer">
                    {{ $payments->links() }}
                </div>
            @endif
        </div>
    @endif

    {{-- ================= MODALS SECTION ================= --}}

    {{-- Modal 1: Interactive Payment / Collection Modal --}}
    <div class="modal-overlay" x-show="paymentModal" x-cloak style="z-index:99990;">
        <div class="modal-dialog" style="max-width:680px; width:95%;" @click.away="paymentModal = false">
            <div class="modal-header">
                <h3 class="modal-title">استخلاص واجب شهري فوري</h3>
                <button type="button" class="icon-btn" @click="paymentModal = false">✕</button>
            </div>
            <form method="POST" action="{{ route('portal.payments.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    {{-- Apartment Selection --}}
                    <div class="form-group">
                        <label class="form-label" style="font-weight:700;">الشقة المعنية</label>
                        <select name="apartment_id" class="form-select" required x-model="selectedApartmentId" @change="onApartmentChange($event)">
                            <option value="">-- اختر الشقة المعنية --</option>
                            @php
                                $groupedApts = $allApartments->groupBy(function($a) {
                                    $b = $a->building;
                                    if (!$b) return 'العمارة العامة';
                                    $block = $b->block ? " (بلوك {$b->block})" : '';
                                    return $b->name . $block;
                                });
                            @endphp
                            @foreach($groupedApts as $groupName => $apts)
                                <optgroup label="🏢 {{ $groupName }}">
                                    @foreach($apts as $apt)
                                        @php
                                            $cleanUnit = str_starts_with(trim($apt->unit_number), 'شقة') ? $apt->unit_number : 'شقة ' . $apt->unit_number;
                                            $residentName = $apt->residents->first()?->full_name;
                                        @endphp
                                        <option value="{{ $apt->id }}" data-fee="{{ (float) $apt->monthly_fee }}">
                                            {{ $cleanUnit }} {{ $residentName ? "({$residentName})" : '(شاغرة)' }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>

                    {{-- Interactive Multi-Month Selection --}}
                    <div class="form-group" style="background:var(--bg-secondary); padding:1.15rem; border-radius:12px; border:1px solid var(--border-color); margin-bottom:1.25rem;">
                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:0.85rem;">
                            <label class="form-label" style="margin:0; font-weight:800; font-size:0.92rem; color:var(--text-primary);">
                                الشهور المستخلصة
                                <span style="font-size:0.78rem; font-weight:normal; color:var(--text-muted); margin-right:4px;">(يمكنك تحديد عدة أشهر)</span>
                            </label>
                            <div style="display:flex; align-items:center; gap:0.4rem; background:var(--bg-surface); padding:3px 6px; border-radius:8px; border:1px solid var(--border-color);">
                                <button type="button" class="btn btn-sm btn-secondary" style="padding:0.2rem 0.55rem; font-size:0.75rem; border-radius:6px; cursor:pointer;" @click="changeYear(-1)">◀</button>
                                <strong class="num" style="font-size:0.95rem; min-width:55px; text-align:center; color:var(--text-primary); font-weight:800;" x-text="selectedYear"></strong>
                                <button type="button" class="btn btn-sm btn-secondary" style="padding:0.2rem 0.55rem; font-size:0.75rem; border-radius:6px; cursor:pointer;" @click="changeYear(1)">▶</button>
                            </div>
                        </div>

                        {{-- Month Pills Grid --}}
                        <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:0.45rem; margin-bottom:0.85rem;">
                            <template x-for="(monthObj, idx) in currentYearMonths" :key="monthObj.key">
                                <button type="button" 
                                        @click="toggleMonth(monthObj.key)"
                                        :style="isMonthSelected(monthObj.key) 
                                            ? 'background:linear-gradient(135deg, #0d9488 0%, #059669 100%); color:#ffffff; border-color:#0d9488; font-weight:800; box-shadow:0 2px 6px rgba(13,148,136,0.35);' 
                                            : 'background:var(--bg-surface); color:var(--text-primary); border-color:var(--border-color); font-weight:600;'"
                                        style="padding:0.55rem 0.35rem; border:1px solid; border-radius:8px; font-size:0.84rem; cursor:pointer; text-align:center; transition:all 0.15s ease;">
                                    <span x-text="monthObj.name"></span>
                                    <span x-show="isMonthSelected(monthObj.key)" style="font-size:0.75rem; margin-right:3px;">✓</span>
                                </button>
                            </template>
                        </div>

                        <input type="hidden" name="covered_months" :value="selectedMonths.join(',')">

                        <div style="display:flex; align-items:center; justify-content:space-between; font-size:0.84rem; padding-top:0.6rem; border-top:1px dashed var(--border-color);">
                            <span style="color:var(--brand-primary); font-weight:700;">
                                ✨ عدد الأشهر المحددة: <span class="num" style="font-weight:900;" x-text="selectedMonths.length"></span> شهر
                            </span>
                            <button type="button" class="btn btn-link btn-sm" style="padding:0; font-size:0.8rem; color:var(--brand-secondary); text-decoration:underline; font-weight:700; cursor:pointer;" @click="selectCurrentMonthOnly()">
                                إعادة ضبط للشهر الحالي فقط
                            </button>
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                        <div class="form-group">
                            <label class="form-label" style="font-weight:700;">المبلغ الإجمالي المؤدى (درهم)</label>
                            <input type="number" step="0.01" name="amount_paid" class="form-control" x-model="amountPaid" placeholder="المبلغ الإجمالي" required>
                            <small style="color:var(--text-muted); font-size:0.72rem;" x-show="selectedApartmentFee > 0">
                                الواجب الشهري للشقة: <strong class="num" x-text="selectedApartmentFee + ' د.م'"></strong>
                            </small>
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-weight:700;">طريقة الأداء</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="CASH">💵 نقداً (صندوق الإقامة)</option>
                                <option value="BANK_TRANSFER">🏦 تحويل بنكي</option>
                                <option value="CHEQUE">📝 شيك بنكي</option>
                            </select>
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                        <div class="form-group">
                            <label class="form-label" style="font-weight:700;">تاريخ الأداء</label>
                            <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">إثبات الأداء أو التوصيل البنكي (اختياري)</label>
                            <input type="file" name="bank_receipt_proof" class="form-control" accept="image/*">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">ملاحظات إضافية (اختياري)</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="ملاحظات حول طريقة الأداء أو رقم الشيك..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="paymentModal = false">إلغاء</button>
                    <button type="submit" class="btn btn-primary" style="font-weight:700;">
                        <span>تأكيد واستخراج التوصيل ✓</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal 2: Add Single Apartment --}}
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

    {{-- Modal 3: Bulk Generate Apartments --}}
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

    {{-- Modal 4: Apartment Details & Payments History Grouped by Year --}}
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
                                <div style="font-size:0.75rem; color:var(--text-muted); font-weight:700;">حالة الخضوع</div>
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
                                <div style="font-size:0.82rem; color:var(--text-muted);">عند تسجيل استخلاص جديد، ستظهر جميع التوصيلات هنا مصنفة حسب السنوات.</div>
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

                                {{-- Year Payments Cards List (Flutter-Identical 2-Row Layout) --}}
                                <div style="padding:0.75rem;">
                                    <template x-for="p in yearGroup.payments" :key="p.id">
                                        <div class="flutter-payment-item">
                                            {{-- Row 1: Month badge & receipt number on right, amount in bold green on left --}}
                                            <div class="item-top-row">
                                                <div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
                                                    <span class="badge badge-success num" style="font-size:0.76rem; font-weight:800; padding:0.25rem 0.6rem;">
                                                        شهر <span x-text="p.covered_months.join(', ')"></span>
                                                    </span>
                                                    <span style="font-weight:800; font-size:0.88rem; color:var(--text-primary);" x-text="'وصل #' + p.receipt_number"></span>
                                                </div>
                                                <div class="num" style="font-weight:900; font-size:1.05rem; color:var(--color-success);" x-text="p.amount_paid.toFixed(0) + ' د.م'"></div>
                                            </div>
                                            
                                            {{-- Row 2: Date + Payment Method on right, Action buttons on left --}}
                                            <div class="item-bottom-row">
                                                <div style="display:flex; align-items:center; gap:0.6rem; font-size:0.8rem; color:var(--text-secondary);">
                                                    <span>📅 <span class="num" x-text="'تاريخ: ' + p.payment_date"></span></span>
                                                    <span class="badge badge-neutral" style="font-size:0.72rem;" x-text="p.payment_method"></span>
                                                </div>
                                                <div style="display:flex; align-items:center; gap:0.4rem;">
                                                    <button type="button" 
                                                            class="btn btn-sm btn-primary" 
                                                            style="padding:0.25rem 0.65rem; font-size:0.78rem; font-weight:700; border-radius:6px; display:inline-flex; align-items:center; gap:0.3rem;" 
                                                            @click="openPreview(p.id, p.receipt_number, p.preview_url)">
                                                        <span>👁️</span>
                                                        <span>معاينة</span>
                                                    </button>
                                                    <a :href="p.download_url" 
                                                       class="btn btn-sm btn-secondary" 
                                                       style="padding:0.25rem 0.65rem; font-size:0.78rem; font-weight:700; border-radius:6px; display:inline-flex; align-items:center; gap:0.3rem;" 
                                                       title="تحميل مباشر للوصل بصيغة PDF">
                                                        <span>⬇️</span>
                                                        <span>تحميل</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
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

    {{-- Modal 5: In-Browser Receipt Preview Modal --}}
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

@endsection

@push('scripts')
<script>
function apartmentsPage() {
    return {
        viewMode: localStorage.getItem('smart_syndic_apt_view') || 'grid',
        addModal: false,
        bulkModal: false,
        paymentModal: false,
        detailsModal: false,
        detailsLoading: false,
        detailsData: {},

        previewModal: false,
        previewModalUrl: '',
        previewDownloadUrl: '',
        previewModalTitle: '',

        selectedApartmentId: '',
        selectedApartmentFee: 0,
        amountPaid: '',
        selectedMonths: ['{{ now()->format('Y-m') }}'],
        selectedYear: {{ now()->year }},
        arabicMonthNames: ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليوز', 'غشت', 'شتنبر', 'أكتوبر', 'نونبر', 'دجنبر'],

        get currentYearMonths() {
            let months = [];
            for (let m = 1; m <= 12; m++) {
                let mStr = m < 10 ? '0' + m : '' + m;
                months.push({
                    key: this.selectedYear + '-' + mStr,
                    name: this.arabicMonthNames[m - 1]
                });
            }
            return months;
        },

        init() {},

        openQuickPayment() {
            this.selectedApartmentId = '';
            this.selectedApartmentFee = 0;
            this.amountPaid = '';
            this.selectedMonths = ['{{ now()->format('Y-m') }}'];
            this.selectedYear = {{ now()->year }};
            this.paymentModal = true;
        },

        openPaymentForApartment(aptId, fee) {
            this.selectedApartmentId = aptId;
            this.selectedApartmentFee = parseFloat(fee);
            this.selectedMonths = ['{{ now()->format('Y-m') }}'];
            this.selectedYear = {{ now()->year }};
            this.recalculateTotal();
            this.paymentModal = true;
        },

        onApartmentChange(e) {
            let opt = e.target.selectedOptions[0];
            if (opt && opt.dataset.fee) {
                this.selectedApartmentFee = parseFloat(opt.dataset.fee);
                this.recalculateTotal();
            }
        },

        toggleMonth(mKey) {
            let idx = this.selectedMonths.indexOf(mKey);
            if (idx > -1) {
                if (this.selectedMonths.length > 1) {
                    this.selectedMonths.splice(idx, 1);
                }
            } else {
                this.selectedMonths.push(mKey);
            }
            this.recalculateTotal();
        },

        isMonthSelected(mKey) {
            return this.selectedMonths.includes(mKey);
        },

        changeYear(delta) {
            this.selectedYear += delta;
        },

        selectCurrentMonthOnly() {
            this.selectedYear = {{ now()->year }};
            this.selectedMonths = ['{{ now()->format('Y-m') }}'];
            this.recalculateTotal();
        },

        recalculateTotal() {
            if (this.selectedApartmentFee > 0) {
                this.amountPaid = (this.selectedApartmentFee * this.selectedMonths.length).toFixed(2);
            }
        },

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
@endpush
