@extends('layouts.superadmin')

@section('title', 'صحة السيرفر وقاعدة البيانات')

@section('content')
<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem; margin-bottom:1.5rem;">
    <div>
        <div class="page-pretitle" style="color:#6366f1; font-weight:700; font-size:0.85rem;">SERVER & INFRASTRUCTURE MONITOR</div>
        <h1 class="page-title" style="margin:0.25rem 0 0.5rem 0; font-size:1.85rem;">صحة السيرفر وقاعدة البيانات والبيئة التشغيلية</h1>
        <p style="color:var(--text-muted); margin:0; font-size:0.95rem;">
            مراقبة شاملة لموارد النظام، مساحة التخزين، استجابة قاعدة بيانات SQLite، وأدوات الصيانة الفورية.
        </p>
    </div>

        <form method="POST" action="{{ route('superadmin.server.vacuum') }}"
              data-confirm="هل ترغب في تشغيل أمر VACUUM لتفريغ وضغط ملف قاعدة البيانات وتحسين الفهارس؟"
              data-confirm-title="تحسين قاعدة البيانات"
              data-confirm-type="info"
              data-confirm-btn="بدء التحسين الفوري">
            @csrf
            <button type="submit" class="btn btn-secondary" style="border-color:#6366f1; color:#6366f1;">
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span>ضغط وتحسين قاعدة البيانات (VACUUM)</span>
            </button>
        </form>

        <a href="{{ route('superadmin.server.backup') }}" class="btn btn-primary" style="background:linear-gradient(135deg, #10b981 0%, #059669 100%); border:none;">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            <span>تحميل نسخة احتياطية (SQLite)</span>
        </a>
    </div>
</div>

{{-- Top Gauges --}}
<div class="kpi-grid" style="margin-bottom:1.5rem;">
    <div class="kpi-card" style="border-top:3px solid #10b981;">
        <div class="kpi-header">
            <span class="kpi-label">زمن استجابة قاعدة البيانات (Latency)</span>
            <span class="badge badge-success">{{ $health['db_status'] }}</span>
        </div>
        <div class="kpi-value">{{ $health['db_latency_ms'] }} <span style="font-size:0.9rem; font-weight:400; color:var(--text-muted);">ms</span></div>
        <div class="kpi-sub">
            فحص استعلام حي (SELECT 1)
        </div>
    </div>

    <div class="kpi-card" style="border-top:3px solid #6366f1;">
        <div class="kpi-header">
            <span class="kpi-label">حجم قاعدة بيانات SQLite</span>
            <span class="kpi-icon" style="background:rgba(99,102,241,0.12); color:#6366f1;">💾</span>
        </div>
        <div class="kpi-value">{{ $health['db_size_mb'] }} <span style="font-size:0.9rem; font-weight:400; color:var(--text-muted);">MB</span></div>
        <div class="kpi-sub">
            فحص السلامة الهيكلية: <strong>{{ $health['db_integrity'] }}</strong>
        </div>
    </div>

    <div class="kpi-card" style="border-top:3px solid #f59e0b;">
        <div class="kpi-header">
            <span class="kpi-label">استهلاك الذاكرة العشوائية (RAM)</span>
            <span class="kpi-icon" style="background:rgba(245,158,11,0.12); color:#f59e0b;">⚡</span>
        </div>
        <div class="kpi-value">{{ $health['memory_usage_mb'] }} <span style="font-size:0.9rem; font-weight:400; color:var(--text-muted);">MB</span></div>
        <div class="kpi-sub">
            أعلى استهلاك مسجل: {{ $health['memory_peak_mb'] }} MB (الحد: {{ $health['memory_limit'] }})
        </div>
    </div>

    <div class="kpi-card" style="border-top:3px solid #0ea5e9;">
        <div class="kpi-header">
            <span class="kpi-label">مساحة القرص الصلب (Storage)</span>
            <span class="badge badge-info">{{ $health['disk_percentage'] }}% مستهلك</span>
        </div>
        <div class="kpi-value">{{ $health['disk_free_gb'] }} <span style="font-size:0.9rem; font-weight:400; color:var(--text-muted);">GB حر</span></div>
        <div class="kpi-sub">
            إجمالي المساحة: {{ $health['disk_total_gb'] }} GB
        </div>
    </div>
