@extends('layouts.superadmin')

@section('title', 'سجل الأمان والتدقيق المركزي')

@section('content')

<div class="page-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
    <div>
        <h1 class="page-title">🛡️ سجل الأمان والتدقيق المركزي (Audit Trail)</h1>
        <p class="page-subtitle">تتبع ومراقبة كافة العمليات الحساسة عبر جميع الإقامات السكنية على المنصة بدقة عالية</p>
    </div>
    <div>
        <a href="{{ route('superadmin.audit.index', array_merge(request()->query(), ['export' => 'csv'])) }}" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:0.5rem;">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            <span>تصدير النتائج (CSV)</span>
        </a>
    </div>
</div>

{{-- KPI Summary Cards --}}
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom:1.5rem;">
    <div class="stat-card">
        <div class="stat-icon" style="background:rgba(99, 102, 241, 0.15); color:#6366f1;">📑</div>
        <div>
            <div class="stat-label">إجمالي العمليات المسجلة</div>
            <div class="stat-value num">{{ number_format($totalLogsCount ?? 0) }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:rgba(245, 158, 11, 0.15); color:#f59e0b;">👤</div>
        <div>
            <div class="stat-label">جلسات الدعم الفني (Impersonation)</div>
            <div class="stat-value num" style="color:#f59e0b;">{{ number_format($impersonateCount ?? 0) }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:rgba(16, 185, 129, 0.15); color:#10b981;">⚡</div>
        <div>
            <div class="stat-label">عمليات اليوم</div>
            <div class="stat-value num" style="color:#10b981;">{{ number_format($todayLogsCount ?? 0) }}</div>
        </div>
    </div>
</div>

{{-- Advanced Filters Card --}}
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-body" style="padding:1.25rem;">
        <form method="GET" action="{{ route('superadmin.audit.index') }}">
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; align-items:flex-end;">
                
                {{-- Search query --}}
                <div class="form-group" style="margin:0;">
                    <label class="form-label" style="font-size:0.8rem; margin-bottom:0.35rem;">بحث حر (اسم، بريد، IP)</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-input" placeholder="مثال: 192.168 أو ahmed@..." style="width:100%;">
                </div>

                {{-- Action filter --}}
                <div class="form-group" style="margin:0;">
                    <label class="form-label" style="font-size:0.8rem; margin-bottom:0.35rem;">نوع العملية</label>
                    <select name="action" class="form-select" style="width:100%;">
                        <option value="">جميع أنواع العمليات</option>
                        @foreach($actions as $act)
                            <option value="{{ $act }}" {{ request('action') === $act ? 'selected' : '' }}>{{ $act }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Tenant filter --}}
                <div class="form-group" style="margin:0;">
                    <label class="form-label" style="font-size:0.8rem; margin-bottom:0.35rem;">الإقامة المتأثرة</label>
                    <select name="tenant_id" class="form-select" style="width:100%;">
                        <option value="">كافة الإقامات</option>
                        @foreach($tenants as $ten)
                            <option value="{{ $ten->id }}" {{ request('tenant_id') === $ten->id ? 'selected' : '' }}>{{ $ten->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Date From --}}
                <div class="form-group" style="margin:0;">
                    <label class="form-label" style="font-size:0.8rem; margin-bottom:0.35rem;">من تاريخ</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-input" style="width:100%;">
                </div>

                {{-- Date To --}}
                <div class="form-group" style="margin:0;">
                    <label class="form-label" style="font-size:0.8rem; margin-bottom:0.35rem;">إلى تاريخ</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-input" style="width:100%;">
                </div>

                {{-- Filter & Reset Buttons --}}
                <div style="display:flex; gap:0.5rem;">
                    <button type="submit" class="btn btn-primary" style="flex:1; justify-content:center;">
                        فلترة
                    </button>
                    @if(request()->anyFilled(['search', 'action', 'tenant_id', 'date_from', 'date_to']))
                        <a href="{{ route('superadmin.audit.index') }}" class="btn btn-secondary" title="إلغاء الفلاتر">
                            إعادة ضبط
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Logs Table Card --}}
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="min-width:170px;">المسؤول / المستخدم</th>
                    <th style="min-width:160px;">نوع العملية</th>
                    <th style="min-width:240px;">التفاصيل والبيانات</th>
                    <th style="min-width:140px;">الإقامة</th>
                    <th style="min-width:120px;">عنوان IP</th>
                    <th style="min-width:140px;">التاريخ والوقت</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    @php
                        $badgeClass = 'badge-info';
                        if (str_contains($log->action, 'IMPERSONATE')) {
                            $badgeClass = 'badge-warning';
                        } elseif (str_contains($log->action, 'DELETE') || str_contains($log->action, 'BLOCK') || str_contains($log->action, 'SUSPEND')) {
                            $badgeClass = 'badge-danger';
                        } elseif (str_contains($log->action, 'PAYMENT') || str_contains($log->action, 'APPROVE') || str_contains($log->action, 'CREATE')) {
                            $badgeClass = 'badge-success';
                        }
                    @endphp
                    <tr>
                        <td>
                            <div style="display:flex; align-items:center; gap:0.6rem;">
                                <div style="width:32px; height:32px; border-radius:50%; background:var(--brand-primary); color:#ffffff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.8rem;">
                                    {{ mb_substr($log->actor?->first_name ?? 'N', 0, 1) }}
                                </div>
                                <div>
                                    <strong style="font-size:0.875rem;">{{ $log->actor?->full_name ?? 'النظام الآلي (System)' }}</strong>
                                    <div style="font-size:0.75rem; color:var(--text-muted);">{{ $log->actor?->email ?? 'نظام المنصة' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge {{ $badgeClass }}" style="font-size:0.78rem; font-family:monospace;">
                                {{ $log->action }}
                            </span>
                        </td>
                        <td>
                            @if(is_array($log->metadata) && !empty($log->metadata))
                                <div style="display:flex; flex-wrap:wrap; gap:0.35rem; max-width:420px;">
                                    @foreach(array_slice($log->metadata, 0, 4) as $k => $v)
                                        <span class="badge badge-neutral num" style="font-size:0.72rem; max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="{{ $k }}: {{ is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v }}">
                                            <strong>{{ $k }}:</strong> {{ is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v }}
                                        </span>
                                    @endforeach
                                    @if(count($log->metadata) > 4)
                                        <span class="badge badge-info" style="font-size:0.7rem;">+{{ count($log->metadata) - 4 }} حقول أخرى</span>
                                    @endif
                                </div>
                            @else
                                <span style="color:var(--text-muted); font-size:0.8rem;">—</span>
                            @endif
                        </td>
                        <td>
                            @if($log->targetTenant)
                                <a href="{{ route('superadmin.tenants.index', ['search' => $log->targetTenant->name]) }}" style="font-weight:600; color:var(--brand-primary); font-size:0.85rem; text-decoration:none;">
                                    🏢 {{ $log->targetTenant->name }}
                                </a>
                            @elseif($log->target_tenant_id)
                                <span class="num" style="color:var(--text-secondary); font-size:0.8rem;">
                                    {{ substr($log->target_tenant_id, 0, 8) }}...
                                </span>
                            @else
                                <span class="badge badge-neutral" style="font-size:0.75rem;">منصة مركزية</span>
                            @endif
                        </td>
                        <td class="num" style="color:var(--text-secondary); font-size:0.85rem;">
                            <span>{{ $log->ip_address ?? '—' }}</span>
                        </td>
                        <td class="num" style="font-size:0.85rem;">
                            <div>{{ $log->created_at ? $log->created_at->format('Y/m/d H:i') : '—' }}</div>
                            <div style="font-size:0.72rem; color:var(--text-muted);">{{ $log->created_at ? $log->created_at->diffForHumans() : '' }}</div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center; padding:3rem; color:var(--text-muted);">
                            لا توجد سجلات أمان تطابق شروط البحث والفلترة المحددة.
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
