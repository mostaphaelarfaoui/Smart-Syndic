@extends('layouts.dashboard')

@section('title', 'الأرشيف وسجل العمليات والرقابة')

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">🗄️ الأرشيف وسجل العمليات والرقابة</h1>
        <p class="page-subtitle">توثيق غير قابل للتعديل لجميع العمليات المالية والإدارية وأرشيف الوثائق لضمان الشفافية</p>
    </div>

    {{-- Tabs Switcher --}}
    <div style="display:flex; align-items:center; gap:0.5rem; background:var(--bg-surface); padding:0.35rem; border:1px solid var(--border-color); border-radius:var(--radius-lg);">
        <a href="{{ route('portal.archives.index', ['tab' => 'audit']) }}" class="btn btn-sm {{ $tab === 'audit' ? 'btn-primary' : 'btn-secondary' }}">
            🛡️ سجل العمليات والتدقيق
        </a>
        <a href="{{ route('portal.archives.index', ['tab' => 'documents']) }}" class="btn btn-sm {{ $tab === 'documents' ? 'btn-primary' : 'btn-secondary' }}">
            📁 أرشيف الوثائق والفواتير
        </a>
    </div>
</div>

@if($tab === 'audit')
    {{-- Audit Logs Section --}}
    <div class="card" style="margin-bottom:1.5rem;">
        <div class="card-body" style="padding:1.25rem;">
            <form method="GET" action="{{ route('portal.archives.index') }}" style="display:flex; align-items:center; gap:1rem; flex-wrap:wrap;">
                <input type="hidden" name="tab" value="audit">

                <select name="action" class="form-select" style="width:auto; min-width:240px;">
                    <option value="">جميع أنواع العمليات</option>
                    <option value="PAYMENT_RECORDED" {{ request('action') == 'PAYMENT_RECORDED' ? 'selected' : '' }}>تسجيل أداء / دفعة</option>
                    <option value="PAYMENT_CONFIRMED" {{ request('action') == 'PAYMENT_CONFIRMED' ? 'selected' : '' }}>تأكيد وصل أداء</option>
                    <option value="EXPENSE_RECORDED" {{ request('action') == 'EXPENSE_RECORDED' ? 'selected' : '' }}>تسجيل مصروف جديد</option>
                    <option value="EXPENSE_DELETED" {{ request('action') == 'EXPENSE_DELETED' ? 'selected' : '' }}>حذف مصروف</option>
                    <option value="RESIDENT_APPROVED" {{ request('action') == 'RESIDENT_APPROVED' ? 'selected' : '' }}>تفعيل حساب ساكن</option>
                    <option value="APARTMENT_VACATED" {{ request('action') == 'APARTMENT_VACATED' ? 'selected' : '' }}>تفريغ شقة وتصفير الحساب</option>
                </select>

                <button type="submit" class="btn btn-primary btn-sm" style="padding:0.75rem 1.25rem;">
                    تطبيق الفلترة
                </button>

                @if(request('action'))
                    <a href="{{ route('portal.archives.index', ['tab' => 'audit']) }}" class="btn btn-secondary btn-sm" style="padding:0.75rem 1.25rem;">
                        إلغاء الفلتر
                    </a>
                @endif
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="min-width:140px;">المسؤول / الفاعل</th>
                        <th style="min-width:130px;">نوع العملية</th>
                        <th style="min-width:250px;">التفاصيل والبيانات</th>
                        <th style="min-width:110px;">عنوان IP</th>
                        <th style="min-width:120px;">التاريخ والوقت</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($auditLogs as $log)
                        <tr>
                            <td>
                                <strong>{{ $log->actor?->full_name ?? 'النظام الآلي' }}</strong>
                                <div style="font-size:0.75rem; color:var(--text-muted);">{{ $log->actor?->email }}</div>
                            </td>
                            <td>
                                <span class="badge badge-info" style="font-size:0.8rem;">
                                    {{ match($log->action) {
                                        'PAYMENT_RECORDED' => '💳 تسجيل أداء',
                                        'PAYMENT_CONFIRMED' => '✓ تأكيد وصل',
                                        'EXPENSE_RECORDED' => '🧾 تسجيل مصروف',
                                        'EXPENSE_DELETED' => '🗑️ حذف مصروف',
                                        'RESIDENT_APPROVED' => '👥 تفعيل ساكن',
                                        'APARTMENT_VACATED' => '🚪 تفريغ شقة',
                                        default => $log->action
                                    } }}
                                </span>
                            </td>
                            <td>
                                @if(is_array($log->metadata) && !empty($log->metadata))
                                    <div style="display:flex; flex-wrap:wrap; gap:0.4rem; max-width:400px;">
                                        @foreach($log->metadata as $k => $v)
                                            <span class="badge badge-neutral num" style="font-size:0.72rem;">
                                                <strong>{{ $k }}:</strong> {{ is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span style="color:var(--text-muted); font-size:0.8rem;">—</span>
                                @endif
                            </td>
                            <td class="num" style="color:var(--text-muted); font-size:0.85rem;">
                                {{ $log->ip_address ?? '—' }}
                            </td>
                            <td class="num" style="font-size:0.85rem;">
                                {{ $log->created_at ? $log->created_at->format('Y/m/d H:i') : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align:center; padding:3rem; color:var(--text-muted);">
                                لا توجد سجلات تدقيق مسجلة حتى الآن.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($auditLogs->hasPages())
            <div class="card-footer">
                {{ $auditLogs->links() }}
            </div>
        @endif
    </div>

@else
    {{-- Documents Archive Section --}}
    <div class="grid-2">
        {{-- Receipts Archive --}}
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <span>📄 أرشيف توصيلات الأداء ({{ count($archivedReceipts) }})</span>
                </div>
            </div>
            <div class="card-body" style="padding:0;">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="min-width:110px;">رقم الوصل</th>
                                <th style="min-width:100px;">الشقة</th>
                                <th style="min-width:120px;">المبلغ</th>
                                <th style="min-width:110px;">التاريخ</th>
                                <th style="text-align:center; min-width:90px;">التحميل</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($archivedReceipts as $rec)
                                <tr>
                                    <td class="num" style="font-weight:700;">#{{ $rec->receipt_number }}</td>
                                    <td>
                                        <strong class="num">شقة {{ $rec->apartment?->unit_number ?? '—' }}</strong>
                                    </td>
                                    <td>
                                        <span class="num" style="font-weight:700; color:var(--color-success);">
                                            {{ number_format($rec->amount_paid, 2) }} د.م
                                        </span>
                                    </td>
                                    <td class="num" style="font-size:0.82rem; color:var(--text-muted);">
                                        {{ $rec->payment_date ? \Carbon\Carbon::parse($rec->payment_date)->format('Y/m/d') : '—' }}
                                    </td>
                                    <td>
                                        <a href="{{ route('receipts.download', $rec) }}" class="btn btn-sm btn-secondary" title="تحميل التوصيل">
                                            📄 PDF
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="text-align:center; padding:2rem; color:var(--text-muted);">
                                        لا توجد وصولات محفوظة بالأرشيف حالياً.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Invoices Archive --}}
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <span>🧾 أرشيف فواتير المصاريف ({{ count($archivedInvoices) }})</span>
                </div>
            </div>
            <div class="card-body" style="padding:0;">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="min-width:140px;">البيان</th>
                                <th style="min-width:120px;">الصنف</th>
                                <th style="min-width:120px;">المبلغ</th>
                                <th style="min-width:110px;">التاريخ</th>
                                <th style="text-align:center; min-width:90px;">المعاينة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($archivedInvoices as $inv)
                                <tr>
                                    <td>
                                        <strong>{{ $inv->title }}</strong>
                                    </td>
                                    <td>
                                        <span class="badge badge-neutral" style="font-size:0.75rem;">
                                            {{ match($inv->category) {
                                                'CLEANING' => '🧹 النظافة',
                                                'ELEVATOR' => '🛗 المصعد',
                                                'SECURITY' => '🔒 الحراسة',
                                                'WATER_ELECTRICITY' => '💡 ماء/كهرباء',
                                                'GARDENING' => '🌿 البستنة',
                                                'MAINTENANCE' => '🔧 الصيانة',
                                                default => '📋 أخرى',
                                            } }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="num" style="font-weight:700; color:var(--color-danger);">
                                            {{ number_format($inv->amount, 2) }} د.م
                                        </span>
                                    </td>
                                    <td class="num" style="font-size:0.82rem; color:var(--text-muted);">
                                        {{ $inv->expense_date ? \Carbon\Carbon::parse($inv->expense_date)->format('Y/m/d') : '—' }}
                                    </td>
                                    <td>
                                        <a href="{{ asset('storage/' . $inv->invoice_proof_path) }}" target="_blank" class="btn btn-sm btn-secondary" title="معاينة الفاتورة المرفوعة">
                                            📎 معاينة
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="text-align:center; padding:2rem; color:var(--text-muted);">
                                        لا توجد فواتير مصاريف مرفوعة في الأرشيف بعد.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endif

@endsection
