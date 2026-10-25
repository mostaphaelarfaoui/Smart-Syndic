@extends('layouts.dashboard')

@section('title', 'إدارة السكان والمقيمين')

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">
            <span>👥</span>
            <span>إدارة السكان والمقيمين</span>
        </h1>
        <p class="page-subtitle">متابعة حسابات الملاك والسكان، تفعيل طلبات الانضمام الجديدة وإدارة كود الدعوة</p>
    </div>

    {{-- Invitation Code Banner Card --}}
    <div style="display:flex; align-items:center; gap:0.5rem; background:var(--bg-surface); padding:0.6rem 1.15rem; border:1px solid var(--border-color); border-radius:var(--radius-lg); box-shadow:var(--shadow-sm);" x-data="{ copied: false }">
        <span style="font-size:0.85rem; color:var(--text-secondary); font-weight:700;">كود الانضمام:</span>
        <code style="font-family:monospace; font-size:1.15rem; font-weight:900; color:#38bdf8; letter-spacing:0.08em; padding:0 0.3rem;">
            {{ $tenant->invitation_code }}
        </code>
        <button
            type="button"
            class="btn btn-sm btn-secondary"
            style="padding:0.25rem 0.6rem; font-size:0.75rem;"
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
                🔄 تجديد
            </button>
        </form>
    </div>
</div>

{{-- KPI Summary Row --}}
<div class="kpi-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom:1.5rem;">
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
            <span class="kpi-label">طلبات في انتظار التفعيل</span>
            <div class="kpi-icon-box amber" style="width:34px; height:34px; font-size:1rem;">⏳</div>
        </div>
        <div class="kpi-value num" style="font-size:1.6rem; margin-bottom:0; color: {{ $pendingCount > 0 ? 'var(--color-warning)' : 'var(--text-primary)' }};">
            {{ $pendingCount }} <span class="unit">طلب</span>
        </div>
    </div>

    <div class="kpi-card" style="padding:1.15rem 1.35rem;">
        <div class="kpi-card-header" style="margin-bottom:0.4rem;">
            <span class="kpi-label">إجمالي المسجلين</span>
            <div class="kpi-icon-box teal" style="width:34px; height:34px; font-size:1rem;">📋</div>
        </div>
        <div class="kpi-value num" style="font-size:1.6rem; margin-bottom:0;">
            {{ $activeCount + $pendingCount }} <span class="unit">حساب</span>
        </div>
    </div>
</div>

{{-- Filters Card --}}
<div class="filter-card">
    <form method="GET" action="{{ route('portal.residents.index') }}" class="filter-form">
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
            <a href="{{ route('portal.residents.index') }}" class="btn btn-secondary">
                <span>إلغاء الفلاتر</span>
            </a>
        @endif
    </form>
</div>

{{-- Residents Table Card --}}
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="min-width:150px;">الاسم الكامل</th>
                    <th style="min-width:120px;">رقم الهاتف</th>
                    <th style="min-width:160px;">البريد الإلكتروني</th>
                    <th style="min-width:110px;">الشقة المرتبطة</th>
                    <th style="text-align:center; min-width:110px;">الحالة</th>
                    <th style="min-width:110px;">تاريخ التسجيل</th>
                    <th style="min-width:150px;">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($residents as $resident)
                    <tr>
                        <td>
                            <strong style="font-size:0.95rem;">{{ $resident->full_name }}</strong>
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
                        <td>
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
                        <td>
                            <div style="display:flex; align-items:center; gap:0.5rem;">
                                @if($resident->status === 'PENDING')
                                    <form method="POST" action="{{ route('portal.residents.approve', $resident->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success" title="موافقة على الحساب">
                                            موافقة ✓
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('portal.residents.reject', $resident->id) }}"
                                          data-confirm="هل تؤكد رفض وحذف طلب انضمام الساكن {{ $resident->full_name }}؟"
                                          data-confirm-title="رفض طلب الانضمام"
                                          data-confirm-type="danger"
                                          data-confirm-btn="رفض الطلب">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-danger" title="رفض الطلب">
                                            رفض ✕
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('portal.residents.destroy', $resident->id) }}"
                                          data-confirm="تحذير: هل تؤكد حذف حساب الساكن {{ $resident->full_name }}؟ سيتم فك ارتباطه بالشقة وإعادتها شاغرة وتصفير الحسابات."
                                          data-confirm-title="تأكيد حذف حساب الساكن"
                                          data-confirm-type="danger"
                                          data-confirm-password="true"
                                          data-confirm-btn="حذف الحساب">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-secondary" style="color:var(--color-danger);" title="حذف حساب الساكن وتفريغ الشقة">
                                            حذف الحساب 🗑️
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

@endsection
