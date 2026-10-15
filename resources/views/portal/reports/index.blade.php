@extends('layouts.dashboard')

@section('title', 'مركز التقارير المالية — ' . $tenant->name)

@section('content')

{{-- Header --}}
<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1.25rem; margin-bottom:2rem;">
    <div>
        <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.4rem;">
            <a href="{{ route('portal.dashboard') }}" style="color:var(--text-secondary); text-decoration:none; font-size:0.875rem;">لوحة القيادة</a>
            <span style="color:var(--text-muted); font-size:0.8rem;">/</span>
            <span style="color:var(--brand-primary); font-weight:700; font-size:0.875rem;">التقارير المالية</span>
        </div>
        <h1 style="font-size:1.85rem; font-weight:900; color:var(--text-primary); margin:0;">
            📑 مركز التقارير المالية والمحاسبية الرسمية (PDF)
        </h1>
        <p style="color:var(--text-secondary); margin-top:0.35rem; font-size:0.95rem;">
            استخراج بيانات مالية معتمدة وفق القانون 18.00 المنظم للملكية المشتركة، مع التفقيط العربي والخاتم الرسمي.
        </p>
    </div>

    <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">
        <button type="button" class="btn btn-secondary" onclick="openFinancialReportModal('revenues')" style="font-weight:700;">
            <span>📈 تقرير المداخيل فقط</span>
        </button>
        <button type="button" class="btn btn-secondary" onclick="openFinancialReportModal('expenses')" style="font-weight:700;">
            <span>📉 تقرير المصاريف فقط</span>
        </button>
        <button type="button" class="btn btn-primary" onclick="openFinancialReportModal('both')" style="font-weight:800; display:inline-flex; align-items:center; gap:0.5rem; background:linear-gradient(135deg, #0d9488 0%, #059669 100%); border:none; box-shadow:0 4px 14px rgba(13,148,136,0.35);">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span>⚙️ تخصيص تقرير متعدد الشهور</span>
        </button>
    </div>
</div>

