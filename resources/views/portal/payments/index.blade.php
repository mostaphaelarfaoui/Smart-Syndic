@extends('layouts.dashboard')

@section('title', 'الواجبات الشهرية والتحصيل')

@section('content')

<div x-data="paymentsPage()" x-init="init()">
    <div class="page-header">
        <div>
            <h1 class="page-title">الواجبات الشهرية والتحصيل</h1>
            <p class="page-subtitle">تسجيل مقبوضات السانديك، إصدار التوصيلات الرسمية المستقلة ومتابعة الشهور المؤداة</p>
        </div>

        <div class="page-actions" style="display:flex; align-items:center; gap:0.5rem;">
            <button type="button" class="btn btn-secondary" onclick="openFinancialReportModal('revenues')" style="display:inline-flex; align-items:center; gap:0.4rem; font-weight:700;">
                <svg width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>📑 تقرير المداخيل (PDF)</span>
            </button>
            <button type="button" class="btn btn-primary" @click="recordModal = true">
                <span>+ استخلاص دفعة جديدة</span>
            </button>
        </div>
    </div>

    {{-- Modal: Record New Payment --}}
    <div class="modal-overlay" x-show="recordModal" x-cloak style="z-index:99990;">
        <div class="modal-dialog" style="max-width:680px; width:95%;" @click.away="recordModal = false">
            <div class="modal-header">
                <h3 class="modal-title">استخلاص وتسجيل دفعة جديدة</h3>
                <button type="button" class="icon-btn" @click="recordModal = false">✕</button>
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
                                $groupedApartments = $apartments->groupBy(function($a) {
                                    $b = $a->building;
                                    if (!$b) return 'العمارة العامة';
                                    $block = $b->block ? " (بلوك {$b->block})" : '';
                                    return $b->name . $block;
                                });
                            @endphp
                            @foreach($groupedApartments as $groupName => $apts)
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
                                <span style="font-size:0.78rem; font-weight:normal; color:var(--text-muted); margin-right:4px;">(يمكنك تحديد عدة أشهر لاستخراج وصل مستقل لكل شهر)</span>
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

                        {{-- Hidden input containing comma-separated months --}}
                        <input type="hidden" name="covered_months" :value="selectedMonths.join(',')">

                        <div style="display:flex; align-items:center; justify-content:space-between; font-size:0.84rem; padding-top:0.6rem; border-top:1px dashed var(--border-color);">
                            <span style="color:var(--brand-primary); font-weight:700;">
                                ✨ عدد الأشهر المحددة: <span class="num" style="font-weight:900;" x-text="selectedMonths.length"></span> شهر
                                <template x-if="selectedMonths.length > 1">
                                    <span style="color:var(--color-success); font-weight:700;">(سيتم استخراج <span x-text="selectedMonths.length"></span> وصولات مستقلة)</span>
                                </template>
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
                    <button type="button" class="btn btn-secondary" @click="recordModal = false">إلغاء</button>
                    <button type="submit" class="btn btn-primary" style="font-weight:700;">
                        <span>تأكيد واستخراج التوصيل ✓</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- KPI Summary Row --}}
    <div class="kpi-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); margin-bottom:1.5rem;">
        <div class="kpi-card" style="padding:1.25rem;">
            <div class="kpi-card-header" style="margin-bottom:0.5rem;">
                <span class="kpi-label">مداخيل الشهر الحالي</span>
                <div class="kpi-icon-box emerald" style="width:36px; height:36px;">📈</div>
            </div>
            <div class="kpi-value num" style="font-size:1.75rem; margin-bottom:0; color:var(--color-success);">
                {{ number_format($thisMonthCollected, 2) }} <span class="unit">د.م</span>
            </div>
        </div>

        <div class="kpi-card" style="padding:1.25rem;">
            <div class="kpi-card-header" style="margin-bottom:0.5rem;">
                <span class="kpi-label">إجمالي المقبوضات المحصلة</span>
                <div class="kpi-icon-box teal" style="width:36px; height:36px;">💰</div>
            </div>
            <div class="kpi-value num" style="font-size:1.75rem; margin-bottom:0;">
                {{ number_format($totalCollected, 2) }} <span class="unit">د.م</span>
            </div>
        </div>
    </div>

    {{-- Filters Card --}}
    <div class="card" style="margin-bottom:1.5rem;">
        <div class="card-body" style="padding:1.25rem;">
            <form method="GET" action="{{ route('portal.payments.index') }}" style="display:flex; align-items:center; gap:1rem; flex-wrap:wrap;">
                <select name="apartment_id" class="form-select" style="width:auto; min-width:200px;">
                    <option value="">جميع الشقق</option>
                    @foreach($groupedApartments as $groupName => $apts)
                        <optgroup label="🏢 {{ $groupName }}">
                            @foreach($apts as $a)
                                @php
                                    $cleanUnit = str_starts_with(trim($a->unit_number), 'شقة') ? $a->unit_number : 'شقة ' . $a->unit_number;
                                @endphp
                                <option value="{{ $a->id }}" {{ request('apartment_id') == $a->id ? 'selected' : '' }}>
                                    {{ $cleanUnit }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>

                <select name="payment_method" class="form-select" style="width:auto; min-width:160px;">
                    <option value="">طريقة الأداء</option>
                    <option value="CASH" {{ request('payment_method') == 'CASH' ? 'selected' : '' }}>نقداً</option>
                    <option value="BANK_TRANSFER" {{ request('payment_method') == 'BANK_TRANSFER' ? 'selected' : '' }}>تحويل بنكي</option>
                    <option value="CHEQUE" {{ request('payment_method') == 'CHEQUE' ? 'selected' : '' }}>شيك</option>
                </select>

                <input type="month" name="month" value="{{ request('month') }}" class="form-control" style="width:auto; min-width:160px;">

                <button type="submit" class="btn btn-primary btn-sm" style="padding:0.75rem 1.25rem;">
                    تطبيق الفلترة
                </button>

                @if(request()->hasAny(['apartment_id', 'payment_method', 'month']))
                    <a href="{{ route('portal.payments.index') }}" class="btn btn-secondary btn-sm" style="padding:0.75rem 1.25rem;">
                        إلغاء الفلاتر
                    </a>
                @endif
            </form>
        </div>
    </div>

    {{-- Payments Table Card --}}
    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="min-width:110px;">رقم الوصل</th>
                        <th style="min-width:120px;">الشقة</th>
                        <th style="min-width:150px;">المؤدي / الساكن</th>
                        <th style="min-width:130px;">المبلغ المؤدى</th>
                        <th style="min-width:110px;">طريقة الدفع</th>
                        <th style="min-width:130px;">الشهور المغطاة</th>
                        <th style="min-width:110px;">تاريخ الأداء</th>
                        <th style="text-align:center; min-width:100px;">الحالة</th>
                        <th style="text-align:center; min-width:170px;">التوصيل الرسمي</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td class="num" style="font-weight:700; color:var(--text-secondary);">
                                #{{ $payment->receipt_number ?? substr($payment->id, 0, 8) }}
                            </td>
                            <td>
                                <strong style="color:var(--text-primary);" class="num">
                                    {{ str_starts_with(trim($payment->apartment?->unit_number ?? ''), 'شقة') ? $payment->apartment?->unit_number : 'شقة ' . ($payment->apartment?->unit_number ?? '—') }}
                                </strong>
                                @if($payment->apartment?->building)
                                    <div style="font-size:0.74rem; color:var(--text-muted);">
                                        {{ $payment->apartment->building->name }}{{ $payment->apartment->building->block ? ' (' . $payment->apartment->building->block . ')' : '' }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $payment->apartment?->residents?->first()?->full_name ?? 'المالك' }}</strong>
                            </td>
                            <td>
                                <span class="num" style="font-weight:800; color:var(--color-success); font-size:1rem;">
                                    {{ number_format($payment->amount_paid, 2) }} د.م
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-info">
                                    {{ match($payment->payment_method) {
                                        'CASH' => '💵 نقداً',
                                        'BANK_TRANSFER' => '🏦 تحويل',
                                        'CHEQUE' => '📝 شيك',
                                        default => $payment->payment_method
                                    } }}
                                </span>
                            </td>
                            <td>
                                @if(is_array($payment->covered_months))
                                    @foreach($payment->covered_months as $m)
                                        <span class="badge badge-neutral num" style="font-size:0.75rem; font-weight:700;">{{ $m }}</span>
                                    @endforeach
                                @else
                                    <span class="badge badge-neutral num">{{ $payment->covered_months ?? '—' }}</span>
                                @endif
                            </td>
                            <td class="num" style="color:var(--text-muted); font-size:0.85rem;">
                                {{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('Y/m/d') : '—' }}
                            </td>
                            <td style="text-align:center;">
                                @if($payment->status === 'DELIVERED')
                                    <span class="badge badge-success">✓ تم التسليم</span>
                                @elseif($payment->status === 'CONFIRMED')
                                    <span class="badge badge-info">✓ مؤكد</span>
                                @elseif($payment->status === 'DRAFT')
                                    <span class="badge badge-warning">مسودة</span>
                                @else
                                    <span class="badge badge-neutral">{{ $payment->status }}</span>
                                @endif
                            </td>
                            <td style="text-align:center;">
                                <div style="display:flex; align-items:center; justify-content:center; gap:0.4rem;">
                                    <button type="button" 
                                            class="btn btn-sm btn-secondary" 
                                            style="display:inline-flex; align-items:center; gap:0.3rem;"
                                            @click="openPreview('{{ $payment->id }}', '{{ $payment->receipt_number ?? substr($payment->id, 0, 8) }}', '{{ route('receipts.download', $payment) }}')">
                                        <span>👁️ معاينة</span>
                                    </button>
                                    <a href="{{ route('receipts.download', ['payment' => $payment->id, 'download' => 1]) }}" 
                                       class="btn btn-sm btn-primary" 
                                       style="display:inline-flex; align-items:center; gap:0.3rem;"
                                       title="تحميل مباشر للوصل بصيغة PDF">
                                        <span>⬇️ تحميل</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align:center; padding:3rem; color:var(--text-muted);">
                                لا توجد مقبوضات مسجلة مطابقة للمعايير.
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
                <iframe id="receiptPreviewIframe" :src="previewModalUrl" style="width:100%; height:75vh; border:none; display:block;"></iframe>
            </div>
            <div class="modal-footer" style="display:flex; justify-content:flex-end;">
                <button type="button" class="btn btn-secondary" @click="previewModal = false">إغلاق</button>
            </div>
        </div>
    </div>

    {{-- Multi-Receipt Success Modal (Shown after recording payment) --}}
    @if(session('generated_payments'))
    <div class="modal-overlay" x-show="successModal" x-cloak style="z-index:99998;">
        <div class="modal-dialog" style="max-width:620px; width:95%;" @click.away="successModal = false">
            <div class="modal-header" style="background:linear-gradient(135deg, rgba(16,185,129,0.12), rgba(6,78,59,0.08)); border-bottom:1px solid rgba(16,185,129,0.25);">
                <div style="display:flex; align-items:center; gap:0.75rem;">
                    <span style="font-size:1.8rem;">🎉</span>
                    <div>
                        <h3 class="modal-title" style="color:var(--color-success); margin:0;">تم استخلاص وتسجيل الدفعات بنجاح!</h3>
                        <p style="margin:0; font-size:0.82rem; color:var(--text-secondary);">تم إصدار وصل رسمي إلكتروني مستقل لكل شهر مؤدى:</p>
                    </div>
                </div>
                <button type="button" class="icon-btn" @click="successModal = false">✕</button>
            </div>
            <div class="modal-body" style="max-height:60vh; overflow-y:auto;">
                <div style="display:flex; flex-direction:column; gap:0.75rem;">
                    @foreach(session('generated_payments') as $p)
                        <div style="display:flex; align-items:center; justify-content:space-between; padding:0.9rem 1.1rem; background:var(--bg-secondary); border:1px solid var(--border-color); border-radius:10px; box-shadow:var(--shadow-sm);">
                            <div style="display:flex; align-items:center; gap:0.75rem;">
                                <div style="width:38px; height:38px; border-radius:8px; background:rgba(16,185,129,0.1); color:#10b981; display:flex; align-items:center; justify-content:center; font-size:1.2rem;">
                                    📄
                                </div>
                                <div>
                                    <div style="font-weight:700; color:var(--text-primary); font-size:0.95rem;">
                                        وصل شهر {{ $p['month'] }}
                                    </div>
                                    <div style="font-size:0.78rem; color:var(--text-muted); font-family:monospace;">
                                        #{{ $p['receipt_number'] }} • {{ number_format($p['amount'], 2) }} د.م
                                    </div>
                                </div>
                            </div>
                            <div style="display:flex; align-items:center; gap:0.4rem;">
                                <button type="button" 
                                        class="btn btn-sm btn-secondary" 
                                        @click="openPreview('{{ $p['id'] }}', '{{ $p['receipt_number'] }}', '{{ $p['preview_url'] }}')">
                                    👁️ معاينة
                                </button>
                                <a href="{{ $p['download_url'] }}" class="btn btn-sm btn-primary">
                                    ⬇️ تحميل
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer" style="display:flex; justify-content:space-between; align-items:center;">
                <span style="font-size:0.85rem; color:var(--text-muted);">
                    إجمالي الوصولات المستخرجة: <strong>{{ count(session('generated_payments')) }}</strong>
                </span>
                <button type="button" class="btn btn-secondary" @click="successModal = false">إغلاق النافذة</button>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
