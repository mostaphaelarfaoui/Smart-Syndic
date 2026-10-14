@extends('layouts.dashboard')

@section('title', 'المصاريف ونفقات الإقامة')

@section('content')

<div class="page-header" x-data="{ addModal: false }">
    <div>
        <h1 class="page-title">المصاريف ونفقات الإقامة</h1>
        <p class="page-subtitle">تسجيل فواتير الصيانة والنظافة والخدمات وتتبع رصيد الخزينة</p>
    </div>

    <div class="page-actions">
        <button type="button" class="btn btn-danger" @click="addModal = true">
            <span>+ تسجيل مصروف جديد</span>
        </button>
    </div>

    {{-- Modal: Add Expense --}}
    <div class="modal-overlay" x-show="addModal" x-cloak>
        <div class="modal-dialog" @click.away="addModal = false">
            <div class="modal-header">
                <h3 class="modal-title">تسجيل مصروف أو فاتورة جديدة</h3>
                <button type="button" class="icon-btn" @click="addModal = false">✕</button>
            </div>
            <form method="POST" action="{{ route('portal.expenses.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">عنوان المصروف أو الخدمة</label>
                        <input type="text" name="title" class="form-control" placeholder="مثال: إصلاح عطل إنارة المدخل" required>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                        <div class="form-group">
                            <label class="form-label">المبلغ (درهم)</label>
                            <input type="number" step="0.01" name="amount" class="form-control" placeholder="350.00" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">الصنف / النوع</label>
                            <select name="category" class="form-select" required>
                                <option value="CLEANING">🧹 النظافة ومواد التطهير</option>
                                <option value="ELEVATOR">🛗 صيانة المصعد</option>
                                <option value="SECURITY">🔒 الحراسة والأمن</option>
                                <option value="WATER_ELECTRICITY">💡 فاتورة الماء والكهرباء</option>
                                <option value="GARDENING">🌿 العناية بالحديقة</option>
                                <option value="MAINTENANCE">🔧 صيانة عامة وإصلاحات</option>
                                <option value="OTHER">📋 مصاريف إدارية أخرى</option>
                            </select>
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                        <div class="form-group">
                            <label class="form-label">طريقة الأداء والخصم</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="CASH">💵 نقداً (يخصم من الصندوق)</option>
                                <option value="BANK_TRANSFER">🏦 تحويل (يخصم من البنك)</option>
                                <option value="CHEQUE">📝 شيك بنكي</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">تاريخ الصرف</label>
                            <input type="date" name="expense_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">صورة الفاتورة أو وصل الشراء (اختياري)</label>
                        <input type="file" name="invoice_proof" class="form-control" accept="image/*,application/pdf">
                        <small style="color:var(--text-muted); font-size:0.75rem;">يمكنك إرفاق صورة للفاتورة لحفظها في الأرشيف المالي</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="addModal = false">إلغاء</button>
                    <button type="submit" class="btn btn-danger">تسجيل وخصم المصروف</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- KPI Summary Row --}}
<div class="kpi-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); margin-bottom:1.5rem;">
    <div class="kpi-card" style="padding:1.25rem;">
        <div class="kpi-card-header" style="margin-bottom:0.5rem;">
            <span class="kpi-label">مصاريف الشهر الحالي</span>
            <div class="kpi-icon-box rose" style="width:36px; height:36px;">📉</div>
        </div>
        <div class="kpi-value num" style="font-size:1.75rem; margin-bottom:0; color:var(--color-danger);">
            {{ number_format($thisMonthExpenses, 2) }} <span class="unit">د.م</span>
        </div>
    </div>

    <div class="kpi-card" style="padding:1.25rem;">
        <div class="kpi-card-header" style="margin-bottom:0.5rem;">
            <span class="kpi-label">إجمالي نفقات المنظمة</span>
            <div class="kpi-icon-box amber" style="width:36px; height:36px;">🧾</div>
        </div>
        <div class="kpi-value num" style="font-size:1.75rem; margin-bottom:0;">
            {{ number_format($totalExpenses, 2) }} <span class="unit">د.م</span>
        </div>
    </div>
</div>