{{-- Quick Period Cards Grid --}}
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(360px, 1fr)); gap:1.5rem; margin-bottom:2rem;">

    {{-- 1. Current Month Card --}}
    <div class="card" style="padding:1.5rem; border:2px solid rgba(13,148,136,0.3); border-radius:var(--radius-lg); position:relative; overflow:hidden;">
        <div style="position:absolute; top:1rem; left:1rem;">
            <span class="badge badge-success" style="font-size:0.75rem;">الشهر الحالي</span>
        </div>
        <div style="display:flex; align-items:center; gap:0.85rem; margin-bottom:1rem;">
            <div style="width:44px; height:44px; border-radius:10px; background:rgba(13,148,136,0.12); color:var(--brand-primary); display:flex; align-items:center; justify-content:center; font-size:1.4rem;">
                📅
            </div>
            <div>
                <h3 style="margin:0; font-size:1.15rem; font-weight:800; color:var(--text-primary);">
                    {{ $currentMonthData['period_title'] }}
                </h3>
                <span style="font-size:0.82rem; color:var(--text-secondary);">تقرير دوري شامل حتى تاريخ اليوم</span>
            </div>
        </div>

        {{-- Mini KPIs --}}
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-bottom:1.25rem;">
            <div style="background:var(--bg-secondary, #f8fafc); padding:0.75rem 1rem; border-radius:8px;">
                <div style="font-size:0.75rem; color:var(--text-secondary);">المداخيل المحصلة</div>
                <div class="num" style="font-size:1.1rem; font-weight:800; color:#10b981;">
                    {{ number_format($currentMonthData['total_revenues'], 2) }} <small style="font-size:0.75rem;">د.م</small>
                </div>
            </div>
            <div style="background:var(--bg-secondary, #f8fafc); padding:0.75rem 1rem; border-radius:8px;">
                <div style="font-size:0.75rem; color:var(--text-secondary);">المصاريف والنفقات</div>
                <div class="num" style="font-size:1.1rem; font-weight:800; color:#f43f5e;">
                    {{ number_format($currentMonthData['total_expenses'], 2) }} <small style="font-size:0.75rem;">د.م</small>
                </div>
            </div>
        </div>

        {{-- Net Balance --}}
        <div style="display:flex; justify-content:space-between; align-items:center; padding:0.6rem 0.85rem; background:rgba(13,148,136,0.06); border-radius:8px; margin-bottom:1.25rem; font-size:0.85rem;">
            <span style="color:var(--text-secondary);">الفارق الصافي للشهر:</span>
            <strong class="num" style="font-size:0.95rem; color:{{ $currentMonthData['net_balance'] >= 0 ? '#10b981' : '#f43f5e' }};">
                {{ ($currentMonthData['net_balance'] >= 0 ? '+' : '') . number_format($currentMonthData['net_balance'], 2) }} د.م
            </strong>
        </div>

        {{-- Actions --}}
        <div style="display:flex; gap:0.6rem; flex-wrap:wrap;">
            <a href="{{ route('portal.reports.financial-pdf', ['start_month' => $currentMonth, 'end_month' => $currentMonth, 'scope' => 'both', 'mode' => 'download']) }}" target="_blank" class="btn btn-primary" style="flex:1; justify-content:center; font-weight:800; font-size:0.85rem; background:linear-gradient(135deg, #0d9488 0%, #059669 100%);">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <span>تحميل PDF</span>
            </a>
            <a href="{{ route('portal.reports.financial-pdf', ['start_month' => $currentMonth, 'end_month' => $currentMonth, 'scope' => 'both', 'mode' => 'preview']) }}" target="_blank" class="btn btn-secondary" style="font-weight:700; font-size:0.85rem;" title="معاينة التقرير مباشرة في المتصفح">
                <span>👁️ معاينة</span>
            </a>
        </div>
    </div>

    {{-- 2. Last Month Card --}}
    <div class="card" style="padding:1.5rem; border:1px solid var(--border-color); border-radius:var(--radius-lg); position:relative; overflow:hidden;">
        <div style="position:absolute; top:1rem; left:1rem;">
            <span class="badge badge-neutral" style="font-size:0.75rem;">الشهر الماضي</span>
        </div>
        <div style="display:flex; align-items:center; gap:0.85rem; margin-bottom:1rem;">
            <div style="width:44px; height:44px; border-radius:10px; background:rgba(99,102,241,0.12); color:#6366f1; display:flex; align-items:center; justify-content:center; font-size:1.4rem;">
                ⏮️
            </div>
            <div>
                <h3 style="margin:0; font-size:1.15rem; font-weight:800; color:var(--text-primary);">
                    {{ $lastMonthData['period_title'] }}
                </h3>
                <span style="font-size:0.82rem; color:var(--text-secondary);">الحساب الختامي للشهر المنصرم</span>
            </div>
        </div>

        {{-- Mini KPIs --}}
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-bottom:1.25rem;">
            <div style="background:var(--bg-secondary, #f8fafc); padding:0.75rem 1rem; border-radius:8px;">
                <div style="font-size:0.75rem; color:var(--text-secondary);">المداخيل المحصلة</div>
                <div class="num" style="font-size:1.1rem; font-weight:800; color:#10b981;">
                    {{ number_format($lastMonthData['total_revenues'], 2) }} <small style="font-size:0.75rem;">د.م</small>
                </div>
            </div>
            <div style="background:var(--bg-secondary, #f8fafc); padding:0.75rem 1rem; border-radius:8px;">
                <div style="font-size:0.75rem; color:var(--text-secondary);">المصاريف والنفقات</div>
                <div class="num" style="font-size:1.1rem; font-weight:800; color:#f43f5e;">
                    {{ number_format($lastMonthData['total_expenses'], 2) }} <small style="font-size:0.75rem;">د.م</small>
                </div>
            </div>
        </div>

        {{-- Net Balance --}}
        <div style="display:flex; justify-content:space-between; align-items:center; padding:0.6rem 0.85rem; background:rgba(99,102,241,0.06); border-radius:8px; margin-bottom:1.25rem; font-size:0.85rem;">
            <span style="color:var(--text-secondary);">الفارق الصافي للشهر:</span>
            <strong class="num" style="font-size:0.95rem; color:{{ $lastMonthData['net_balance'] >= 0 ? '#10b981' : '#f43f5e' }};">
                {{ ($lastMonthData['net_balance'] >= 0 ? '+' : '') . number_format($lastMonthData['net_balance'], 2) }} د.م
            </strong>
        </div>

        {{-- Actions --}}
        <div style="display:flex; gap:0.6rem; flex-wrap:wrap;">
            <a href="{{ route('portal.reports.financial-pdf', ['start_month' => $lastMonth, 'end_month' => $lastMonth, 'scope' => 'both', 'mode' => 'download']) }}" target="_blank" class="btn btn-secondary" style="flex:1; justify-content:center; font-weight:700; font-size:0.85rem;">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <span>تحميل PDF</span>
            </a>
            <a href="{{ route('portal.reports.financial-pdf', ['start_month' => $lastMonth, 'end_month' => $lastMonth, 'scope' => 'both', 'mode' => 'preview']) }}" target="_blank" class="btn btn-secondary" style="font-weight:700; font-size:0.85rem;" title="معاينة التقرير مباشرة في المتصفح">
                <span>👁️ معاينة</span>
            </a>
        </div>
    </div>

    {{-- 3. Preset Quick Periods Card --}}
    <div class="card" style="padding:1.5rem; border:1px solid var(--border-color); border-radius:var(--radius-lg);">
        <div style="display:flex; align-items:center; gap:0.85rem; margin-bottom:1rem;">
            <div style="width:44px; height:44px; border-radius:10px; background:rgba(245,158,11,0.12); color:#f59e0b; display:flex; align-items:center; justify-content:center; font-size:1.4rem;">
                ⚡
            </div>
            <div>
                <h3 style="margin:0; font-size:1.15rem; font-weight:800; color:var(--text-primary);">
                    فترات دورية سريعة
                </h3>
                <span style="font-size:0.82rem; color:var(--text-secondary);">استخراج تقارير فصلية أو سنوية بنقرة واحدة</span>
            </div>
        </div>

        <div style="display:flex; flex-direction:column; gap:0.65rem; margin-bottom:1rem;">
            {{-- Last 3 Months --}}
            @php
                $qStart = now()->subMonths(2)->format('Y-m');
                $qEnd   = now()->format('Y-m');
            @endphp
            <div style="display:flex; justify-content:space-between; align-items:center; padding:0.65rem 0.85rem; background:var(--bg-secondary, #f8fafc); border-radius:8px;">
                <div>
                    <strong style="font-size:0.85rem; color:var(--text-primary);">آخر 3 أشهر (الربع السنوي)</strong>
                    <div style="font-size:0.75rem; color:var(--text-secondary);">من {{ $qStart }} إلى {{ $qEnd }}</div>
                </div>
                <a href="{{ route('portal.reports.financial-pdf', ['start_month' => $qStart, 'end_month' => $qEnd, 'scope' => 'both', 'mode' => 'download']) }}" target="_blank" class="btn btn-sm btn-secondary" style="font-weight:700;">
                    <span>تحميل 📥</span>
                </a>
            </div>

            {{-- Full Current Year --}}
            @php
                $yStart = now()->startOfYear()->format('Y-m');
                $yEnd   = now()->format('Y-m');
            @endphp
            <div style="display:flex; justify-content:space-between; align-items:center; padding:0.65rem 0.85rem; background:var(--bg-secondary, #f8fafc); border-radius:8px;">
                <div>
                    <strong style="font-size:0.85rem; color:var(--text-primary);">تقرير سنة {{ $currentYear }} كاملة</strong>
                    <div style="font-size:0.75rem; color:var(--text-secondary);">من يناير {{ $currentYear }} حتى الآن</div>
                </div>
                <a href="{{ route('portal.reports.financial-pdf', ['start_month' => $yStart, 'end_month' => $yEnd, 'scope' => 'both', 'mode' => 'download']) }}" target="_blank" class="btn btn-sm btn-secondary" style="font-weight:700;">
                    <span>تحميل 📥</span>
                </a>
            </div>
        </div>

        <button type="button" onclick="openFinancialReportModal('both')" class="btn btn-secondary" style="width:100%; justify-content:center; font-weight:800; font-size:0.85rem;">
            <span>⚙️ فتح أداة التخصيص الكاملة...</span>
        </button>
    </div>

