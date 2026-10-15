@extends('layouts.dashboard')

@section('title', 'الواجبات الشهرية والتحصيل')

@section('content')

<div class="page-header" x-data="{ recordModal: false }">
    <div>
        <h1 class="page-title">الواجبات الشهرية والتحصيل</h1>
        <p class="page-subtitle">تسجيل مقبوضات السانديك، إصدار التوصيلات الرسمية ومتابعة الشهور المؤداة</p>
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

    {{-- Modal: Record New Payment --}}
    <div class="modal-overlay" x-show="recordModal" x-cloak>
        <div class="modal-dialog" @click.away="recordModal = false">
            <div class="modal-header">
                <h3 class="modal-title">استخلاص وتسجيل دفعة جديدة</h3>
                <button type="button" class="icon-btn" @click="recordModal = false">✕</button>
            </div>
            <form method="POST" action="{{ route('portal.payments.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">الشقة المعنية</label>
                        <select name="apartment_id" class="form-select" required>
                            <option value="">اختر الشقة...</option>
                            @foreach($apartments as $apt)
                                <option value="{{ $apt->id }}">
                                    شقة {{ $apt->unit_number }} — الواجب: {{ number_format($apt->monthly_fee, 2) }} د.م
                                    @if($apt->residents->isNotEmpty())
                                        ({{ $apt->residents->first()->full_name }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                        <div class="form-group">
                            <label class="form-label">المبلغ المؤدى (درهم)</label>
                            <input type="number" step="0.01" name="amount_paid" class="form-control" placeholder="200" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">طريقة الأداء</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="CASH">💵 نقداً (صندوق الإقامة)</option>
                                <option value="BANK_TRANSFER">🏦 تحويل بنكي</option>
                                <option value="CHEQUE">📝 شيك بنكي</option>
                            </select>
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                        <div class="form-group">
                            <label class="form-label">تاريخ الأداء</label>
                            <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">الشهور المغطاة</label>
                            <input type="text" name="covered_months" class="form-control" value="{{ date('Y-m') }}" placeholder="مثال: 2026-10">
                            <small style="color:var(--text-muted); font-size:0.75rem;">افصل بفواصل في حال تغطية عدة شهور</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">إثبات الأداء أو التوصيل البنكي (اختياري)</label>
                        <input type="file" name="bank_receipt_proof" class="form-control" accept="image/*">
                    </div>

                    <div class="form-group">
                        <label class="form-label">ملاحظات إضافية (اختياري)</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="ملاحظات حول طريقة الأداء أو رقم الشيك..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="recordModal = false">إلغاء</button>
                    <button type="submit" class="btn btn-primary">تأكيد واستخراج التوصيل ✓</button>
                </div>
            </form>
        </div>
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
            <select name="apartment_id" class="form-select" style="width:auto; min-width:180px;">
                <option value="">جميع الشقق</option>
                @foreach($apartments as $a)
                    <option value="{{ $a->id }}" {{ request('apartment_id') == $a->id ? 'selected' : '' }}>شقة {{ $a->unit_number }}</option>
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
                    <th style="min-width:100px;">الشقة</th>
                    <th style="min-width:150px;">المؤدي / الساكن</th>
                    <th style="min-width:140px;">المبلغ المؤدى</th>
                    <th style="min-width:110px;">طريقة الدفع</th>
                    <th style="min-width:130px;">الشهور المغطاة</th>
                    <th style="min-width:110px;">تاريخ الأداء</th>
                    <th style="text-align:center; min-width:110px;">الحالة</th>
                    <th style="text-align:center; min-width:90px;">التوصيل</th>
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
                                شقة {{ $payment->apartment?->unit_number ?? '—' }}
                            </strong>
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
                                    <span class="badge badge-neutral num" style="font-size:0.72rem;">{{ $m }}</span>
                                @endforeach
                            @else
                                <span class="badge badge-neutral num">{{ $payment->covered_months ?? '—' }}</span>
                            @endif
                        </td>
                        <td class="num" style="color:var(--text-muted); font-size:0.85rem;">
                            {{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('Y/m/d') : '—' }}
                        </td>
                        <td>
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
                        <td>
                            <a href="{{ route('receipts.download', $payment) }}" class="btn btn-sm btn-secondary" title="تحميل التوصيل الرسمي بصيغة PDF">
                                📄 تحميل الوصل
                            </a>
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

@endsection
