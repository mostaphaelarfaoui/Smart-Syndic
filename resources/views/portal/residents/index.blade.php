@extends('layouts.dashboard')

@section('title', 'إدارة السكان والمقيمين')

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">إدارة السكان والمقيمين</h1>
        <p class="page-subtitle">متابعة حسابات الملاك والسكان، تفعيل طلبات الانضمام الجديدة وإدارة كود الدعوة</p>
    </div>

    {{-- Invitation Code Banner Card --}}
    <div style="display:flex; align-items:center; gap:0.75rem; background:var(--bg-surface); padding:0.6rem 1.25rem; border:1px solid var(--border-color); border-radius:var(--radius-lg); box-shadow:var(--shadow-sm);" x-data="{ copied: false }">
        <span style="font-size:0.85rem; color:var(--text-secondary); font-weight:700;">كود انضمام السكان:</span>
        <code style="font-family:monospace; font-size:1.1rem; font-weight:900; color:var(--brand-primary); letter-spacing:0.05em;">
            {{ $tenant->invitation_code }}
        </code>
        <button
            type="button"
            class="btn btn-sm btn-secondary"
            x-on:click="navigator.clipboard.writeText('{{ $tenant->invitation_code }}'); copied = true; setTimeout(() => copied = false, 2500)"
        >
            <span x-show="!copied">نسخ 📋</span>
            <span x-show="copied" x-cloak style="color:var(--color-success); font-weight:700;">تم! ✓</span>
        </button>

        <form method="POST" action="{{ route('portal.residents.regenerate-code') }}"
              data-confirm="هل تؤكد تجديد كود الدعوة للإقامة؟ الكود القديم سيتوقف عن العمل فوراً."
              data-confirm-title="تجديد كود الدعوة"
              data-confirm-type="warning"
              data-confirm-btn="تجديد الكود">
            @csrf
            <button type="submit" class="btn btn-sm btn-secondary" title="توليد كود جديد">
                🔄 تجديد
            </button>
        </form>
    </div>
</div>

{{-- KPI Summary Row --}}
<div class="kpi-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom:1.5rem;">
    <div class="kpi-card" style="padding:1.25rem;">
        <div class="kpi-card-header" style="margin-bottom:0.5rem;">
            <span class="kpi-label">السكان المفعلين</span>
            <div class="kpi-icon-box emerald" style="width:36px; height:36px;">👥</div>
        </div>
        <div class="kpi-value num" style="font-size:1.75rem; margin-bottom:0;">
            {{ $activeCount }} <span class="unit">ساكن</span>
        </div>
    </div>

    <div class="kpi-card" style="padding:1.25rem;">
        <div class="kpi-card-header" style="margin-bottom:0.5rem;">
            <span class="kpi-label">طلبات في انتظار التفعيل</span>
            <div class="kpi-icon-box amber" style="width:36px; height:36px;">⏳</div>
        </div>
        <div class="kpi-value num" style="font-size:1.75rem; margin-bottom:0; color: {{ $pendingCount > 0 ? 'var(--color-warning)' : 'var(--text-primary)' }};">
            {{ $pendingCount }} <span class="unit">طلب</span>
        </div>
    </div>
</div>

{{-- Filters Card --}}
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-body" style="padding:1.25rem;">
        <form method="GET" action="{{ route('portal.residents.index') }}" style="display:flex; align-items:center; gap:1rem; flex-wrap:wrap;">
            <div class="search-box" style="flex:1;">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="ابحث بالاسم، الهاتف أو البريد الإلكتروني...">
                <span class="search-icon">🔍</span>
            </div>

            <select name="status" class="form-select" style="width:auto; min-width:180px;">
                <option value="">جميع الحالات</option>
                <option value="ACTIVE" {{ request('status') == 'ACTIVE' ? 'selected' : '' }}>حساب مفعل (نشط)</option>
                <option value="PENDING" {{ request('status') == 'PENDING' ? 'selected' : '' }}>في انتظار الموافقة</option>
            </select>

            <button type="submit" class="btn btn-primary btn-sm" style="padding:0.75rem 1.25rem;">
                تطبيق الفلترة
            </button>

            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('portal.residents.index') }}" class="btn btn-secondary btn-sm" style="padding:0.75rem 1.25rem;">
                    إلغاء الفلاتر
                </a>
            @endif
        </form>
    </div>
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
                                <span class="badge badge-neutral num" style="font-weight:800;">
                                    شقة {{ $resident->apartments->first()->unit_number }}
                                </span>
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
