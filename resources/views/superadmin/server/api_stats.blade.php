@extends('layouts.superadmin')

@section('title', 'إحصائيات الـ API وتطبيق الهاتف')

@section('content')

<div class="page-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
    <div>
        <h1 class="page-title">📱 إحصائيات الـ API وتطبيق الهاتف</h1>
        <p class="page-subtitle">مراقبة الجلسات المتصلة عبر تطبيق الهاتف (Sanctum Tokens)، الأجهزة المسجلة، وصحة نقاط النهاية</p>
    </div>
    <div>
        <a href="{{ route('superadmin.server.index') }}" class="btn btn-secondary">
            🖥️ صحة السيرفر والخوادم &larr;
        </a>
    </div>
</div>

{{-- KPI Summary Cards --}}
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom:1.5rem;">
    <div class="stat-card">
        <div class="stat-icon" style="background:rgba(99, 102, 241, 0.15); color:#6366f1;">🔑</div>
        <div>
            <div class="stat-label">إجمالي جلسات الـ API النشطة</div>
            <div class="stat-value num">{{ number_format($totalTokensCount) }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:rgba(16, 185, 129, 0.15); color:#10b981;">⚡</div>
        <div>
            <div class="stat-label">أجهزة نشطة خلال آخر 24 ساعة</div>
            <div class="stat-value num" style="color:#10b981;">{{ number_format($active24hCount) }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:rgba(245, 158, 11, 0.15); color:#f59e0b;">🔔</div>
        <div>
            <div class="stat-label">أجهزة تستقبل الإشعارات (FCM)</div>
            <div class="stat-value num" style="color:#f59e0b;">{{ number_format($fcmDevicesCount) }}</div>
        </div>
    </div>
</div>

{{-- API Infrastructure Status & Information --}}
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-header">
        <h3 class="card-title">🌐 بنية الـ API التحتية (REST API v1)</h3>
        <p class="card-subtitle">المعايير التقنية وضوابط الحماية لربط التطبيق المحمول</p>
    </div>
    <div class="card-body" style="padding:1.25rem;">
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:1rem; font-size:0.875rem;">
            <div style="background:var(--bg-main); padding:0.85rem 1rem; border-radius:var(--radius-md); border:1px solid var(--border-color);">
                <div style="color:var(--text-muted); font-size:0.75rem;">مسار الـ API الأساسي (Base URL)</div>
                <div style="font-family:monospace; font-weight:700; color:var(--brand-primary); margin-top:0.25rem;">/api/v1</div>
            </div>
            <div style="background:var(--bg-main); padding:0.85rem 1rem; border-radius:var(--radius-md); border:1px solid var(--border-color);">
                <div style="color:var(--text-muted); font-size:0.75rem;">نظام المصادقة والتفويض</div>
                <div style="font-weight:700; color:#10b981; margin-top:0.25rem;">Laravel Sanctum (Bearer Token)</div>
            </div>
            <div style="background:var(--bg-main); padding:0.85rem 1rem; border-radius:var(--radius-md); border:1px solid var(--border-color);">
                <div style="color:var(--text-muted); font-size:0.75rem;">حماية المعدل (Rate Limiting)</div>
                <div style="font-weight:700; color:#f59e0b; margin-top:0.25rem;">Throttle: 60 req/min (Auth: 5/min)</div>
            </div>
            <div style="background:var(--bg-main); padding:0.85rem 1rem; border-radius:var(--radius-md); border:1px solid var(--border-color);">
                <div style="color:var(--text-muted); font-size:0.75rem;">دعم اللغات (Localization)</div>
                <div style="font-weight:700; margin-top:0.25rem;">العربية (ar) / الفرنسية (fr)</div>
            </div>
        </div>
    </div>
</div>

{{-- Active Mobile Sessions Filter & Table --}}
<div class="card">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
        <div>
            <h3 class="card-title">📱 الجلسات والأجهزة المتصلة بالتطبيق</h3>
            <p class="card-subtitle">إدارة مفاتيح الدخول وإلغاء جلسات الأجهزة المفقودة أو المشبوهة</p>
        </div>
        <div>
            <form method="GET" action="{{ route('superadmin.api-stats.index') }}" style="display:flex; gap:0.5rem;">
                <input type="text" name="search" value="{{ request('search') }}" class="form-input form-input-sm" placeholder="بحث بالمستخدم أو الجهاز..." style="width:220px;">
                <button type="submit" class="btn btn-secondary btn-sm">بحث</button>
                @if(request('search'))
                    <a href="{{ route('superadmin.api-stats.index') }}" class="btn btn-secondary btn-sm">إلغاء</a>
                @endif
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>المستخدم</th>
                    <th>الإقامة التابعة</th>
                    <th>اسم الجهاز / الجلسة</th>
                    <th>آخر نشاط واستخدام</th>
                    <th>تاريخ تسجيل الدخول</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tokens as $tok)
                    @php
                        $user = $tok->tokenable;
                    @endphp
                    <tr>
                        <td>
                            @if($user)
                                <div style="font-weight:700; font-size:0.875rem;">{{ $user->full_name }}</div>
                                <div style="font-size:0.75rem; color:var(--text-muted);">
                                    {{ $user->email }} • <span class="badge badge-neutral" style="font-size:0.65rem;">{{ $user->role }}</span>
                                </div>
                            @else
                                <span style="color:var(--text-muted);">مستخدم محذوف</span>
                            @endif
                        </td>
                        <td>
                            <span style="font-size:0.85rem; color:var(--text-secondary);">
                                {{ $user?->tenant?->name ?? '—' }}
                            </span>
                        </td>
                        <td>
                            <div style="display:flex; align-items:center; gap:0.4rem;">
                                <span>📱</span>
                                <code style="font-size:0.85rem; color:var(--brand-primary);">{{ $tok->name }}</code>
                            </div>
                        </td>
                        <td class="num" style="font-size:0.85rem;">
                            @if($tok->last_used_at)
                                <div>{{ $tok->last_used_at->format('Y/m/d H:i') }}</div>
                                <div style="font-size:0.72rem; color:#10b981;">{{ $tok->last_used_at->diffForHumans() }}</div>
                            @else
                                <span style="color:var(--text-muted); font-size:0.8rem;">لم يُستخدم بعد</span>
                            @endif
                        </td>
                        <td class="num" style="font-size:0.85rem;">
                            {{ $tok->created_at ? $tok->created_at->format('Y/m/d H:i') : '—' }}
                        </td>
                        <td>
                            <form method="POST" action="{{ route('superadmin.api-stats.revoke-token', $tok->id) }}"
                                  data-confirm="هل تؤكد إلغاء هذه الجلسة فوراً؟ سيتم تسجيل خروج التطبيق من جهاز المستخدم."
                                  data-confirm-title="إلغاء جلسة الجهاز"
                                  data-confirm-type="danger"
                                  data-confirm-btn="إلغاء الجلسة">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-secondary btn-sm" style="color:#ef4444; font-size:0.75rem;">
                                    🔒 إلغاء الجلسة
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center; padding:3rem; color:var(--text-muted);">
                            لا توجد جلسات API مسجلة حالياً.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($tokens->hasPages())
        <div class="card-footer">
            {{ $tokens->links() }}
        </div>
    @endif
</div>

@endsection
