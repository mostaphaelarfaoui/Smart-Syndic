@extends('layouts.dashboard')

@section('title', 'السكان والإقامة والتواصل')

@section('content')

<div x-data="{
    pollModal: false,
    convertModal: false,
    convertData: {
        id: '',
        title: '',
        description: '',
        residentName: '',
        unitNumber: '',
        includeResident: true,
        expiresAt: '{{ date('Y-m-d', strtotime('+7 days')) }}',
        options: ['نعم، أوافق', 'لا، أرفض']
    },
    openConvertModal(id, title, desc, name, unit) {
        this.convertData.id = id;
        this.convertData.title = title || '';
        this.convertData.description = desc || '';
        this.convertData.residentName = name || 'أحد السكان';
        this.convertData.unitNumber = (unit || '').toString().replace(/^(شقة\s*)+/g, '').trim();
        this.convertData.includeResident = true;
        this.convertData.expiresAt = '{{ date('Y-m-d', strtotime('+7 days')) }}';
        this.convertData.options = ['نعم، أوافق', 'لا، أرفض'];
        this.convertModal = true;
    }
}">
    {{-- Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">
                <span>👥</span>
                <span>السكان والإقامة والتواصل</span>
            </h1>
            <p class="page-subtitle">دليل السكان والملاك، معالجة طلبات الانضمام، متابعة الشكايات واستطلاعات الرأي التشاركية</p>
        </div>

        {{-- Invitation Code & Actions --}}
        <div style="display:flex; align-items:center; gap:0.6rem; flex-wrap:wrap;">
            <div style="display:flex; align-items:center; gap:0.5rem; background:var(--bg-surface); padding:0.5rem 1rem; border:1px solid var(--border-color); border-radius:var(--radius-lg); box-shadow:var(--shadow-sm);" x-data="{ copied: false }">
                <span style="font-size:0.8rem; color:var(--text-secondary); font-weight:700;">كود الانضمام:</span>
                <code style="font-family:monospace; font-size:1.15rem; font-weight:900; color:#38bdf8; letter-spacing:0.08em; padding:0 0.3rem;">
                    {{ $tenant->invitation_code }}
                </code>
                <button
                    type="button"
                    class="btn btn-sm btn-secondary"
                    style="padding:0.25rem 0.55rem; font-size:0.75rem;"
                    x-on:click="navigator.clipboard.writeText('{{ $tenant->invitation_code }}'); copied = true; setTimeout(() => copied = false, 2500)"
                >
                    <span x-show="!copied">📋 نسخ</span>
                    <span x-show="copied" x-cloak style="color:var(--color-success); font-weight:700;">تم! ✓</span>
                </button>

                <a href="https://api.whatsapp.com/send?text={{ urlencode('السلام عليكم، يرجى من جميع سكان ' . $tenant->name . ' تحميل تطبيق Smart Syndic واستخدام كود الانضمام: ' . $tenant->invitation_code) }}" 
                   target="_blank" 
                   class="btn btn-sm btn-success" 
                   style="padding:0.25rem 0.65rem; font-size:0.75rem; background:#25d366; border-color:#25d366; color:#fff;"
                   title="مشاركة الكود في واتساب">
                    📲 واتساب
                </a>

                <form method="POST" action="{{ route('portal.residents.regenerate-code') }}"
                      data-confirm="هل تؤكد تجديد كود الدعوة للإقامة؟ الكود القديم سيتوقف عن العمل فوراً."
                      data-confirm-title="تجديد كود الدعوة"
                      data-confirm-type="warning"
                      data-confirm-btn="تجديد الكود"
                      style="margin:0;">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-secondary" style="padding:0.25rem 0.55rem; font-size:0.75rem;" title="توليد كود جديد">
                        🔄
                    </button>
                </form>
            </div>

            <button type="button" class="btn btn-primary" @click="pollModal = true" style="display:inline-flex; align-items:center; gap:0.4rem; font-weight:800;">
                <span>+</span>
                <span>إنشاء تصويت جديد</span>
            </button>
        </div>
    </div>

    {{-- Unified KPIs Grid --}}
    <div class="kpi-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom:1.5rem;">
        <div class="kpi-card" style="padding:1.15rem 1.35rem;">
            <div class="kpi-card-header" style="margin-bottom:0.4rem;">
                <span class="kpi-label">السكان المفعلين</span>
                <div class="kpi-icon-box emerald" style="width:34px; height:34px; font-size:1rem;">👥</div>
            </div>
            <div class="kpi-value num" style="font-size:1.6rem; margin-bottom:0; color:var(--color-success);">
                {{ $activeCount }} <span class="unit">ساكن</span>
            </div>
        </div>

        <div class="kpi-card" style="padding:1.15rem 1.35rem;">
            <div class="kpi-card-header" style="margin-bottom:0.4rem;">
                <span class="kpi-label">طلبات في الانتظار</span>
                <div class="kpi-icon-box amber" style="width:34px; height:34px; font-size:1rem;">⏳</div>
            </div>
            <div class="kpi-value num" style="font-size:1.6rem; margin-bottom:0; color: {{ $pendingCount > 0 ? 'var(--color-warning)' : 'var(--text-primary)' }};">
                {{ $pendingCount }} <span class="unit">طلب</span>
            </div>
        </div>

        <div class="kpi-card" style="padding:1.15rem 1.35rem;">
            <div class="kpi-card-header" style="margin-bottom:0.4rem;">
                <span class="kpi-label">شكاوى معلقة</span>
                <div class="kpi-icon-box rose" style="width:34px; height:34px; font-size:1rem;">📩</div>
            </div>
            <div class="kpi-value num" style="font-size:1.6rem; margin-bottom:0; color: {{ $pendingSuggestionsCount > 0 ? 'var(--color-danger)' : 'var(--text-primary)' }};">
                {{ $pendingSuggestionsCount }} <span class="unit">شكاية</span>
            </div>
        </div>

        <div class="kpi-card" style="padding:1.15rem 1.35rem;">
            <div class="kpi-card-header" style="margin-bottom:0.4rem;">
                <span class="kpi-label">استطلاعات نشطة</span>
                <div class="kpi-icon-box indigo" style="width:34px; height:34px; font-size:1rem;">🗳️</div>
            </div>
            <div class="kpi-value num" style="font-size:1.6rem; margin-bottom:0; color:var(--brand-primary);">
                {{ $activePollsCount }} <span class="unit">تصويت</span>
            </div>
        </div>
    </div>

    {{-- Tabs Switcher --}}
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem; margin-bottom:1.25rem;">
        <div style="display:inline-flex; align-items:center; gap:0.4rem; background:var(--bg-surface); padding:0.35rem; border:1px solid var(--border-color); border-radius:var(--radius-lg);">
            <a href="{{ route('portal.residents.index', ['tab' => 'residents']) }}" 
               class="btn btn-sm {{ $tab === 'residents' ? 'btn-primary' : 'btn-secondary' }}"
               style="font-weight:800; font-size:0.88rem; padding:0.45rem 1rem;">
                <span>👥 دليل السكان والطلبات</span>
                <span class="badge badge-neutral num" style="font-size:0.75rem; margin-right:4px;">{{ $activeCount + $pendingCount }}</span>
            </a>
            <a href="{{ route('portal.residents.index', ['tab' => 'suggestions']) }}" 
               class="btn btn-sm {{ $tab === 'suggestions' ? 'btn-primary' : 'btn-secondary' }}"
               style="font-weight:800; font-size:0.88rem; padding:0.45rem 1rem;">
                <span>📩 الشكاوى والملاحظات</span>
                <span class="badge badge-neutral num" style="font-size:0.75rem; margin-right:4px;">{{ $suggestions->total() }}</span>
            </a>
            <a href="{{ route('portal.residents.index', ['tab' => 'polls']) }}" 
               class="btn btn-sm {{ $tab === 'polls' ? 'btn-primary' : 'btn-secondary' }}"
               style="font-weight:800; font-size:0.88rem; padding:0.45rem 1rem;">
                <span>🗳️ استطلاعات الرأي والتصويت</span>
                <span class="badge badge-neutral num" style="font-size:0.75rem; margin-right:4px;">{{ $polls->total() }}</span>
            </a>
        </div>
    </div>

    {{-- ================= TAB 1: RESIDENTS DIRECTORY & PENDING REQUESTS ================= --}}
    @if($tab === 'residents')
        <div class="filter-card">
            <form method="GET" action="{{ route('portal.residents.index') }}" class="filter-form">
                <input type="hidden" name="tab" value="residents">

                <div class="search-box" style="flex:1; min-width:200px;">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="ابحث بالاسم، الهاتف أو البريد الإلكتروني...">
                    <span class="search-icon">🔍</span>
                </div>

                <select name="status" class="form-select" style="width:auto; min-width:180px;">
                    <option value="">جميع الحالات</option>
                    <option value="ACTIVE" {{ request('status') == 'ACTIVE' ? 'selected' : '' }}>حساب مفعل (نشط)</option>
                    <option value="PENDING" {{ request('status') == 'PENDING' ? 'selected' : '' }}>في انتظار الموافقة</option>
                </select>

                <button type="submit" class="btn btn-primary">
                    <span>تطبيق الفلترة</span>
                </button>

                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('portal.residents.index', ['tab' => 'residents']) }}" class="btn btn-secondary">
                        <span>إلغاء الفلاتر</span>
                    </a>
                @endif
            </form>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="min-width:150px;">الاسم الكامل</th>
                            <th style="min-width:120px;">رقم الهاتف</th>
                            <th style="min-width:160px;">البريد الإلكتروني</th>
                            <th style="min-width:120px;">الشقة المرتبطة</th>
                            <th style="text-align:center; min-width:110px;">الحالة</th>
                            <th style="min-width:110px;">تاريخ التسجيل</th>
                            <th style="min-width:150px; text-align:center;">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($residents as $resident)
                            <tr>
                                <td>
                                    <strong style="font-size:0.95rem; color:var(--text-primary);">{{ $resident->full_name }}</strong>
                                </td>
                                <td class="num">{{ $resident->phone ?? '—' }}</td>
                                <td style="color:var(--text-secondary); font-size:0.88rem;">{{ $resident->email }}</td>
                                <td>
                                    @if($resident->apartments->isNotEmpty())
                                        @php
                                            $apt = $resident->apartments->first();
                                            $cleanUnit = str_starts_with(trim($apt->unit_number), 'شقة') ? $apt->unit_number : 'شقة ' . $apt->unit_number;
                                        @endphp
                                        <div>
                                            <span class="badge badge-neutral num" style="font-weight:800; font-size:0.85rem;">
                                                {{ $cleanUnit }}
                                            </span>
                                            @if($apt->building)
                                                <div style="font-size:0.72rem; color:var(--text-muted); margin-top:2px;">
                                                    {{ $apt->building->name }}{{ $apt->building->block ? ' (' . $apt->building->block . ')' : '' }}
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <span style="color:var(--text-muted); font-size:0.85rem;">غير محددة</span>
                                    @endif
                                </td>
                                <td style="text-align:center;">
                                    @if($resident->status === 'ACTIVE')
                                        <span class="badge badge-success">✓ مفعل</span>
                                    @elseif($resident->status === 'PENDING')
                                        <span class="badge badge-warning">⏳ في الانتظار</span>
                                    @else
                                        <span class="badge badge-neutral">{{ $resident->status }}</span>
                                    @endif
                                </td>
                                <td class="num" style="color:var(--text-muted); font-size:0.85rem;">
                                    {{ $resident->created_at->format('Y/m/d') }}
                                </td>
                                <td style="text-align:center;">
                                    <div style="display:flex; align-items:center; justify-content:center; gap:0.4rem;">
                                        @if($resident->status === 'PENDING')
                                            <form method="POST" action="{{ route('portal.residents.approve', $resident->id) }}" style="margin:0;">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success" style="font-weight:700; padding:0.25rem 0.6rem; font-size:0.75rem; border-radius:6px;" title="موافقة على الحساب">
                                                    قبول ✓
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('portal.residents.reject', $resident->id) }}"
                                                  data-confirm="هل تؤكد رفض وحذف طلب انضمام الساكن {{ $resident->full_name }}؟"
                                                  data-confirm-title="رفض طلب الانضمام"
                                                  data-confirm-type="danger"
                                                  data-confirm-btn="رفض الطلب"
                                                  style="margin:0;">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-danger" style="padding:0.25rem 0.55rem; font-size:0.75rem; border-radius:6px;" title="رفض الطلب">
                                                    ✕
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('portal.residents.destroy', $resident->id) }}"
                                                  data-confirm="تحذير: هل تؤكد حذف حساب الساكن {{ $resident->full_name }}؟ سيتم فك ارتباطه بالشقة وإعادتها شاغرة."
                                                  data-confirm-title="تأكيد حذف حساب الساكن"
                                                  data-confirm-type="danger"
                                                  data-confirm-password="true"
                                                  data-confirm-btn="حذف الحساب"
                                                  style="margin:0;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-secondary" style="color:var(--color-danger); padding:0.25rem 0.55rem; font-size:0.75rem; border-radius:6px;" title="حذف حساب الساكن">
                                                    حذف 🗑️
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align:center; padding:3rem; color:var(--text-muted);">
                                    لا يوجد سكان مطابقين للمعايير المحددة.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($residents->hasPages())
                <div class="card-footer">
                    {{ $residents->links() }}
                </div>
            @endif
        </div>
    @endif

    {{-- ================= TAB 2: SUGGESTIONS & COMPLAINTS ================= --}}
    @if($tab === 'suggestions')
        <div class="card">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="min-width:140px;">المرسل والشقة</th>
                            <th style="min-width:170px;">موضوع الشكاية / الملاحظة</th>
                            <th style="min-width:240px;">التفاصيل والوصف</th>
                            <th style="text-align:center; min-width:110px;">الحالة الحالية</th>
                            <th style="min-width:110px;">التاريخ</th>
                            <th style="min-width:260px; text-align:center;">الإجراءات وتحديث الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($suggestions as $sug)
                            <tr>
                                <td>
                                    <strong>{{ $sug->user?->full_name ?? 'الساكن' }}</strong>
                                    <div style="font-size:0.75rem; color:var(--text-muted);">
                                        @if($sug->apartment)
                                            شقة {{ $sug->apartment->unit_number }}
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <strong style="font-size:0.95rem; color:var(--text-primary);">{{ $sug->title }}</strong>
                                </td>
                                <td style="max-width:300px; color:var(--text-secondary); font-size:0.88rem;">
                                    {{ $sug->description }}
                                </td>
                                <td style="text-align:center;">
                                    @if($sug->status === 'CONVERTED_TO_POLL')
                                        <span class="badge badge-success" style="font-size:0.75rem; font-weight:700;">🗳️ محوّل لتصويت</span>
                                    @elseif($sug->status === 'RESOLVED')
                                        <span class="badge badge-success">✓ تم الحل</span>
                                    @elseif($sug->status === 'IN_PROGRESS')
                                        <span class="badge badge-info">⚙️ قيد المعالجة</span>
                                    @elseif($sug->status === 'REJECTED')
                                        <span class="badge badge-danger">مرفوضة</span>
                                    @else
                                        <span class="badge badge-warning">⏳ في الانتظار</span>
                                    @endif
                                </td>
                                <td class="num" style="color:var(--text-muted); font-size:0.85rem;">
                                    {{ $sug->created_at->format('Y/m/d') }}
                                </td>
                                <td style="text-align:center;">
                                    <div style="display:flex; align-items:center; gap:0.4rem; justify-content:center; flex-wrap:wrap;">
                                        {{-- Status Updater Form --}}
                                        <form method="POST" action="{{ route('portal.community.suggestions.update', $sug->id) }}" style="display:flex; align-items:center; gap:0.3rem; margin:0;">
                                            @csrf
                                            <select name="status" class="form-select form-select-sm" style="width:auto; padding:0.25rem 0.45rem; font-size:0.75rem;">
                                                <option value="PENDING" {{ $sug->status === 'PENDING' ? 'selected' : '' }}>في الانتظار</option>
                                                <option value="IN_PROGRESS" {{ $sug->status === 'IN_PROGRESS' ? 'selected' : '' }}>قيد المعالجة</option>
                                                <option value="RESOLVED" {{ $sug->status === 'RESOLVED' ? 'selected' : '' }}>تم الحل</option>
                                                <option value="REJECTED" {{ $sug->status === 'REJECTED' ? 'selected' : '' }}>رفض</option>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-secondary" style="padding:0.25rem 0.5rem; font-size:0.75rem;" title="حفظ الحالة">
                                                حفظ
                                            </button>
                                        </form>

                                        {{-- Convert to Poll Button (Like Mobile App) --}}
                                        @if($sug->status !== 'CONVERTED_TO_POLL')
                                            @php
                                                $cleanSugUnit = preg_replace('/^(شقة\s*)+/u', '', trim($sug->apartment?->unit_number ?? ''));
                                            @endphp
                                            <button type="button" 
                                                    class="btn btn-sm btn-primary" 
                                                    @click="openConvertModal('{{ $sug->id }}', @js($sug->title), @js($sug->description), @js($sug->user?->full_name ?? 'الساكن'), @js($cleanSugUnit))"
                                                    style="display:inline-flex; align-items:center; gap:0.3rem; padding:0.25rem 0.65rem; font-size:0.75rem; font-weight:700; background:linear-gradient(135deg, #6366f1, #4f46e5); border:none; box-shadow:0 2px 6px rgba(99,102,241,0.25);"
                                                    title="تحويل هذا المقترح لاستطلاع رأي وتصويت لجميع السكان">
                                                <span>🗳️ تحويل لتصويت</span>
                                            </button>
                                        @else
                                            <span class="badge badge-neutral" style="font-size:0.72rem; padding:0.25rem 0.5rem;">تم الطرح للتصويت ✓</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align:center; padding:3rem; color:var(--text-muted);">
                                    لا توجد شكاوى أو ملاحظات مسجلة حالياً.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($suggestions->hasPages())
                <div class="card-footer">
                    {{ $suggestions->links() }}
                </div>
            @endif
        </div>
    @endif

    {{-- ================= TAB 3: POLLS & COMMUNITY VOTING ================= --}}
    @if($tab === 'polls')
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap:1.5rem;">
            @forelse($polls as $poll)
                @php
                    $totalVotes = $poll->options->sum(fn($o) => $o->votes->count());
                    $comments = $poll->votes->filter(fn($v) => !empty(trim($v->comment ?? '')));
                @endphp
                <div class="card" style="margin:0; padding:1.25rem; border:1px solid var(--border-color); border-radius:var(--radius-lg); background:var(--bg-card); display:flex; flex-direction:column; justify-content:space-between;">
                    <div>
                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:0.75rem;">
                            <span class="badge {{ $poll->is_active ? 'badge-success' : 'badge-neutral' }}">
                                {{ $poll->is_active ? '● تصويت نشط' : 'مغلق' }}
                            </span>
                            <span class="num" style="font-size:0.8rem; color:var(--text-muted);">
                                المشاركون: <strong>{{ $totalVotes }}</strong> صوت
                            </span>
                        </div>

                        <h3 style="margin:0 0 0.5rem 0; font-size:1.1rem; font-weight:800; color:var(--text-primary);">
                            {{ $poll->title }}
                        </h3>

                        @if($poll->description)
                            <p style="font-size:0.85rem; color:var(--text-secondary); margin:0 0 1rem 0; line-height:1.45; white-space:pre-line;">
                                {{ $poll->description }}
                            </p>
                        @endif

                        {{-- Options Progress Bars --}}
                        <div style="display:flex; flex-direction:column; gap:0.6rem; margin-bottom:1.25rem;">
                            @foreach($poll->options as $opt)
                                @php
                                    $optVotes = $opt->votes->count();
                                    $pct = $totalVotes > 0 ? round(($optVotes / $totalVotes) * 100) : 0;
                                @endphp
                                <div style="background:var(--bg-secondary); border-radius:8px; padding:0.6rem 0.85rem; border:1px solid var(--border-color);">
                                    <div style="display:flex; justify-content:space-between; font-size:0.85rem; font-weight:700; margin-bottom:0.35rem;">
                                        <span>{{ $opt->option_text }}</span>
                                        <span class="num">{{ $optVotes }} صوت ({{ $pct }}%)</span>
                                    </div>
                                    <div style="background:var(--bg-surface); height:6px; border-radius:999px; overflow:hidden;">
                                        <div style="background:linear-gradient(90deg, #0d9488, #10b981); height:100%; width:{{ $pct }}%;"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Comments on Poll Section (Identical to Mobile Experience) --}}
                        <div x-data="{ openComments: false }" style="margin-top:0.75rem; border-top:1px dashed var(--border-color); padding-top:0.75rem;">
                            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:0.4rem;">
                                <div style="display:flex; align-items:center; gap:0.4rem;">
                                    <span style="font-size:1.05rem;">💬</span>
                                    <strong style="font-size:0.85rem; color:var(--text-primary);">تعليقات وآراء السكان</strong>
                                    <span class="badge {{ $comments->isNotEmpty() ? 'badge-info' : 'badge-neutral' }} num" style="font-size:0.72rem; font-weight:800; padding:0.15rem 0.45rem;">
                                        {{ $comments->count() }}
                                    </span>
                                </div>
                                @if($comments->isNotEmpty())
                                    <button type="button" @click="openComments = !openComments" class="btn btn-sm btn-secondary" style="font-size:0.72rem; padding:0.2rem 0.5rem; font-weight:700;">
                                        <span x-text="openComments ? 'إخفاء التعليقات ▲' : 'عرض التعليقات (' + {{ $comments->count() }} + ') ▼'"></span>
                                    </button>
                                @endif
                            </div>

                            @if($comments->isNotEmpty())
                                <div x-show="openComments" x-cloak style="display:flex; flex-direction:column; gap:0.6rem; max-height:260px; overflow-y:auto; padding-left:4px; margin-top:0.6rem;">
                                    @foreach($comments as $commentVote)
                                        <div style="background:var(--bg-secondary); border:1px solid var(--border-color); border-radius:8px; padding:0.6rem 0.8rem;">
                                            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:0.35rem; flex-wrap:wrap; gap:0.25rem;">
                                                <div style="display:flex; align-items:center; gap:0.4rem;">
                                                    <div style="width:20px; height:20px; border-radius:50%; background:rgba(13,148,136,0.15); color:var(--brand-primary); display:flex; align-items:center; justify-content:center; font-size:0.7rem;">
                                                        👤
                                                    </div>
                                                    @if($commentVote->is_anonymous_comment)
                                                        <strong style="font-size:0.8rem; color:var(--text-secondary);">ساكن (مجهول الهوية)</strong>
                                                    @else
                                                        <strong style="font-size:0.82rem; color:var(--text-primary);">{{ $commentVote->user?->full_name ?? 'الساكن' }}</strong>
                                                    @endif

                                                    @if($commentVote->apartment)
                                                        <span class="badge badge-neutral num" style="font-size:0.7rem;">
                                                            شقة {{ $commentVote->apartment->unit_number }}
                                                            @if($commentVote->apartment->building)
                                                                <small style="color:var(--text-muted);">({{ $commentVote->apartment->building->name }})</small>
                                                            @endif
                                                        </span>
                                                    @endif
                                                </div>
                                                <span class="num" style="font-size:0.7rem; color:var(--text-muted);">
                                                    {{ $commentVote->created_at->format('Y/m/d H:i') }}
                                                </span>
                                            </div>

                                            @if($commentVote->option)
                                                <div style="margin-bottom:0.35rem; font-size:0.75rem;">
                                                    <span style="color:var(--text-muted); font-size:0.72rem;">صوّت بـ:</span>
                                                    <span class="badge badge-neutral" style="font-size:0.7rem; font-weight:700;">
                                                        {{ $commentVote->option->option_text }}
                                                    </span>
                                                </div>
                                            @endif

                                            <div style="font-size:0.83rem; color:var(--text-primary); line-height:1.45; background:var(--bg-surface); padding:0.5rem 0.7rem; border-radius:6px; border-right:3px solid var(--brand-primary);">
                                                {{ $commentVote->comment }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div style="font-size:0.75rem; color:var(--text-muted); background:var(--bg-secondary); padding:0.4rem 0.65rem; border-radius:6px;">
                                    لا توجد تعليقات مكتوبة من السكان على هذا التصويت حتى الآن.
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div style="display:flex; align-items:center; justify-content:space-between; border-top:1px dashed var(--border-color); padding-top:0.75rem; margin-top:1rem;">
                        <span style="font-size:0.78rem; color:var(--text-muted);">
                            ينتهي في: <strong class="num">{{ $poll->expires_at ? \Carbon\Carbon::parse($poll->expires_at)->format('Y/m/d') : 'مفتوح' }}</strong>
                        </span>

                        <form method="POST" action="{{ route('portal.community.polls.toggle', $poll->id) }}" style="margin:0;">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-secondary" style="font-size:0.75rem; padding:0.25rem 0.6rem;">
                                {{ $poll->is_active ? 'إيقاف التصويت' : 'إعادة تفعيل' }}
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="card" style="grid-column: 1 / -1; text-align:center; padding:3.5rem 1rem; color:var(--text-muted);">
                    <div style="font-size:2.2rem; margin-bottom:0.5rem;">🗳️</div>
                    <div style="font-weight:700; font-size:1rem; color:var(--text-primary); margin-bottom:0.3rem;">لا توجد استطلاعات رأي منشورة حالياً</div>
                    <div style="font-size:0.85rem; margin-bottom:1rem;">يمكنك إشراك السكان عبر طرح استطلاع جديد من زر "إنشاء تصويت جديد" بالأعلى، أو تحويل مقترحات السكان لتصويت.</div>
                    <button type="button" class="btn btn-primary" @click="pollModal = true">
                        + إنشاء استطلاع رأي جديد
                    </button>
                </div>
            @endforelse
        </div>

        @if($polls->hasPages())
            <div style="margin-top:1.5rem;">
                {{ $polls->links() }}
            </div>
        @endif
    @endif

    {{-- Modal 1: Create Poll From Scratch --}}
    <div class="modal-overlay" x-show="pollModal" x-cloak style="z-index:99990;">
        <div class="modal-dialog" @click.away="pollModal = false" x-data="{ options: ['', ''] }">
            <div class="modal-header">
                <h3 class="modal-title">إنشاء استطلاع رأي جديد</h3>
                <button type="button" class="icon-btn" @click="pollModal = false">✕</button>
            </div>
            <form method="POST" action="{{ route('portal.community.polls.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" style="font-weight:700;">موضوع أو سؤال الاستطلاع</label>
                        <input type="text" name="title" class="form-control" placeholder="مثال: هل توافق على تركيب كاميرات مراقبة إضافية بالمرآب؟" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">توضيح إضافي (اختياري)</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="تفاصيل الميزانية المقترحة أو سبب التصويت..."></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-weight:700;">تاريخ انتهاء التصويت</label>
                        <input type="date" name="expires_at" class="form-control" value="{{ date('Y-m-d', strtotime('+7 days')) }}">
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-weight:700;">خيارات التصويت (2 على الأقل)</label>
                        <template x-for="(opt, index) in options" :key="index">
                            <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.5rem;">
                                <input type="text" name="options[]" class="form-control" :placeholder="'الخيار ' + (index + 1)" required>
                                <button type="button" class="btn btn-sm btn-secondary" @click="if(options.length > 2) options.splice(index, 1)" style="color:#ef4444;" title="حذف الخيار">✕</button>
                            </div>
                        </template>
                        <button type="button" class="btn btn-sm btn-secondary" @click="options.push('')" style="margin-top:0.5rem;">
                            + إضافة خيار إضافي
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="pollModal = false">إلغاء</button>
                    <button type="submit" class="btn btn-primary" style="font-weight:700;">نشر الاستطلاع للجميع ✓</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal 2: Convert Suggestion to Poll (Matching Mobile App Experience) --}}
    <div class="modal-overlay" x-show="convertModal" x-cloak style="z-index:99995;">
        <div class="modal-dialog" @click.away="convertModal = false" style="max-width:540px;">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title" style="display:flex; align-items:center; gap:0.5rem;">
                        <span>🗳️</span>
                        <span>تحويل الاقتراح إلى تصويت عام للسكان</span>
                    </h3>
                    <p style="margin:0.25rem 0 0 0; font-size:0.82rem; color:var(--text-secondary);">
                        سيتم طرح هذا المقترح كتصويت تفاعلي لجميع سكان الإقامة لإبداء آرائهم
                    </p>
                </div>
                <button type="button" class="icon-btn" @click="convertModal = false">✕</button>
            </div>
            <form method="POST" :action="'{{ url('portal/community/suggestions') }}/' + convertData.id + '/convert-to-poll'">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" style="font-weight:700;">عنوان أو سؤال التصويت</label>
                        <input type="text" name="title" class="form-control" x-model="convertData.title" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-weight:700;">تفاصيل وشرح الاقتراح</label>
                        <textarea name="description" class="form-control" rows="3" x-model="convertData.description" placeholder="تفاصيل المقترح..."></textarea>
                    </div>

                    {{-- Option to credit the resident --}}
                    <div style="margin-bottom:1rem; padding:0.65rem 0.85rem; background:rgba(99,102,241,0.06); border:1px solid rgba(99,102,241,0.2); border-radius:8px;">
                        <label style="display:flex; align-items:center; gap:0.6rem; cursor:pointer; font-size:0.85rem; font-weight:700; color:var(--text-primary); margin:0;">
                            <input type="checkbox" name="include_resident_info" value="1" x-model="convertData.includeResident" style="width:16px; height:16px; accent-color:#6366f1;">
                            <span>تضمين اسم صاحب المقترح تقديراً لمبادرته</span>
                        </label>
                        <div style="font-size:0.75rem; color:var(--text-muted); margin-top:0.35rem; margin-right:1.75rem;">
                            سيتم إلحاق العبارة: <strong x-text="'(مُقترح من طرف: ' + convertData.residentName + (convertData.unitNumber ? ' - شقة ' + convertData.unitNumber.replace(/^(شقة\s*)+/g, '').trim() : '') + ')'"></strong> في نهاية الوصف
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-weight:700;">تاريخ انتهاء التصويت</label>
                        <input type="date" name="expires_at" class="form-control" x-model="convertData.expiresAt" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-weight:700;">خيارات التصويت (2 على الأقل)</label>
                        <template x-for="(opt, index) in convertData.options" :key="index">
                            <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.5rem;">
                                <input type="text" name="options[]" class="form-control" x-model="convertData.options[index]" :placeholder="'الخيار ' + (index + 1)" required>
                                <button type="button" class="btn btn-sm btn-secondary" @click="if(convertData.options.length > 2) convertData.options.splice(index, 1)" style="color:#ef4444;" title="حذف هذا الخيار">✕</button>
                            </div>
                        </template>
                        <button type="button" class="btn btn-sm btn-secondary" @click="if(convertData.options.length < 6) convertData.options.push('')" style="margin-top:0.35rem; font-size:0.8rem; font-weight:700;">
                            + إضافة خيار تصويت آخر
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="convertModal = false">إلغاء</button>
                    <button type="submit" class="btn btn-primary" style="font-weight:800; background:linear-gradient(135deg, #6366f1, #4f46e5); border:none; box-shadow:0 4px 12px rgba(99,102,241,0.3);">
                        🗳️ تأكيد وطرح التصويت للجميع ✓
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
