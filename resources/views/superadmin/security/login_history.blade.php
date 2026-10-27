@extends('layouts.superadmin')

@section('title', 'سجل جلسات وتسجيل الدخول')

@section('content')

<div class="page-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
    <div>
        <h1 class="page-title">🔑 سجل جلسات ومحاولات تسجيل الدخول</h1>
        <p class="page-subtitle">مراقبة فورية لجميع محاولات الدخول الناجحة والفاشلة وعمليات الرصد الأمني لحماية الحسابات</p>
    </div>
    <div style="display:flex; gap:0.5rem;">
        <a href="{{ route('superadmin.security.index') }}" class="btn btn-secondary">
            🛡️ جدار الحظر والحوادث &larr;
        </a>
    </div>
</div>

{{-- KPI Summary Cards --}}
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom:1.5rem;">
    <div class="stat-card">
        <div class="stat-icon" style="background:rgba(16, 185, 129, 0.15); color:#10b981;">✓</div>
        <div>
            <div class="stat-label">جلسات الدخول الناجحة (اليوم)</div>
            <div class="stat-value num" style="color:#10b981;">{{ number_format($todaySuccessCount) }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:rgba(245, 158, 11, 0.15); color:#f59e0b;">⚠️</div>
        <div>
            <div class="stat-label">محاولات خاطئة (اليوم)</div>
            <div class="stat-value num" style="color:#f59e0b;">{{ number_format($todayFailedCount) }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:rgba(239, 68, 68, 0.15); color:#ef4444;">🔒</div>
        <div>
            <div class="stat-label">حسابات محظورة حالياً</div>
            <div class="stat-value num" style="color:#ef4444;">{{ number_format($blockedUsersCount) }}</div>
        </div>
    </div>
</div>

{{-- Advanced Filters Card --}}
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-body" style="padding:1.25rem;">
        <form method="GET" action="{{ route('superadmin.login-history.index') }}">
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; align-items:flex-end;">
                
                {{-- Search query --}}
                <div class="form-group" style="margin:0;">
                    <label class="form-label" style="font-size:0.8rem; margin-bottom:0.35rem;">بحث بالبريد أو IP</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-input" placeholder="مثال: admin@... أو 192.168" style="width:100%;">
                </div>

                {{-- Status filter --}}
                <div class="form-group" style="margin:0;">
                    <label class="form-label" style="font-size:0.8rem; margin-bottom:0.35rem;">حالة المحاولة</label>
                    <select name="status" class="form-select" style="width:100%;">
                        <option value="ALL" {{ request('status') === 'ALL' ? 'selected' : '' }}>جميع الحالات</option>
                        <option value="SUCCESS" {{ request('status') === 'SUCCESS' ? 'selected' : '' }}>دخول ناجح فقط ✓</option>
                        <option value="FAILED" {{ request('status') === 'FAILED' ? 'selected' : '' }}>محاولة فاشلة (كلمة سر خاطئة) ⚠️</option>
                        <option value="BLOCKED" {{ request('status') === 'BLOCKED' ? 'selected' : '' }}>حساب مقفل أو محظور 🔒</option>
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

                {{-- Submit Buttons --}}
                <div style="display:flex; gap:0.5rem;">
                    <button type="submit" class="btn btn-primary" style="flex:1; justify-content:center;">
                        فلترة
                    </button>
                    @if(request()->anyFilled(['search', 'status', 'date_from', 'date_to']))
                        <a href="{{ route('superadmin.login-history.index') }}" class="btn btn-secondary" title="إلغاء الفلاتر">
                            إلغاء
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Records Table Card --}}
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="min-width:140px;">الحالة والنتيجة</th>
                    <th style="min-width:180px;">البريد والمستخدم</th>
                    <th style="min-width:140px;">الإقامة التابعة</th>
                    <th style="min-width:120px;">عنوان IP</th>
                    <th style="min-width:200px;">المتصفح والنظام</th>
                    <th style="min-width:130px;">التوقيت</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $rec)
                    <tr>
                        <td>
                            <span class="badge {{ $rec->status_badge }}" style="font-size:0.8rem; display:inline-flex; align-items:center; gap:0.3rem;">
                                @if($rec->type === 'SUCCESS')
                                    <span>✓</span>
                                @elseif($rec->type === 'BLOCKED')
                                    <span>🔒</span>
                                @else
                                    <span>⚠️</span>
                                @endif
                                <span>{{ $rec->title }}</span>
                            </span>
                        </td>
                        <td>
                            <div style="font-weight:700; font-size:0.875rem;">{{ $rec->email }}</div>
                            <div style="font-size:0.75rem; color:var(--text-muted);">
                                {{ $rec->user_name }} • <span class="badge badge-neutral" style="font-size:0.65rem;">{{ $rec->role }}</span>
                            </div>
                        </td>
                        <td>
                            <span style="font-size:0.85rem; color:var(--text-secondary);">
                                {{ $rec->tenant_name }}
                            </span>
                        </td>
                        <td class="num" style="font-size:0.85rem;">
                            <span>{{ $rec->ip_address ?? '—' }}</span>
                        </td>
                        <td style="font-size:0.78rem; color:var(--text-muted); max-width:260px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="{{ $rec->user_agent }}">
                            {{ $rec->user_agent ?? '—' }}
                        </td>
                        <td class="num" style="font-size:0.85rem;">
                            <div>{{ $rec->created_at ? $rec->created_at->format('Y/m/d H:i') : '—' }}</div>
                            <div style="font-size:0.72rem; color:var(--text-muted);">{{ $rec->created_at ? $rec->created_at->diffForHumans() : '' }}</div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center; padding:3rem; color:var(--text-muted);">
                            لا توجد جلسات تسجيل دخول مطابقة للبحث المحدد.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