{{-- Filters Card --}}
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-body" style="padding:1.25rem;">
        <form method="GET" action="{{ route('portal.expenses.index') }}" style="display:flex; align-items:center; gap:1rem; flex-wrap:wrap;">
            <div class="search-box" style="flex:1;">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="ابحث باسم المصروف...">
                <span class="search-icon">🔍</span>
            </div>

            <select name="category" class="form-select" style="width:auto; min-width:180px;">
                <option value="">جميع الأصناف</option>
                <option value="CLEANING" {{ request('category') == 'CLEANING' ? 'selected' : '' }}>النظافة</option>
                <option value="ELEVATOR" {{ request('category') == 'ELEVATOR' ? 'selected' : '' }}>المصعد</option>
                <option value="SECURITY" {{ request('category') == 'SECURITY' ? 'selected' : '' }}>الحراسة</option>
                <option value="WATER_ELECTRICITY" {{ request('category') == 'WATER_ELECTRICITY' ? 'selected' : '' }}>الماء والكهرباء</option>
                <option value="GARDENING" {{ request('category') == 'GARDENING' ? 'selected' : '' }}>البستنة</option>
                <option value="MAINTENANCE" {{ request('category') == 'MAINTENANCE' ? 'selected' : '' }}>الصيانة</option>
                <option value="OTHER" {{ request('category') == 'OTHER' ? 'selected' : '' }}>أخرى</option>
            </select>

            <select name="payment_method" class="form-select" style="width:auto; min-width:160px;">
                <option value="">طريقة الصرف</option>
                <option value="CASH" {{ request('payment_method') == 'CASH' ? 'selected' : '' }}>نقداً (صندوق)</option>
                <option value="BANK_TRANSFER" {{ request('payment_method') == 'BANK_TRANSFER' ? 'selected' : '' }}>تحويل (بنك)</option>
                <option value="CHEQUE" {{ request('payment_method') == 'CHEQUE' ? 'selected' : '' }}>شيك</option>
            </select>

            <input type="month" name="month" value="{{ request('month') }}" class="form-control" style="width:auto; min-width:160px;">

            <button type="submit" class="btn btn-primary btn-sm" style="padding:0.75rem 1.25rem;">
                تطبيق الفلترة
            </button>

            @if(request()->hasAny(['search', 'category', 'payment_method', 'month']))
                <a href="{{ route('portal.expenses.index') }}" class="btn btn-secondary btn-sm" style="padding:0.75rem 1.25rem;">
                    إلغاء الفلاتر
                </a>
            @endif
        </form>
    </div>
</div>

{{-- Expenses Table Card --}}
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="min-width:160px;">بيان المصروف</th>
                    <th style="min-width:120px;">الصنف</th>
                    <th style="min-width:130px;">المبلغ المصروف</th>
                    <th style="min-width:110px;">طريقة الأداء</th>
                    <th style="min-width:110px;">تاريخ الصرف</th>
                    <th style="text-align:center; min-width:120px;">الفاتورة / الإثبات</th>
                    <th style="text-align:center; min-width:90px;">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenses as $expense)
                    <tr>
                        <td>
                            <strong style="font-size:0.95rem;">{{ $expense->title }}</strong>
                        </td>
                        <td>
                            <span class="badge badge-neutral">
                                {{ match($expense->category) {
                                    'CLEANING' => '🧹 النظافة',
                                    'ELEVATOR' => '🛗 المصعد',
                                    'SECURITY' => '🔒 الحراسة',
                                    'WATER_ELECTRICITY' => '💡 ماء وكهرباء',
                                    'GARDENING' => '🌿 البستنة',
                                    'MAINTENANCE' => '🔧 الصيانة',
                                    default => '📋 أخرى',
                                } }}
                            </span>
                        </td>
                        <td>
                            <span class="num" style="font-weight:800; color:var(--color-danger); font-size:1rem;">
                                {{ number_format($expense->amount, 2) }} د.م
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-info">
                                {{ match($expense->payment_method) {
                                    'CASH' => '💵 الصندوق',
                                    'BANK_TRANSFER' => '🏦 البنك',
                                    'CHEQUE' => '📝 شيك',
                                    default => $expense->payment_method
                                } }}
                            </span>
                        </td>
                        <td class="num" style="color:var(--text-muted); font-size:0.85rem;">
                            {{ $expense->expense_date ? \Carbon\Carbon::parse($expense->expense_date)->format('Y/m/d') : '—' }}
                        </td>
                        <td>
                            @if($expense->invoice_proof_path)
                                <a href="{{ asset('storage/' . $expense->invoice_proof_path) }}" target="_blank" class="btn btn-sm btn-secondary" title="معاينة الفاتورة">
                                    📎 الفاتورة
                                </a>
                            @else
                                <span style="color:var(--text-muted); font-size:0.82rem;">—</span>
                            @endif
                        </td>
                            <form method="POST" action="{{ route('portal.expenses.destroy', $expense->id) }}"
                                  data-confirm="هل تؤكد حذف هذا المصروف المسجل بمبلغ {{ number_format($expense->amount, 2) }} درهم؟"
                                  data-confirm-title="تأكيد حذف المصروف"
                                  data-confirm-type="danger"
                                  data-confirm-btn="حذف المصروف">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-secondary" style="color:var(--color-danger);" title="حذف">
                                    🗑️
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center; padding:3rem; color:var(--text-muted);">
                            لا توجد مصاريف مسجلة مطابقة للمعايير.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($expenses->hasPages())
        <div class="card-footer">
            {{ $expenses->links() }}
        </div>
    @endif
</div>

@endsection