</div>

{{-- Storage Bar & System Environment Grid --}}
<div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:1.5rem;">
    {{-- Storage Visualizer --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">💽 مساحة التخزين والأقراص</h3>
            <p class="card-subtitle">توزيع السعة التخزينية في الخادم</p>
        </div>
        <div class="card-body">
            <div style="margin-bottom:1rem;">
                <div style="display:flex; justify-content:space-between; margin-bottom:0.5rem; font-size:0.9rem;">
                    <span>المساحة المستهلكة ({{ $health['disk_used_gb'] }} GB)</span>
                    <span style="font-weight:700;">{{ $health['disk_percentage'] }}%</span>
                </div>
                <div style="height:12px; background:var(--border-color); border-radius:9999px; overflow:hidden;">
                    <div style="height:100%; width:{{ $health['disk_percentage'] }}%; background:linear-gradient(90deg, #6366f1, #ef4444); border-radius:9999px;"></div>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-top:1.5rem;">
                <div style="background:var(--bg-main); padding:1rem; border-radius:var(--radius-md); border:1px solid var(--border-color);">
                    <div style="font-size:0.8rem; color:var(--text-muted);">المساحة المتبقية</div>
                    <div style="font-size:1.3rem; font-weight:800; color:#10b981;">{{ $health['disk_free_gb'] }} GB</div>
                </div>
                <div style="background:var(--bg-main); padding:1rem; border-radius:var(--radius-md); border:1px solid var(--border-color);">
                    <div style="font-size:0.8rem; color:var(--text-muted);">السعة الكلية</div>
                    <div style="font-size:1.3rem; font-weight:800; color:var(--text-primary);">{{ $health['disk_total_gb'] }} GB</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Environment Specs --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">⚙️ مواصفات البيئة التشغيلية (Runtime)</h3>
            <p class="card-subtitle">إصدارات البرمجيات وحزم السيرفر</p>
        </div>
        <div class="card-body" style="padding:0;">
            <div class="table-responsive">
                <table class="data-table">
                    <tbody>
                        <tr>
                            <td style="font-weight:700; width:40%;">إصدار PHP</td>
                            <td style="font-family:monospace; color:#6366f1;">{{ $health['php_version'] }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight:700;">إطار عمل Laravel</td>
                            <td style="font-family:monospace; color:#ef4444;">v{{ $health['laravel_version'] }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight:700;">نظام التشغيل (OS)</td>
                            <td>{{ $health['os'] }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight:700;">أقصى حجم لرفع الملفات</td>
                            <td>{{ $health['upload_max'] }} (POST: {{ $health['post_max'] }})</td>
                        </tr>
                        <tr>
                            <td style="font-weight:700;">زمن التنفيذ الأقصى (Timeout)</td>
                            <td>{{ $health['max_execution_time'] }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight:700;">محرك الجلسات والكاش</td>
                            <td>
                                <span class="badge badge-info">Cache: {{ $health['cache_driver'] }}</span>
                                <span class="badge badge-info">Session: {{ $health['session_driver'] }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Database Table Counts & PHP Extensions --}}
<div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem;">
    {{-- Database Tables Count --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">📊 إحصائيات جداول قاعدة البيانات</h3>
            <p class="card-subtitle">عدد السجلات الحالية في الجداول الأساسية</p>
        </div>
        <div class="card-body" style="padding:0;">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>اسم الجدول</th>
                            <th>عدد السجلات (Rows)</th>
                            <th>النوع</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($health['table_counts'] as $table => $count)
                            <tr>
                                <td style="font-family:monospace; font-weight:700;">{{ $table }}</td>
                                <td style="font-weight:800; color:#6366f1;">{{ number_format($count) }}</td>
                                <td>
                                    @if(in_array($table, ['payments', 'expenses']))
                                        <span class="badge badge-success">مالي</span>
                                    @elseif(in_array($table, ['security_incidents', 'blocked_ips', 'audit_logs']))
                                        <span class="badge badge-danger">أمان ورقابة</span>
                                    @else
                                        <span class="badge badge-info">كيانات النظام</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- PHP Extensions & Services --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">🧩 حزم وإضافات PHP الأساسية</h3>
            <p class="card-subtitle">التأكد من توفر الإضافات المطلوبة لأمان وسرعة التطبيق</p>
        </div>
        <div class="card-body">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
                @foreach($health['extensions'] as $ext => $loaded)
                    <div style="display:flex; justify-content:space-between; align-items:center; background:var(--bg-main); padding:0.75rem 1rem; border-radius:var(--radius-md); border:1px solid var(--border-color);">
                        <span style="font-family:monospace; font-weight:700;">{{ $ext }}</span>
                        @if($loaded)
                            <span class="badge badge-success" style="font-size:0.75rem;">✓ نشطة</span>
                        @else
                            <span class="badge badge-danger" style="font-size:0.75rem;">✗ مفقودة</span>
                        @endif
                    </div>
                @endforeach
            </div>

            <div style="margin-top:1.5rem; background:rgba(99,102,241,0.08); border:1px solid rgba(99,102,241,0.2); border-radius:var(--radius-md); padding:1rem;">
                <div style="font-weight:700; color:#6366f1; margin-bottom:0.25rem;">⚡ طابور المهام (Queue Workers)</div>
                <div style="font-size:0.85rem; color:var(--text-secondary);">
                    المحرك الحالي: <strong>{{ $health['queue_driver'] }}</strong> | الوظائف الفاشلة (Failed Jobs): <strong>{{ $health['failed_jobs'] }}</strong>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Backup Management & Storage Snapshots (Priority 8) --}}
<div class="card" style="margin-top:1.5rem;">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
        <div>
            <h3 class="card-title">💾 إدارة النسخ الاحتياطية المحفوظة (Snapshots)</h3>
            <p class="card-subtitle">قائمة بالنسخ الاحتياطية المتوفرة على السيرفر مع إمكانية التحميل أو الحذف</p>
        </div>
        <form method="POST" action="{{ route('superadmin.server.backup.create') }}">
            @csrf
            <button type="submit" class="btn btn-primary btn-sm" style="background:linear-gradient(135deg, #10b981 0%, #059669 100%); border:none; display:inline-flex; align-items:center; gap:0.4rem; padding:0.55rem 1rem; font-weight:700;">
                <span>➕</span>
                <span>إنشاء وحفظ نسخة احتياطية جديدة الآن</span>
            </button>
        </form>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>اسم ملف النسخة</th>
                        <th>الحجم</th>
                        <th>تاريخ الإنشاء</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($backupFiles as $backup)
                        <tr>
                            <td>
                                <div style="display:flex; align-items:center; gap:0.5rem;">
                                    <span style="font-size:1.1rem;">📦</span>
                                    <strong class="num" style="font-size:0.9rem;">{{ $backup['filename'] }}</strong>
                                </div>
                            </td>
                            <td class="num">
                                <span class="badge badge-info">{{ $backup['size_mb'] > 0 ? $backup['size_mb'] . ' MB' : $backup['size_kb'] . ' KB' }}</span>
                            </td>
                            <td class="num" style="font-size:0.85rem;">
                                <div>{{ $backup['created_at']->format('Y/m/d H:i') }}</div>
                                <div style="font-size:0.72rem; color:var(--text-muted);">{{ $backup['created_at']->diffForHumans() }}</div>
                            </td>
                            <td>
                                <div style="display:flex; align-items:center; gap:0.5rem;">
                                    <a href="{{ route('superadmin.server.backup.download-file', $backup['filename']) }}" class="btn btn-sm btn-secondary" style="font-size:0.75rem;">
                                        ⬇️ تحميل
                                    </a>
                                    <form method="POST" action="{{ route('superadmin.server.backup.delete-file', $backup['filename']) }}"
                                          data-confirm="هل تؤكد حذف هذه النسخة الاحتياطية نهائياً من السيرفر؟"
                                          data-confirm-title="حذف نسخة احتياطية"
                                          data-confirm-type="danger"
                                          data-confirm-btn="حذف الملف">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-secondary" style="color:#ef4444; font-size:0.75rem;">
                                            🗑️ حذف
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align:center; padding:2.5rem; color:var(--text-muted);">
                                لا توجد نسخ احتياطية محفوظة حالياً على السيرفر. اضغط على الزر أعلاه لإنشاء أول نسخة وحفظها محلياً.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