</div>

{{-- Features & Legal Info Banner --}}
<div class="card" style="padding:1.5rem 1.75rem; border-radius:var(--radius-lg); background:linear-gradient(135deg, rgba(13,148,136,0.05) 0%, rgba(99,102,241,0.03) 100%); border:1px solid rgba(13,148,136,0.18);">
    <h3 style="margin:0 0 1rem 0; font-size:1.1rem; font-weight:800; color:var(--text-primary); display:flex; align-items:center; gap:0.5rem;">
        <span>⚖️ مميزات التقرير المالي المعتمد في Smart Syndic</span>
    </h3>
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:1.25rem;">
        <div style="display:flex; gap:0.75rem;">
            <div style="font-size:1.4rem;">📊</div>
            <div>
                <strong style="font-size:0.9rem; color:var(--text-primary);">جداول تفصيلية بالدفعات والمصاريف</strong>
                <p style="margin:0.25rem 0 0 0; font-size:0.82rem; color:var(--text-secondary);">
                    يسرد التقرير جميع عمليات التحصيل مع أرقام الشقق، والشهور المغطاة، وتفاصيل الفواتير والموردين وأصناف المصاريف.
                </p>
            </div>
        </div>

        <div style="display:flex; gap:0.75rem;">
            <div style="font-size:1.4rem;">✍️</div>
            <div>
                <strong style="font-size:0.9rem; color:var(--text-primary);">تفقيط المبالغ كتابةً باللغة العربية</strong>
                <p style="margin:0.25rem 0 0 0; font-size:0.82rem; color:var(--text-secondary);">
                    يتم تحويل الأرقام تلقائياً إلى نصوص عربية قانونية (مثال: فقط خمسة آلاف وثلاثمائة درهم مغربي لا غير) لمنع أي تلاعب.
                </p>
            </div>
        </div>

        <div style="display:flex; gap:0.75rem;">
            <div style="font-size:1.4rem;">🏛️</div>
            <div>
                <strong style="font-size:0.9rem; color:var(--text-primary);">الخاتم الرسمي ومصادقة السانديك</strong>
                <p style="margin:0.25rem 0 0 0; font-size:0.82rem; color:var(--text-secondary);">
                    يتضمن التقرير خاتم السانديك الرقمي وبيانات الإقامة وتوقيع المسير، جاهز للعرض في الجمع العام أو إرساله للملاك.
                </p>
            </div>
        </div>
    </div>
</div>

@endsection