function paymentsPage() {
    return {
        recordModal: false,
        previewModal: false,
        previewModalUrl: '',
        previewDownloadUrl: '',
        previewModalTitle: '',
        successModal: {{ session('generated_payments') ? 'true' : 'false' }},

        selectedApartmentId: '',
        selectedApartmentFee: 0,
        selectedYear: {{ (int) date('Y') }},
        selectedMonths: ['{{ date('Y-m') }}'],
        amountPaid: '',

        monthNames: [
            'يناير', 'فبراير', 'مارس', 'أبريل', 'ماي', 'يونيو',
            'يوليوز', 'غشت', 'شتنبر', 'أكتوبر', 'نونبر', 'دجنبر'
        ],

        init() {
            // Initial fee calculation if apartment is preselected
        },

        get currentYearMonths() {
            const list = [];
            for (let i = 1; i <= 12; i++) {
                const mm = i < 10 ? '0' + i : '' + i;
                list.push({
                    key: `${this.selectedYear}-${mm}`,
                    name: this.monthNames[i - 1],
                });
            }
            return list;
        },

        isMonthSelected(monthKey) {
            return this.selectedMonths.includes(monthKey);
        },

        toggleMonth(monthKey) {
            const index = this.selectedMonths.indexOf(monthKey);
            if (index > -1) {
                if (this.selectedMonths.length > 1) {
                    this.selectedMonths.splice(index, 1);
                }
            } else {
                this.selectedMonths.push(monthKey);
                this.selectedMonths.sort();
            }
            this.recalculateAmount();
        },

        changeYear(delta) {
            this.selectedYear += delta;
        },

        selectCurrentMonthOnly() {
            const current = '{{ date('Y-m') }}';
            this.selectedYear = {{ (int) date('Y') }};
            this.selectedMonths = [current];
            this.recalculateAmount();
        },

        onApartmentChange(event) {
            const selectedOption = event.target.options[event.target.selectedIndex];
            const fee = parseFloat(selectedOption.getAttribute('data-fee')) || 0;
            this.selectedApartmentFee = fee;
            this.recalculateAmount();
        },

        recalculateAmount() {
            if (this.selectedApartmentFee > 0) {
                const total = this.selectedMonths.length * this.selectedApartmentFee;
                this.amountPaid = total.toFixed(2);
            }
        },

        openPreview(id, receiptNumber, url) {
            this.previewModalTitle = 'معاينة الوصل الرسمي رقم #' + receiptNumber;
            this.previewModalUrl = url;
            this.previewDownloadUrl = url + (url.includes('?') ? '&' : '?') + 'download=1';
            this.previewModal = true;
        },

        printPreview() {
            const iframe = document.getElementById('receiptPreviewIframe');
            if (iframe && iframe.contentWindow) {
                iframe.contentWindow.print();
            }
        }
    };
}
</script>

@endsection
