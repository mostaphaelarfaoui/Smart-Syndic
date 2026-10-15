<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $period_title }} - {{ $tenant->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'dejavusans', 'cairo', 'xbriyaz', sans-serif;
            font-size: 9.5px;
            color: #1e293b;
            background: #ffffff;
            direction: rtl;
            text-align: right;
            line-height: 1.4;
        }

        /* ─── Header & Branding ─────────────────────── */
        .report-header {
            width: 100%;
            border-bottom: 2px solid #0f3460;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: top;
        }

        .residence-title {
            font-size: 16px;
            font-weight: bold;
            color: #0f3460;
            margin-bottom: 3px;
        }

        .residence-meta {
            font-size: 9px;
            color: #475569;
            line-height: 1.45;
        }

        .report-badge-box {
            text-align: left;
            direction: ltr;
        }

        .report-badge {
            display: inline-block;
            background-color: #0f3460;
            color: #ffffff;
            font-size: 11px;
            font-weight: bold;
            padding: 5px 12px;
            border-radius: 4px;
            direction: rtl;
            text-align: center;
        }

        .report-ref {
            font-size: 8.5px;
            color: #64748b;
            margin-top: 4px;
            direction: ltr;
        }

        .period-card {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-right: 4px solid #0d9488;
            border-radius: 4px;
            padding: 8px 12px;
            margin-bottom: 14px;
        }

        .period-title {
            font-size: 13px;
            font-weight: bold;
            color: #0f3460;
        }

        .period-sub {
            font-size: 9.5px;
            color: #475569;
            margin-top: 2px;
        }

        /* ─── Executive Summary KPI Grid ────────────── */
        .kpi-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px;
            margin-bottom: 14px;
        }

        .kpi-box {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 8px 10px;
            text-align: center;
            vertical-align: middle;
        }

        .kpi-box.emerald {
            background-color: #ecfdf5;
            border-color: #a7f3d0;
        }

        .kpi-box.rose {
            background-color: #fff1f2;
            border-color: #fecdd3;
        }

        .kpi-box.navy {
            background-color: #eff6ff;
            border-color: #bfdbfe;
        }

        .kpi-box-label {
            font-size: 9px;
            font-weight: bold;
            color: #475569;
            margin-bottom: 4px;
        }

        .kpi-box-val {
            font-size: 15px;
            font-weight: bold;
        }

        .kpi-box-sub {
            font-size: 8px;
            color: #64748b;
            margin-top: 2px;
        }

        /* ─── Section Headings ──────────────────────── */
        .section-heading {
            width: 100%;
            background-color: #0f3460;
            color: #ffffff;
            font-size: 10.5px;
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 3px;
            margin-top: 14px;
            margin-bottom: 6px;
        }

        .section-heading.green {
            background-color: #047857;
        }

        .section-heading.red {
            background-color: #be123c;
        }

        /* ─── Data Tables ───────────────────────────── */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .data-table th {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            color: #0f3460;
            font-size: 8.5px;
            font-weight: bold;
            padding: 5px 6px;
            text-align: center;
        }

        .data-table td {
            border: 1px solid #e2e8f0;
            padding: 5px 6px;
            font-size: 8.5px;
            vertical-align: middle;
        }

        .data-table tr.even {
            background-color: #f8fafc;
        }

        .data-table tr.total-row {
            background-color: #e2e8f0;
            font-weight: bold;
        }

        .data-table tr.total-row td {
            border-top: 2px solid #0f3460;
            border-bottom: 2px solid #0f3460;
            font-size: 9.5px;
            color: #0f3460;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .num { direction: ltr; display: inline-block; }

        .badge-method {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 7.5px;
            font-weight: bold;
            background-color: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
        }

        .badge-cat {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 7.5px;
            font-weight: bold;
            background-color: #f1f5f9;
            color: #0f3460;
        }

        .badge-paid {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 7.5px;
            font-weight: bold;
            background-color: #dcfce7;
            color: #15803d;
            border: 1px solid #86efac;
        }

        .badge-partial {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 7.5px;
            font-weight: bold;
            background-color: #fef3c7;
            color: #b45309;
            border: 1px solid #fde68a;
        }

        .badge-unpaid {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 7.5px;
            font-weight: bold;
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
        }

        .data-table tr.row-paid td {
            background-color: #f0fdf4;
        }

        .data-table tr.row-partial td {
            background-color: #fffbeb;
        }

        .data-table tr.row-unpaid td {
            background-color: #fff5f5;
        }

        /* ─── Breakdown & Mini Tables ───────────────── */
        .mini-tables-wrap {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            margin-bottom: 12px;
        }

        .mini-tables-wrap td {
            vertical-align: top;
            width: 50%;
            padding: 0 4px;
        }

        /* ─── Certification & Signatures ─────────────── */
        .cert-box {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 10px 14px;
            margin-top: 14px;
            margin-bottom: 12px;
            font-size: 9px;
            line-height: 1.5;
            color: #334155;
        }

        .sign-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        .sign-table td {
            width: 50%;
            vertical-align: top;
            padding: 8px;
        }

        .stamp-preview {
            max-width: 140px;
            max-height: 90px;
            display: block;
            margin-top: 6px;
        }

        .sign-box {
            border: 1px dashed #94a3b8;
            border-radius: 4px;
            min-height: 85px;
            padding: 8px;
            text-align: center;
        }
    </style>
</head>
<body>

    {{-- Official Header --}}
    <div class="report-header">
        <table class="header-table">
            <tr>
                <td style="width: 55%;">
                    <div class="residence-title">🏢 {{ $tenant->name }}</div>
                    <div class="residence-meta">
                        <div><strong>الموقع العقاري:</strong> إقامة سكنية مشتركة (Loi 18-00 sur la copropriété)</div>
                        <div><strong>عدد العمارات:</strong> {{ $buildings_count }} عمارة | <strong>عدد الشقق:</strong> {{ $apartments_count }} شقة</div>
                        @if($tenant->bank_rib)
                            <div><strong>الحساب البنكي (RIB):</strong> <span class="num">{{ $tenant->bank_rib }}</span></div>
                        @endif
                        @if($actor)
                            <div><strong>المسير (السانديك):</strong> {{ $actor->full_name }} ({{ $actor->phone ?? $actor->email }})</div>
                        @endif
                    </div>
                </td>
                <td style="width: 45%;" class="report-badge-box">
                    <div class="report-badge">
                        @if($scope === 'both')
                            📑 التقرير المالي والمحاسبي الشامل
                        @elseif($scope === 'revenues')
                            📈 تقرير المداخيل والتحصيلات الرسمية
                        @else
                            📉 تقرير المصاريف والنفقات المؤداة
                        @endif
                    </div>
                    <div class="report-ref">
                        <strong>رقم الوثيقة:</strong> {{ $reference_number }}<br>
                        <strong>تاريخ وتوقيت الإصدار:</strong> {{ $generated_at->format('Y-m-d H:i') }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Period Banner --}}
    <div class="period-card">
        <div class="period-title">📅 النطاق الزمني للتقرير: {{ $period_title }}</div>
        <div class="period-sub">
            {{ $period_subtitle }} — من تاريخ <span class="num">{{ $start_date->format('d/m/Y') }}</span> إلى تاريخ <span class="num">{{ $end_date->format('d/m/Y') }}</span>
        </div>
    </div>

    {{-- Financial KPI Summary Grid --}}
    <table class="kpi-table">
        <tr>
            @if($scope === 'both' || $scope === 'revenues')
                <td class="kpi-box emerald" style="width: {{ $scope === 'both' ? '33.3%' : '50%' }};">
                    <div class="kpi-box-label">💰 مجموع المداخيل المستخلصة</div>
                    <div class="kpi-box-val" style="color: #047857;">
                        <span class="num">{{ number_format($total_revenues, 2) }}</span> <small style="font-size: 10px;">د.م</small>
                    </div>
                    <div class="kpi-box-sub">إجمالي {{ $payments_count }} وصل أداء رسمي</div>
                </td>
            @endif

            @if($scope === 'both' || $scope === 'expenses')
                <td class="kpi-box rose" style="width: {{ $scope === 'both' ? '33.3%' : '50%' }};">
                    <div class="kpi-box-label">🧾 مجموع المصاريف والنفقات</div>
                    <div class="kpi-box-val" style="color: #be123c;">
                        <span class="num">{{ number_format($total_expenses, 2) }}</span> <small style="font-size: 10px;">د.م</small>
                    </div>
                    <div class="kpi-box-sub">إجمالي {{ $expenses_count }} بند مصروف مسجل</div>
                </td>
            @endif

            @if($scope === 'both')
                <td class="kpi-box {{ $balance_status === 'SURPLUS' ? 'emerald' : 'rose' }}" style="width: 33.3%;">
                    <div class="kpi-box-label">⚖️ الرصيد الصافي (الفائض / العجز)</div>
                    <div class="kpi-box-val" style="color: {{ $balance_status === 'SURPLUS' ? '#047857' : '#be123c' }};">
                        <span class="num">{{ ($net_balance >= 0 ? '+' : '') . number_format($net_balance, 2) }}</span> <small style="font-size: 10px;">د.م</small>
                    </div>
                    <div class="kpi-box-sub">
                        {{ $balance_status === 'SURPLUS' ? 'فائض مالي متاح بالخزينة ✓' : 'عجز مالي مسجل للفترة ⚠' }}
                    </div>
                </td>
            @endif
        </tr>
    </table>

    {{-- SECTION: APARTMENTS PAYMENT STATUS (PAID & UNPAID) --}}
    @if(($scope === 'both' || $scope === 'revenues') && !empty($apartments_status))
        <div class="section-heading" style="background-color: #0d9488; color: #ffffff;">
            🏢 كشف وضعية استخلاص اشتراكات الشقق (الخالصين وغير الخالصين) — État des Cotisations
        </div>

        {{-- Collection KPI Mini Bar --}}
        <table style="width: 100%; border-collapse: separate; border-spacing: 4px; margin-bottom: 8px;">
            <tr>
                <td style="background-color: #f8fafc; border: 1px solid #cbd5e1; border-radius: 4px; padding: 6px 8px; text-align: center; width: 25%;">
                    <div style="font-size: 8px; color: #64748b; font-weight: bold;">إجمالي الشقق المطالبة:</div>
                    <div style="font-size: 11px; font-weight: bold; color: #0f3460;" class="num">{{ $total_billable_apartments }} شقة</div>
                    <div style="font-size: 7.5px; color: #64748b;">المجموع المستحق: <span class="num font-bold">{{ number_format($total_expected_subscriptions, 2) }}</span> د.م</div>
                </td>
                <td style="background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 4px; padding: 6px 8px; text-align: center; width: 25%;">
                    <div style="font-size: 8px; color: #047857; font-weight: bold;">الشقق الخالصة (سددت):</div>
                    <div style="font-size: 11px; font-weight: bold; color: #047857;" class="num">{{ $paid_apartments_count }} شقة ({{ $collection_rate }}%)</div>
                    <div style="font-size: 7.5px; color: #047857;">المحصل: <span class="num font-bold">{{ number_format($total_collected_subscriptions, 2) }}</span> د.م</div>
                </td>
                <td style="background-color: #fffbeb; border: 1px solid #fde68a; border-radius: 4px; padding: 6px 8px; text-align: center; width: 25%;">
                    <div style="font-size: 8px; color: #b45309; font-weight: bold;">أداء جزئي:</div>
                    <div style="font-size: 11px; font-weight: bold; color: #b45309;" class="num">{{ $partial_apartments_count }} شقة</div>
                    <div style="font-size: 7.5px; color: #b45309;">سداد غير مكتمل للفترة</div>
                </td>
                <td style="background-color: #fff1f2; border: 1px solid #fecdd3; border-radius: 4px; padding: 6px 8px; text-align: center; width: 25%;">
                    <div style="font-size: 8px; color: #be123c; font-weight: bold;">الشقق غير الخالصة (متأخرات):</div>
                    <div style="font-size: 11px; font-weight: bold; color: #be123c;" class="num">{{ $unpaid_apartments_count }} شقة</div>
                    <div style="font-size: 7.5px; color: #be123c;">المتأخرات: <span class="num font-bold">{{ number_format($total_remaining_arrears, 2) }}</span> د.م</div>
                </td>
            </tr>
        </table>

        {{-- Detailed Apartments Table --}}
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 4%;">#</th>
                    <th style="width: 14%;">الشقة / العمارة</th>
                    <th style="width: 22%;">الساكن / المالك الرئيسي</th>
                    <th style="width: 13%;">الهاتف</th>
                    <th style="width: 11%;">الواجب (د.م)</th>
                    <th style="width: 11%;">المؤدى (د.م)</th>
                    <th style="width: 11%;">الباقي (د.م)</th>
                    <th style="width: 14%;">الوضعية</th>
                </tr>
            </thead>
            <tbody>
                @foreach($apartments_status as $idx => $row)
                    @php
                        $rowClass = match($row['status']) {
                            'PAID'    => 'row-paid',
                            'PARTIAL' => 'row-partial',
                            default   => 'row-unpaid',
                        };
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td class="text-center font-bold">
                            شقة {{ $row['unit_number'] }}
                            @if($row['building_name'] && $row['building_name'] !== '—')
                                <small style="color: #64748b; font-weight: normal;">({{ $row['building_name'] }})</small>
                            @endif
                        </td>
                        <td>{{ $row['resident_name'] }}</td>
                        <td class="text-center num" style="font-size: 8px;">{{ $row['phone'] }}</td>
                        <td class="text-left num">{{ number_format($row['expected_fee'], 2) }}</td>
                        <td class="text-left num font-bold" style="color: {{ $row['paid_amount'] > 0 ? '#047857' : '#64748b' }};">
                            {{ number_format($row['paid_amount'], 2) }}
                        </td>
                        <td class="text-left num font-bold" style="color: {{ $row['remaining_due'] > 0 ? '#be123c' : '#047857' }};">
                            {{ number_format($row['remaining_due'], 2) }}
                        </td>
                        <td class="text-center">
                            @if($row['status'] === 'PAID')
                                <span class="badge-paid">✓ خالص</span>
                                @if(!empty($row['receipts']))
                                    <div style="font-size: 7px; color: #047857; margin-top: 1px;">وصل: {{ $row['receipts'][0] }}</div>
                                @endif
                            @elseif($row['status'] === 'PARTIAL')
                                <span class="badge-partial">⏳ أداء جزئي</span>
                                @if(!empty($row['receipts']))
                                    <div style="font-size: 7px; color: #b45309; margin-top: 1px;">وصل: {{ $row['receipts'][0] }}</div>
                                @endif
                            @else
                                <span class="badge-unpaid">✗ غير خالص</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="4" class="text-right font-bold" style="padding-right: 10px;">
                        <strong>المجموع الإجمالي لاشتراكات الشقق ({{ $total_billable_apartments }} شقة):</strong>
                    </td>
                    <td class="text-left num font-bold">{{ number_format($total_expected_subscriptions, 2) }} د.م</td>
                    <td class="text-left num font-bold" style="color: #047857;">{{ number_format($total_collected_subscriptions, 2) }} د.م</td>
                    <td class="text-left num font-bold" style="color: #be123c;">{{ number_format($total_remaining_arrears, 2) }} د.م</td>
                    <td class="text-center font-bold" style="color: #0f3460;">{{ $collection_rate }}% تحصيل</td>
                </tr>
            </tbody>
        </table>
        <div style="margin-bottom: 14px;"></div>
    @endif

    {{-- SECTION 1: DETAILED REVENUES TABLE --}}
    @if($scope === 'both' || $scope === 'revenues')
        <div class="section-heading green">
            📈 جدول المداخيل والتحصيلات المفصل (Recettes & Cotisations)
        </div>

        @if($payments->isEmpty())
            <div style="padding: 12px; text-align: center; color: #64748b; background-color: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 4px; margin-bottom: 10px;">
                لم يتم تسجيل أي استخلاصات أو مداخيل خلال هذه الفترة المحددة.
            </div>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 4%;">#</th>
                        <th style="width: 17%;">رقم الوصل</th>
                        <th style="width: 11%;">تاريخ الأداء</th>
                        <th style="width: 14%;">الشقة / العمارة</th>
                        <th style="width: 19%;">الساكن / المالك</th>
                        <th style="width: 11%;">طريقة الدفع</th>
                        <th style="width: 12%;">الشهور المغطاة</th>
                        <th style="width: 12%;">المبلغ (د.م)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payments as $index => $payment)
                        @php
                            $apt = $payment->apartment;
                            $res = $apt?->residents?->first();
                            $months = is_array($payment->covered_months) ? implode(', ', $payment->covered_months) : ($payment->covered_months ?? '-');
                        @endphp
                        <tr class="{{ $index % 2 === 1 ? 'even' : '' }}">
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td class="text-center font-bold" style="font-weight: bold; color: #0f3460;">{{ $payment->receipt_number }}</td>
                            <td class="text-center num">{{ \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') }}</td>
                            <td class="text-center">
                                شقة {{ $apt?->unit_number ?? $apt?->number ?? '-' }}
                                @if($apt?->building)
                                    <small style="color: #64748b;">({{ $apt->building->name }})</small>
                                @endif
                            </td>
                            <td>{{ $res?->full_name ?? ($payment->recorder?->full_name ?? 'الساكن') }}</td>
                            <td class="text-center">
                                <span class="badge-method">
                                    {{ \App\Services\FinancialReportService::PAYMENT_METHODS[strtoupper($payment->payment_method)] ?? $payment->payment_method }}
                                </span>
                            </td>
                            <td class="text-center" style="font-size: 8px;">{{ $months }}</td>
                            <td class="text-left font-bold num" style="font-weight: bold; color: #047857;">
                                {{ number_format($payment->amount_paid, 2) }}
                            </td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="7" class="text-right" style="padding-right: 12px;">
                            <strong>مجموع المداخيل المستخلصة:</strong>
                            <span style="font-weight: normal; font-size: 8.5px; color: #475569; margin-right: 8px;">
                                ({{ $total_revenues_words }})
                            </span>
                        </td>
                        <td class="text-left num" style="font-weight: bold; color: #047857;">
                            {{ number_format($total_revenues, 2) }} د.م
                        </td>
                    </tr>
                </tbody>
            </table>

            {{-- Revenues by Method Mini Table --}}
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 12px;">
                <tr>
                    <td style="font-size: 8.5px; color: #475569;">
                        <strong>تفصيل المداخيل حسب طريقة الدفع:</strong>
                        نقداً: <span class="num font-bold">{{ number_format($revenues_by_method['CASH'], 2) }} د.م</span> |
                        تحويل بنكي: <span class="num font-bold">{{ number_format($revenues_by_method['BANK_TRANSFER'], 2) }} د.م</span> |
                        شيك: <span class="num font-bold">{{ number_format($revenues_by_method['CHEQUE'], 2) }} د.م</span>
                    </td>
                </tr>
            </table>
        @endif
    @endif

    {{-- SECTION 2: DETAILED EXPENSES TABLE --}}
    @if($scope === 'both' || $scope === 'expenses')
        <div class="section-heading red" style="{{ $scope === 'both' ? 'page-break-before: auto;' : '' }}">
            📉 جدول المصاريف والنفقات المؤداة (Dépenses & Charges)
        </div>

        @if($expenses->isEmpty())
            <div style="padding: 12px; text-align: center; color: #64748b; background-color: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 4px; margin-bottom: 10px;">
                لم يتم تسجيل أي مصاريف أو نفقات خلال هذه الفترة المحددة.
            </div>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 4%;">#</th>
                        <th style="width: 11%;">تاريخ الصرف</th>
                        <th style="width: 18%;">صنف المصروف</th>
                        <th style="width: 32%;">بيان النفقة / المستفيد</th>
                        <th style="width: 12%;">المرفق / العمارة</th>
                        <th style="width: 11%;">طريقة الصرف</th>
                        <th style="width: 12%;">المبلغ (د.م)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($expenses as $eIndex => $expense)
                        @php
                            $catConfig = \App\Services\FinancialReportService::EXPENSE_CATEGORIES[strtoupper($expense->category)] ?? ['label' => $expense->category, 'icon' => '📋'];
                        @endphp
                        <tr class="{{ $eIndex % 2 === 1 ? 'even' : '' }}">
                            <td class="text-center">{{ $eIndex + 1 }}</td>
                            <td class="text-center num">{{ \Carbon\Carbon::parse($expense->expense_date)->format('d/m/Y') }}</td>
                            <td>
                                <span class="badge-cat">{{ $catConfig['icon'] }} {{ $catConfig['label'] }}</span>
                            </td>
                            <td>{{ $expense->title }}</td>
                            <td class="text-center">{{ $expense->building?->name ?? 'مرافق الإقامة العامة' }}</td>
                            <td class="text-center">
                                <span class="badge-method">
                                    {{ \App\Services\FinancialReportService::PAYMENT_METHODS[strtoupper($expense->payment_method)] ?? ($expense->payment_method ?? 'نقداً') }}
                                </span>
                            </td>
                            <td class="text-left font-bold num" style="font-weight: bold; color: #be123c;">
                                {{ number_format($expense->amount, 2) }}
                            </td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="6" class="text-right" style="padding-right: 12px;">
                            <strong>مجموع المصاريف المؤداة:</strong>
                            <span style="font-weight: normal; font-size: 8.5px; color: #475569; margin-right: 8px;">
                                ({{ $total_expenses_words }})
                            </span>
                        </td>
                        <td class="text-left num" style="font-weight: bold; color: #be123c;">
                            {{ number_format($total_expenses, 2) }} د.م
                        </td>
                    </tr>
                </tbody>
            </table>

            {{-- Category Breakdown Table --}}
            <div style="font-weight: bold; font-size: 9.5px; color: #0f3460; margin: 8px 0 4px;">
                📊 الجدول التحليلي للمصاريف حسب الأصناف:
            </div>
            <table class="data-table" style="margin-bottom: 14px;">
                <thead>
                    <tr>
                        <th style="width: 35%;">صنف المصروف</th>
                        <th style="width: 20%;">عدد الفواتير / العمليات</th>
                        <th style="width: 25%;">المبلغ الإجمالي (د.م)</th>
                        <th style="width: 20%;">النسبة المئوية (%)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($expenses_by_category as $catK => $catV)
                        @if($catV['count'] > 0)
                            <tr>
                                <td>{{ $catV['icon'] }} <strong>{{ $catV['label'] }}</strong></td>
                                <td class="text-center">{{ $catV['count'] }} عمليـة</td>
                                <td class="text-left num" style="font-weight: bold;">{{ number_format($catV['amount'], 2) }}</td>
                                <td class="text-center num">
                                    <strong>{{ $catV['percentage'] }}%</strong>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        @endif
    @endif

    {{-- SECTION 3: SYNTHESIS & RECAPITULATION (when both) --}}
    @if($scope === 'both')
        <div class="section-heading" style="background-color: #1e293b;">
            ⚖️ الخلاصة المحاسبية والمركز المالي للفترة
        </div>
        <table class="data-table" style="margin-bottom: 14px;">
            <thead>
                <tr>
                    <th style="width: 50%;">البيان المحاسبي</th>
                    <th style="width: 50%;">المبلغ الصافي (درهم مغربي)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>إجمالي المداخيل والاشتراكات المحصلة</strong></td>
                    <td class="text-left num" style="color: #047857; font-weight: bold;">
                        + {{ number_format($total_revenues, 2) }} د.م
                    </td>
                </tr>
                <tr class="even">
                    <td><strong>إجمالي النفقات والمصاريف التشغيلية</strong></td>
                    <td class="text-left num" style="color: #be123c; font-weight: bold;">
                        - {{ number_format($total_expenses, 2) }} د.م
                    </td>
                </tr>
                <tr class="total-row">
                    <td>
                        <strong>الرصيد الصافي المتبقي للفترة (Solde Net):</strong><br>
                        <small style="font-weight: normal; color: #475569;">
                            ({{ $balance_status === 'SURPLUS' ? 'فائض مالي متاح بالخزينة' : 'عجز مالي' }}: {{ $net_balance_words }})
                        </small>
                    </td>
                    <td class="text-left num" style="font-size: 11px; font-weight: bold; color: {{ $balance_status === 'SURPLUS' ? '#047857' : '#be123c' }};">
                        {{ ($net_balance >= 0 ? '+' : '') . number_format($net_balance, 2) }} د.م
                    </td>
                </tr>
            </tbody>
        </table>
    @endif

    {{-- Certification & Signatures Block --}}
    <div class="cert-box">
        <strong>شهادة المطابقة والشفافية المالية:</strong><br>
        يشهد مكتب السانديك القانوني لإقامة <strong>{{ $tenant->name }}</strong> بأن هذا التقرير المالي المعتمد تم استخراجه من السجلات المحاسبية الرسمية للإقامة عبر منصة <em>سمارت سانديك</em>، وهو يعكس بصورة أمينة ومطابقة لكافة المداخيل والوصولات والمصاريف المؤداة خلال النطاق الزمني المحدد أعلاه.
    </div>

    <table class="sign-table">
        <tr>
            <td style="text-align: right;">
                <div style="font-size: 9px; color: #475569; margin-bottom: 4px;">
                    <strong>حرر بتاريخ:</strong> {{ $generated_at->format('d/m/Y') }} بمدينة الإقامة
                </div>
                <div style="font-size: 9px; color: #475569;">
                    <strong>السانديك المسير:</strong> {{ $actor?->full_name ?? 'إدارة السانديك' }}
                </div>
            </td>
            <td style="text-align: left;">
                <div class="sign-box">
                    <div style="font-size: 8.5px; font-weight: bold; color: #0f3460; margin-bottom: 4px;">
                        توقيع وخاتم السانديك المعتمد
                    </div>
                    @if(!empty($stamp_base64))
                        <img src="{{ $stamp_base64 }}" class="stamp-preview" alt="خاتم السانديك">
                    @else
                        <div style="height: 50px; line-height: 50px; color: #94a3b8; font-size: 8px;">
                            (خاتم وتوقيع السانديك الرسمي)
                        </div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
