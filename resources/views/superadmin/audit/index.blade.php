@extends('layouts.superadmin')

@section('title', 'سجل الأمان والتدقيق المركزي')

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">🛡️ سجل الأمان والتدقيق المركزي</h1>
        <p class="page-subtitle">مراقبة شاملة لكافة العمليات الحساسة عبر جميع الإقامات السكنية على المنصة</p>
    </div>
</div>

{{-- Filters Card --}}
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-body" style="padding:1.25rem;">
        <form method="GET" action="{{ route('superadmin.audit.index') }}" style="display:flex; align-items:center; gap:1rem; flex-wrap:wrap;">
            <select name="action" class="form-select" style="width:auto; min-width:240px;">
                <option value="">جميع أنواع العمليات</option>
                <option value="PAYMENT_RECORDED" {{ request('action') == 'PAYMENT_RECORDED' ? 'selected' : '' }}>تسجيل أداء / دفعة</option>
                <option value="PAYMENT_CONFIRMED" {{ request('action') == 'PAYMENT_CONFIRMED' ? 'selected' : '' }}>تأكيد وصل أداء</option>
                <option value="EXPENSE_RECORDED" {{ request('action') == 'EXPENSE_RECORDED' ? 'selected' : '' }}>تسجيل مصروف جديد</option>
                <option value="EXPENSE_DELETED" {{ request('action') == 'EXPENSE_DELETED' ? 'selected' : '' }}>حذف مصروف</option>
                <option value="RESIDENT_APPROVED" {{ request('action') == 'RESIDENT_APPROVED' ? 'selected' : '' }}>تفعيل حساب ساكن</option>
                <option value="APARTMENT_VACATED" {{ request('action') == 'APARTMENT_VACATED' ? 'selected' : '' }}>تفريغ شقة</option>
            </select>

            <button type="submit" class="btn btn-primary btn-sm" style="padding:0.75rem 1.25rem;">
                تطبيق الفلترة
            </button>

            @if(request('action'))
                <a href="{{ route('superadmin.audit.index') }}" class="btn btn-secondary btn-sm" style="padding:0.75rem 1.25rem;">
                    إلغاء الفلتر
                </a>
            @endif
        </form>
    </div>
</div>

{{-- Logs Table Card --}}
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="min-width:160px;">المسؤول / المستخدم</th>
                    <th style="min-width:130px;">نوع العملية</th>
                    <th style="min-width:250px;">التفاصيل والبيانات</th>
                    <th style="min-width:120px;">معرف الإقامة</th>
                    <th style="min-width:110px;">عنوان IP</th>
                    <th style="min-width:130px;">التاريخ والوقت</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td>
                            <strong>{{ $log->actor?->full_name ?? 'النظام الآلي' }}</strong>
                            <div style="font-size:0.75rem; color:var(--text-muted);">{{ $log->actor?->email }}</div>
                        </td>
                        <td>
                            <span class="badge badge-info" style="font-size:0.8rem;">
                                {{ $log->action }}
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
                        <td class="num" style="color:var(--text-secondary); font-size:0.82rem;">
                            {{ $log->target_tenant_id ? substr($log->target_tenant_id, 0, 8) . '...' : '—' }}
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
                        <td colspan="6" style="text-align:center; padding:3rem; color:var(--text-muted);">
                            لا توجد سجلات أمان مسجلة.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
        <div class="card-footer">
            {{ $logs->links() }}
        </div>
    @endif
</div>

@endsection
