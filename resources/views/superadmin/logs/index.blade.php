@extends('layouts.superadmin')

@section('title', 'سجل الأخطاء الحي — Logs Viewer')

@section('content')
<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem; margin-bottom:1.5rem;">
    <div>
        <div class="page-pretitle" style="color:#6366f1; font-weight:700; font-size:0.85rem;">LIVE SYSTEM LOG MONITOR</div>
        <h1 class="page-title" style="margin:0.25rem 0 0.5rem 0; font-size:1.85rem;">سجل أخطاء السيرفر والتطبيق الحي (Laravel Logs)</h1>
        <p style="color:var(--text-muted); margin:0; font-size:0.95rem;">
            استعراض فوري لملف <code style="color:#6366f1; background:rgba(99,102,241,0.1); padding:0.1rem 0.4rem; border-radius:4px;">storage/logs/laravel.log</code> وتتبع الاستثناءات البرمجية وأخطاء النظام.
        </p>
    </div>

    <div style="display:flex; gap:0.75rem; align-items:center; flex-wrap:wrap;">
        <span class="badge badge-info" style="font-size:0.85rem; padding:0.4rem 0.8rem;">
            حجم الملف: <strong>{{ $fileSize }}</strong>
        </span>

        <a href="{{ route('superadmin.logs.download') }}" class="btn btn-secondary">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            <span>تحميل ملف السجل</span>
        </a>

        <form method="POST" action="{{ route('superadmin.logs.clear') }}" onsubmit="return confirm('هل أنت متأكد من رغبتك في تفريغ ومسح سجل الأخطاء؟')">
            @csrf
            <button type="submit" class="btn btn-danger" style="border:none;">
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                <span>مسح السجل</span>
            </button>
        </form>
    </div>
</div>

{{-- Filters Strip --}}
<div class="card" style="margin-bottom:1.5rem; padding:1rem 1.25rem;">
    <form method="GET" action="{{ route('superadmin.logs.index') }}" style="display:flex; gap:1rem; align-items:center; flex-wrap:wrap;">
        <div style="flex:1; min-width:260px;">
            <input type="text" name="search" class="form-control" placeholder="ابحث في نص الخطأ أو مسار الملف..." value="{{ request('search') }}">
        </div>

        <div style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
            <a href="{{ route('superadmin.logs.index') }}" class="btn btn-sm {{ empty(request('level')) ? 'btn-primary' : 'btn-secondary' }}" style="font-size:0.8rem;">
                الكل
            </a>
            <a href="{{ route('superadmin.logs.index', ['level' => 'ERROR']) }}" class="btn btn-sm {{ request('level') === 'ERROR' ? 'btn-danger' : 'btn-secondary' }}" style="font-size:0.8rem;">
                🔴 أخطاء (ERROR)
            </a>
            <a href="{{ route('superadmin.logs.index', ['level' => 'CRITICAL']) }}" class="btn btn-sm {{ request('level') === 'CRITICAL' ? 'btn-danger' : 'btn-secondary' }}" style="font-size:0.8rem;">
                🚨 حرج (CRITICAL)
            </a>
            <a href="{{ route('superadmin.logs.index', ['level' => 'WARNING']) }}" class="btn btn-sm {{ request('level') === 'WARNING' ? 'btn-warning' : 'btn-secondary' }}" style="font-size:0.8rem;">
                ⚠️ تحذيرات (WARNING)
            </a>
            <a href="{{ route('superadmin.logs.index', ['level' => 'INFO']) }}" class="btn btn-sm {{ request('level') === 'INFO' ? 'btn-info' : 'btn-secondary' }}" style="font-size:0.8rem;">
                ℹ️ معلومات (INFO)
            </a>
        </div>
    </form>
</div>

{{-- Log Entries List --}}
<div style="display:flex; flex-direction:column; gap:1rem;">
    @forelse($logs as $index => $log)
        <div class="card" style="border-right:4px solid {{ in_array($log['level'], ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY']) ? '#ef4444' : ($log['level'] === 'WARNING' ? '#f59e0b' : '#3b82f6') }};" x-data="{ expanded: false }">
            <div class="card-body" style="padding:1rem 1.25rem;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:0.75rem; margin-bottom:0.5rem;">
                    <div style="display:flex; align-items:center; gap:0.6rem; flex-wrap:wrap;">
                        <span class="badge {{ in_array($log['level'], ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY']) ? 'badge-danger' : ($log['level'] === 'WARNING' ? 'badge-warning' : 'badge-info') }}" style="font-size:0.75rem; font-family:monospace;">
                            {{ $log['level'] }}
                        </span>
                        <span style="font-size:0.8rem; color:var(--text-muted); font-family:monospace;">
                            [{{ $log['timestamp'] }}]
                        </span>
                        <span class="badge badge-info" style="font-size:0.7rem;">
                            {{ $log['environment'] }}
                        </span>
                    </div>

                    <button type="button" class="btn btn-sm btn-secondary" @click="expanded = !expanded" style="font-size:0.75rem; padding:0.2rem 0.6rem;">
                        <span x-text="expanded ? 'إخفاء التفاصيل ▲' : 'عرض التفاصيل والـ Stack Trace ▼'"></span>
                    </button>
                </div>

                <div style="font-weight:700; font-size:0.95rem; color:var(--text-primary); margin-bottom:0.5rem; word-break:break-word;">
                    {{ $log['title'] }}
                </div>

                {{-- Full details and stack trace in terminal code block --}}
                <div x-show="expanded" style="display:none; margin-top:1rem;">
                    <pre style="background:#090d16; color:#94a3b8; padding:1rem; border-radius:var(--radius-md); font-family:Consolas, 'Courier New', monospace; font-size:0.8rem; line-height:1.6; overflow-x:auto; max-height:400px; white-space:pre-wrap; border:1px solid rgba(255,255,255,0.08);">{{ $log['body'] }}</pre>
                </div>
            </div>
        </div>
    @empty
        <div class="card" style="text-align:center; padding:3rem;">
            <div style="font-size:2.5rem; margin-bottom:0.75rem;">📋✨</div>
            <h3 style="margin:0 0 0.5rem 0; font-size:1.2rem;">لا توجد أخطاء مسجلة</h3>
            <p style="color:var(--text-muted); margin:0; font-size:0.9rem;">
                السجل خالٍ من أية أخطاء تطابق معايير البحث والفلترة.
            </p>
        </div>
    @endforelse
</div>

@endsection
