@extends('layouts.superadmin')
@section('title', 'نظام الإعلانات والإشعارات الجماعية')

@section('content')
<div x-data="{ createModalOpen: false, targetAudience: 'ALL' }">
    <div class="page-header" style="margin-bottom:1.5rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
        <div>
            <h1 class="page-title">📢 نظام الإعلانات والإشعارات الجماعية للبوابة</h1>
            <p class="page-subtitle">بث تنبيهات، رسائل صيانة، وإشعارات فورية تظهر في أعلى شاشات السانديك والسكان.</p>
        </div>
        <div>
            <button type="button" @click="createModalOpen = true" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:0.5rem; padding:0.65rem 1.25rem;">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>إنشاء إعلان جماعي جديد</span>
            </button>
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div style="background:rgba(16, 185, 129, 0.15); border:1px solid #10b981; color:#10b981; padding:0.85rem 1.25rem; border-radius:10px; margin-bottom:1.5rem; font-weight:600; display:flex; align-items:center; gap:0.6rem;">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div style="background:rgba(239, 68, 68, 0.15); border:1px solid #ef4444; color:#ef4444; padding:0.85rem 1.25rem; border-radius:10px; margin-bottom:1.5rem; font-weight:600; display:flex; align-items:center; gap:0.6rem;">
            <span>⚠️</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- KPI Cards --}}
    <div class="kpi-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom:1.75rem;">
        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-title">إجمالي الإعلانات</span>
                <span class="kpi-icon" style="background:rgba(99, 102, 241, 0.15); color:#6366f1;">📢</span>
            </div>
            <div class="kpi-value">{{ number_format($totalCount) }}</div>
            <div class="kpi-subtitle">سجل المنشورات الشاملة</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-title">الإعلانات النشطة حالياً</span>
                <span class="kpi-icon" style="background:rgba(16, 185, 129, 0.15); color:#10b981;">⚡</span>
            </div>
            <div class="kpi-value" style="color:#10b981;">{{ number_format($activeCount) }}</div>
            <div class="kpi-subtitle">تظهر للمستخدمين الآن</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-title">موجّه للسانديك فقط</span>
                <span class="kpi-icon" style="background:rgba(245, 158, 11, 0.15); color:#f59e0b;">🏢</span>
            </div>
            <div class="kpi-value">{{ number_format(\App\Models\PlatformAnnouncement::where('target_audience', 'SYNDICS_ONLY')->count()) }}</div>
            <div class="kpi-subtitle">إشعارات إدارية خاصة</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-title">موجّه لكافة المنظومة</span>
                <span class="kpi-icon" style="background:rgba(168, 85, 247, 0.15); color:#a855f7;">🌐</span>
            </div>
            <div class="kpi-value">{{ number_format(\App\Models\PlatformAnnouncement::where('target_audience', 'ALL')->count()) }}</div>
            <div class="kpi-subtitle">سكان + مسيرين + حراس</div>
        </div>
    </div>

    {{-- Filters Card --}}
    <div class="card" style="margin-bottom:1.5rem; padding:1.25rem;">
        <form method="GET" action="{{ route('superadmin.announcements.index') }}" style="display:flex; gap:1rem; flex-wrap:wrap; align-items:flex-end;">
            <div style="flex:1; min-width:180px;">
                <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:0.4rem; color:var(--text-muted);">نوع الإشعار</label>
                <select name="type" class="form-input">
                    <option value="">جميع الأنواع</option>
                    <option value="INFO" {{ request('type') === 'INFO' ? 'selected' : '' }}>ℹ️ معلومة عامة (INFO)</option>
                    <option value="WARNING" {{ request('type') === 'WARNING' ? 'selected' : '' }}>⚠️ تنبيه هام (WARNING)</option>
                    <option value="DANGER" {{ request('type') === 'DANGER' ? 'selected' : '' }}>🚨 تحذير عاجل / صيانة (DANGER)</option>
                    <option value="SUCCESS" {{ request('type') === 'SUCCESS' ? 'selected' : '' }}>✨ ميزة جديدة / نجاح (SUCCESS)</option>
                </select>
            </div>

            <div style="flex:1; min-width:180px;">
                <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:0.4rem; color:var(--text-muted);">الفئة المستهدفة</label>
                <select name="audience" class="form-input">
                    <option value="">كافة الفئات</option>
                    <option value="ALL" {{ request('audience') === 'ALL' ? 'selected' : '' }}>🌐 كافة المستخدمين</option>
                    <option value="SYNDICS_ONLY" {{ request('audience') === 'SYNDICS_ONLY' ? 'selected' : '' }}>👔 السانديك فقط</option>
                    <option value="RESIDENTS_ONLY" {{ request('audience') === 'RESIDENTS_ONLY' ? 'selected' : '' }}>🏠 السكان فقط</option>
                    <option value="SPECIFIC_TENANT" {{ request('audience') === 'SPECIFIC_TENANT' ? 'selected' : '' }}>📍 إقامة محددة</option>
                </select>
            </div>

            <div>
                <button type="submit" class="btn btn-primary" style="padding:0.6rem 1.25rem;">تصفية</button>
                @if(request()->hasAny(['type', 'audience']))
                    <a href="{{ route('superadmin.announcements.index') }}" class="btn btn-secondary" style="padding:0.6rem 1rem;">إلغاء</a>
                @endif
            </div>
        </form>
    </div>

    {{-- Announcements Table --}}
    <div class="card" style="padding:0; overflow:hidden;">
        <div style="padding:1.25rem 1.5rem; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0; font-size:1.05rem; font-weight:700;">قائمة الإعلانات والرسائل الجماعية</h3>
            <span class="badge badge-neutral">{{ $announcements->total() }} إعلان</span>
        </div>

        <div class="table-responsive">
            <table class="data-table" style="width:100%;">
                <thead>
                    <tr>
                        <th>النوع & العنوان</th>
                        <th>نص الإعلان</th>
                        <th>الجمهور المستهدف</th>
                        <th>فترة الظهور</th>
                        <th>الحالة</th>
                        <th style="text-align:left;">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($announcements as $item)
                        <tr>
                            <td style="min-width:200px;">
                                <div style="display:flex; align-items:center; gap:0.6rem;">
                                    @if($item->type === 'DANGER')
                                        <span class="badge badge-danger" style="font-size:0.75rem;">🚨 عاجل</span>
                                    @elseif($item->type === 'WARNING')
                                        <span class="badge badge-warning" style="font-size:0.75rem;">⚠️ تنبيه</span>
                                    @elseif($item->type === 'SUCCESS')
                                        <span class="badge badge-success" style="font-size:0.75rem;">✨ جديد</span>
                                    @else
                                        <span class="badge badge-info" style="font-size:0.75rem;">ℹ️ إشعار</span>
                                    @endif
                                    <strong style="color:var(--text-main); font-size:0.95rem;">{{ $item->title }}</strong>
                                </div>
                                <div style="font-size:0.75rem; color:var(--text-muted); margin-top:0.25rem;">
                                    بواسطة: {{ $item->creator?->full_name ?? 'الإدارة' }} • {{ $item->created_at->diffForHumans() }}
                                </div>
                            </td>
                            <td style="max-width:350px;">
                                <div style="font-size:0.875rem; color:var(--text-main); white-space:pre-line; max-height:4.5rem; overflow:hidden; text-overflow:ellipsis;">
                                    {{ $item->message }}
                                </div>
                                @if($item->is_dismissible)
                                    <span style="font-size:0.7rem; color:var(--text-muted);">✓ قابل للإغلاق من المستخدم</span>
                                @else
                                    <span style="font-size:0.7rem; color:#ef4444; font-weight:600;">🔒 مثبت (لا يمكن إغلاقه)</span>
                                @endif
                            </td>
                            <td>
                                @if($item->target_audience === 'ALL')
                                    <span class="badge badge-neutral">🌐 كافة المنظومة</span>
                                @elseif($item->target_audience === 'SYNDICS_ONLY')
                                    <span class="badge badge-warning">👔 السانديك فقط</span>
                                @elseif($item->target_audience === 'RESIDENTS_ONLY')
                                    <span class="badge badge-info">🏠 السكان فقط</span>
                                @elseif($item->target_audience === 'SPECIFIC_TENANT')
                                    <span class="badge badge-purple">🏢 {{ $item->tenant?->name ?? 'إقامة محددة' }}</span>
                                @endif
                            </td>
                            <td style="font-size:0.8rem; color:var(--text-muted);">
                                @if($item->starts_at || $item->expires_at)
                                    <div>من: {{ $item->starts_at ? $item->starts_at->format('Y-m-d H:i') : 'الآن' }}</div>
                                    <div>إلى: {{ $item->expires_at ? $item->expires_at->format('Y-m-d H:i') : 'دائم' }}</div>
                                @else
                                    <span>مستمر ودائم</span>
                                @endif
                            </td>
                            <td>
                                @if($item->is_active)
                                    <span class="badge badge-success" style="display:inline-flex; align-items:center; gap:0.3rem;">
                                        <span style="width:6px; height:6px; border-radius:50%; background:#10b981;"></span>
                                        نشط
                                    </span>
                                @else
                                    <span class="badge badge-neutral">متوقف</span>
                                @endif
                            </td>
                            <td style="text-align:left;">
                                <div style="display:flex; align-items:center; justify-content:flex-end; gap:0.5rem;">
                                    {{-- Quick toggle --}}
                                    <form method="POST" action="{{ route('superadmin.announcements.toggle', $item->id) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-sm {{ $item->is_active ? 'btn-secondary' : 'btn-success' }}" title="{{ $item->is_active ? 'إيقاف البث' : 'تفعيل البث' }}">
                                            {{ $item->is_active ? '⏸️ إيقاف' : '▶️ تفعيل' }}
                                        </button>
                                    </form>

                                    {{-- Delete --}}
                                    <form method="POST" action="{{ route('superadmin.announcements.destroy', $item->id) }}" style="display:inline;"
                                          data-confirm="هل أنت متأكد من حذف هذا الإعلان الجماعي نهائياً؟"
                                          data-confirm-title="تأكيد حذف الإعلان"
                                          data-confirm-type="danger"
                                          data-confirm-btn="حذف الإعلان">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="حذف الإعلان">
                                            🗑️
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center; padding:3rem; color:var(--text-muted);">
                                <div style="font-size:2.5rem; margin-bottom:0.5rem;">📢</div>
                                <div style="font-weight:600; font-size:1.05rem;">لا توجد إعلانات أو إشعارات جماعية حالياً</div>
                                <p style="font-size:0.85rem; margin-top:0.25rem;">اضغط على "إنشاء إعلان جماعي جديد" لبث رسالة للمستخدمين.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($announcements->hasPages())
            <div style="padding:1rem 1.5rem; border-top:1px solid var(--border-color);">
                {{ $announcements->links() }}
            </div>
        @endif
    </div>

    {{-- Create Announcement Modal --}}
    <div x-show="createModalOpen" style="display:none;" class="modal-backdrop" @keydown.escape.window="createModalOpen = false">
        <div class="modal-dialog" style="max-width:620px;" @click.away="createModalOpen = false">
            <div class="modal-header">
                <h3 class="modal-title">📢 إنشاء وبث إعلان جماعي للمنصة</h3>
                <button type="button" class="modal-close" @click="createModalOpen = false" title="إغلاق">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form method="POST" action="{{ route('superadmin.announcements.store') }}">
                @csrf
                <div class="modal-body" style="padding:1.5rem; display:flex; flex-direction:column; gap:1.2rem;">
                    <div>
                        <label class="form-label" style="font-weight:600; margin-bottom:0.4rem;">عنوان الإشعار *</label>
                        <input type="text" name="title" required class="form-input" placeholder="مثال: أعمال صيانة مبرمجة لقواعد البيانات">
                    </div>

                    <div>
                        <label class="form-label" style="font-weight:600; margin-bottom:0.4rem;">نوع التنبيه والشريط *</label>
                        <select name="type" required class="form-input">
                            <option value="INFO">ℹ️ معلومة عامة (أزرق - Info)</option>
                            <option value="WARNING">⚠️ تنبيه هام (برتقالي - Warning)</option>
                            <option value="DANGER">🚨 تحذير عاجل / توقف خدمة (أحمر - Danger)</option>
                            <option value="SUCCESS">✨ ميزة جديدة / ترقية سعيدة (أخضر - Success)</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label" style="font-weight:600; margin-bottom:0.4rem;">الجمهور المستهدف *</label>
                        <select name="target_audience" x-model="targetAudience" required class="form-input">
                            <option value="ALL">🌐 كافة مستخدمي المنصة (سانديك + سكان)</option>
                            <option value="SYNDICS_ONLY">👔 مسيري السانديك فقط (لوحة تحكم السانديك)</option>
                            <option value="RESIDENTS_ONLY">🏠 السكان فقط (تطبيق وبوابة الملاك)</option>
                            <option value="SPECIFIC_TENANT">🏢 إقامة محددة بعينها</option>
                        </select>
                    </div>

                    <div x-show="targetAudience === 'SPECIFIC_TENANT'" style="display:none;">
                        <label class="form-label" style="font-weight:600; margin-bottom:0.4rem;">اختر الإقامة المستهدفة *</label>
                        <select name="tenant_id" class="form-input">
                            <option value="">-- حدد الإقامة --</option>
                            @foreach($tenants as $t)
                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="form-label" style="font-weight:600; margin-bottom:0.4rem;">نص الإعلان والتفاصيل *</label>
                        <textarea name="message" rows="4" required class="form-input" placeholder="اكتب نص الإشعار بالتفصيل، وسيظهر كشريط مميز في أعلى شاشة المستخدم..."></textarea>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                        <div>
                            <label class="form-label" style="font-size:0.8rem;">تاريخ البدء (اختياري)</label>
                            <input type="datetime-local" name="starts_at" class="form-input">
                        </div>
                        <div>
                            <label class="form-label" style="font-size:0.8rem;">تاريخ الانتهاء التلقائي (اختياري)</label>
                            <input type="datetime-local" name="expires_at" class="form-input">
                        </div>
                    </div>

                    <div style="display:flex; flex-direction:column; gap:0.6rem; padding-top:0.5rem; border-top:1px solid var(--border-color);">
                        <label style="display:flex; align-items:center; gap:0.6rem; cursor:pointer; font-size:0.875rem;">
                            <input type="checkbox" name="is_dismissible" value="1" checked style="width:17px; height:17px; accent-color:#6366f1;">
                            <span>السماح للمستخدم بإغلاق الإشعار بعد قراءته (Dismissible)</span>
                        </label>

                        <label style="display:flex; align-items:center; gap:0.6rem; cursor:pointer; font-size:0.875rem;">
                            <input type="checkbox" name="is_active" value="1" checked style="width:17px; height:17px; accent-color:#10b981;">
                            <span style="font-weight:600; color:#10b981;">تفعيل ونشر الإعلان فوراً</span>
                        </label>
                    </div>
                </div>
                <div class="modal-footer" style="padding:1rem 1.5rem; display:flex; justify-content:flex-end; gap:0.75rem; border-top:1px solid var(--border-color);">
                    <button type="button" @click="createModalOpen = false" class="btn btn-secondary">إلغاء</button>
                    <button type="submit" class="btn btn-primary" style="padding:0.6rem 1.5rem;">بث ونشر الإعلان الآن</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
