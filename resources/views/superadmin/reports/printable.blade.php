<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التقرير التنفيذي الشامل للمنصة — {{ $summary['period_title'] }}</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        :root {
            --brand-primary: #0f172a;
            --brand-accent: #2563eb;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --bg-table-alt: #f8fafc;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Cairo', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f1f5f9;
            color: var(--text-main);
            font-size: 13px;
            line-height: 1.5;
            direction: rtl;
            text-align: right;
            padding-bottom: 3rem;
        }

        /* Top Action Bar (Screen Only) */
        .no-print-toolbar {
            background: #1e293b;
            color: #ffffff;
            padding: 0.85rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }

        .toolbar-title {
            font-size: 0.95rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .toolbar-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .t-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.45rem 1rem;
            border-radius: 6px;
            font-size: 0.825rem;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
        }

        .t-btn-primary {
            background: #2563eb;
            color: #ffffff;
        }
        .t-btn-primary:hover { background: #1d4ed8; }

        .t-btn-danger {
            background: #dc2626;
            color: #ffffff;
        }
        .t-btn-danger:hover { background: #b91c1c; }

        .t-btn-outline {
            background: rgba(255,255,255,0.1);
            color: #ffffff;
            border: 1px solid rgba(255,255,255,0.25);
        }
        .t-btn-outline:hover { background: rgba(255,255,255,0.2); }

        /* Report Paper (A4 Page Container) */
        .report-page {
            max-width: 1050px;
            margin: 2rem auto;
            background: #ffffff;
            padding: 2.5rem 3rem;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
        }

        /* Document Header */
        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid #0f172a;
            margin-bottom: 1.75rem;
        }

        .doc-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .brand-logo-sq {
            width: 44px;
            height: 44px;
            background: #0f172a;
            color: #ffffff;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .brand-name {
            font-size: 1.35rem;
            font-weight: 900;
            color: #0f172a;
            line-height: 1.1;
        }

        .brand-tagline {
            font-size: 0.75rem;
            color: var(--text-muted);
            letter-spacing: 0.05em;
        }

        .doc-meta {
            text-align: left;
            direction: ltr;
            font-size: 0.75rem;
            color: var(--text-muted);
            line-height: 1.45;
        }

        .doc-meta strong {
            color: var(--text-main);
        }

        .report-badge {
            display: inline-block;
            background: #f1f5f9;
            color: #0f172a;
            font-size: 0.7rem;
            font-weight: 800;
            padding: 0.2rem 0.6rem;
            border-radius: 4px;
            border: 1px solid #cbd5e1;
            margin-bottom: 0.25rem;
        }

        /* Report Title & Subject */
        .report-subject {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-right: 5px solid #2563eb;
            padding: 1.25rem 1.5rem;
            border-radius: 6px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .report-title-text {
            font-size: 1.25rem;
            font-weight: 900;
            color: #0f172a;
            margin-bottom: 0.25rem;
        }

        .report-sub-text {
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        .report-period-pill {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 0.4rem 0.85rem;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 700;
            color: #2563eb;
        }

        /* Executive KPIs Grid */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .kpi-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 1rem;
        }

        .kpi-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .kpi-value {
            font-size: 1.35rem;
            font-weight: 900;
            color: #0f172a;
            font-family: 'Segoe UI', Tahoma, sans-serif;
            direction: ltr;
            text-align: right;
        }

        .kpi-sub {
            font-size: 0.7rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }

        /* Tables */
        .section-title {
            font-size: 1rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .doc-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
            margin-bottom: 2rem;
        }

        .doc-table th {
            background: #0f172a;
            color: #ffffff;
            font-weight: 700;
            text-align: right;
            padding: 0.75rem 0.85rem;
            border: 1px solid #0f172a;
            font-size: 0.8rem;
        }

        .doc-table td {
            padding: 0.7rem 0.85rem;
            border: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        .doc-table tr:nth-child(even) {
            background: #f8fafc;
        }

        .num {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            font-weight: 700;
        }

        .status-badge {
            display: inline-block;
            padding: 0.2rem 0.55rem;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 800;
        }
        .status-active { background: #dcfce7; color: #15803d; }
        .status-suspended { background: #fee2e2; color: #b91c1c; }
        .status-pending { background: #fef3c7; color: #b45309; }

        /* Document Footer & Signatures */
        .doc-signatures {
            margin-top: 3rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding-top: 1.5rem;
            border-top: 1px dashed #cbd5e1;
        }

        .sign-box {
            width: 250px;
            text-align: center;
        }

        .sign-title {
            font-size: 0.85rem;
            font-weight: 800;
            margin-bottom: 4rem;
            color: #0f172a;
        }

        .sign-line {
            border-top: 1px solid #94a3b8;
            padding-top: 0.5rem;
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .doc-legal-footer {
            margin-top: 2.5rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
            font-size: 0.7rem;
            color: #94a3b8;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* ─── PRINT MEDIA OPTIMIZATIONS ─── */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
                font-size: 11pt !important;
                color: #000000 !important;
            }

            .no-print-toolbar {
                display: none !important;
            }

            .report-page {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
            }

            .doc-table th {
                background: #1e293b !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .report-subject {
                background: #f8fafc !important;
                border: 1px solid #cbd5e1 !important;
                border-right: 5px solid #2563eb !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .kpi-card {
                background: #f8fafc !important;
                border: 1px solid #cbd5e1 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            tr {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

    {{-- Screen-only Toolbar --}}
    <header class="no-print-toolbar">
        <div class="toolbar-title">
            <span>📊</span>
            <span>معاينة التقرير التنفيذي الرسمي — جاهز للطباعة والحفظ</span>
        </div>
        <div class="toolbar-actions">
            <a href="{{ route('superadmin.reports.index', request()->query()) }}" class="t-btn t-btn-outline">
                <span>&rarr;</span>
                <span>العودة للوحة التقارير</span>
            </a>
            <a href="{{ route('superadmin.reports.pdf', request()->query()) }}" class="t-btn t-btn-danger">
                <span>📥</span>
                <span>تحميل PDF رسمي</span>
            </a>
            <button type="button" class="t-btn t-btn-primary" onclick="window.print()">
                <span>🖨️</span>
                <span>طباعة الآن (Print)</span>
            </button>
        </div>
    </header>

    {{-- Report Paper --}}
    <main class="report-page">

        {{-- Official Header --}}
        <header class="doc-header">
            <div class="doc-brand">
                <div class="brand-logo-sq">🏢</div>
                <div>
                    <div class="brand-name">Smart Syndic</div>
                    <div class="brand-tagline">منظومة تدبير الإقامات والمجمعات السكنية بالمملكة المغربية</div>
                </div>
            </div>

            <div class="doc-meta">
                <div class="report-badge">وثيقة رسمية معتمدة</div>
                <div>المرجع: <strong>{{ $summary['ref_number'] }}</strong></div>
                <div>تاريخ الاستخراج: <strong>{{ now()->translatedFormat('d F Y - H:i') }}</strong></div>
                <div>المُستخرج: <strong>{{ auth()->user()->full_name }} (Super Admin)</strong></div>
            </div>
        </header>

        {{-- Subject & Period Card --}}
        <section class="report-subject">
            <div>
                <h1 class="report-title-text">التقرير التنفيذي المالي ونمو الإقامات المشتركة</h1>
                <p class="report-sub-text">كشف إحصائي وتحليلي لحركة المقبوضات والنفقات وتدفقات الصناديق عبر المنصة</p>
            </div>
            <div class="report-period-pill">
                📅 {{ $summary['period_title'] }}
            </div>
        </section>

        {{-- KPIs Summary Grid --}}
        <section class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-label">إجمالي الإقامات المسجلة</div>
                <div class="kpi-value">{{ $summary['total_tenants'] }}</div>
                <div class="kpi-sub">{{ $summary['active_tenants'] }} نشطة • {{ $summary['dormant_count'] }} خاملة</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-label">مجموع المقبوضات المحصلة</div>
                <div class="kpi-value" style="color:#059669;">{{ number_format($summary['total_collected'], 2) }} <span style="font-size:0.75rem;">DH</span></div>
                <div class="kpi-sub">المداخيل الإجمالية للفترة</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-label">مجموع المصاريف والنفقات</div>
                <div class="kpi-value" style="color:#dc2626;">{{ number_format($summary['total_expenses'], 2) }} <span style="font-size:0.75rem;">DH</span></div>
                <div class="kpi-sub">النفقات التشغيلية المعتمدة</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-label">صافي رصيد الصناديق (الخزينة)</div>
                <div class="kpi-value" style="color:{{ $summary['platform_net'] >= 0 ? '#2563eb' : '#dc2626' }};">
                    {{ number_format($summary['platform_net'], 2) }} <span style="font-size:0.75rem;">DH</span>
                </div>
                <div class="kpi-sub">{{ $summary['platform_net'] >= 0 ? 'فائض مالي عام' : 'عجز في الصندوق' }}</div>
            </div>
        </section>

        {{-- Detailed Table --}}
        <section>
            <h2 class="section-title">
                <span>📋</span>
                <span>كشف تفصيلي لجميع الإقامات والتدفقات المالية</span>
            </h2>

            <table class="doc-table">
                <thead>
                    <tr>
                        <th style="width:30px;">#</th>
                        <th>اسم الإقامة / المجمع</th>
                        <th>المدينة</th>
                        <th>الحالة</th>
                        <th>الشقق</th>
                        <th>السكان</th>
                        <th>المقبوضات المحصلة</th>
                        <th>المصاريف</th>
                        <th>الرصيد الصافي</th>
                        <th>آخر حركة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tenants as $index => $t)
                        <tr>
                            <td class="num" style="text-align:center; color:var(--text-muted);">{{ $index + 1 }}</td>
                            <td>
                                <strong>{{ $t->name }}</strong>
                            </td>
                            <td>{{ $t->city ?? '—' }}</td>
                            <td>
                                @if($t->status === 'ACTIVE')
                                    <span class="status-badge status-active">نشطة</span>
                                @elseif($t->status === 'PENDING_APPROVAL')
                                    <span class="status-badge status-pending">قيد الموافقة</span>
                                @else
                                    <span class="status-badge status-suspended">موقوفة</span>
                                @endif
                            </td>
                            <td class="num" style="text-align:center;">{{ $t->apartments_count }}</td>
                            <td class="num" style="text-align:center;">{{ $t->users_count }}</td>
                            <td class="num" style="color:#059669; font-weight:800;">
                                {{ number_format($t->total_collected, 2) }} DH
                            </td>
                            <td class="num" style="color:#dc2626;">
                                {{ number_format($t->total_expenses, 2) }} DH
                            </td>
                            <td class="num" style="font-weight:800; color:{{ $t->net_treasury >= 0 ? '#2563eb' : '#dc2626' }};">
                                {{ number_format($t->net_treasury, 2) }} DH
                            </td>
                            <td style="font-size:0.75rem; color:var(--text-muted);">
                                {{ $t->last_payment_date ? \Carbon\Carbon::parse($t->last_payment_date)->format('Y/m/d') : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" style="text-align:center; padding:2rem; color:var(--text-muted);">
                                لا توجد إقامات مسجلة في هذا النطاق الزمني.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        {{-- Signatures & Stamp Block --}}
        <section class="doc-signatures">
            <div class="sign-box">
                <div class="sign-title">قسم المراقبة والتدقيق المالي</div>
                <div class="sign-line">التوقيع والتاريخ</div>
            </div>

            <div class="sign-box">
                <div class="sign-title">الإدارة العامة لمنصة Smart Syndic</div>
                <div class="sign-line">الخاتم والاعتماد الرسمي</div>
            </div>
        </section>

        {{-- Legal Footer --}}
        <footer class="doc-legal-footer">
            <div>
                تم استخراج هذا التقرير آلياً عبر نظام <strong>Smart Syndic Platform</strong> ويُعد مستنداً محاسبياً داخلياً للمراقبة والتسيير.
            </div>
            <div>
                صفحة 1 من 1
            </div>
        </footer>

    </main>

    @if(!empty($autoPrint))
        <script>
            window.addEventListener('load', () => {
                setTimeout(() => window.print(), 400);
            });
        </script>
    @endif
</body>
</html>
