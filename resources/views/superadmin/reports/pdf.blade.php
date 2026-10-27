<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>التقرير التنفيذي الشامل للمنصة — {{ $summary['period_title'] }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'dejavusans', 'cairo', sans-serif;
            font-size: 8.5pt;
            color: #0f172a;
            direction: rtl;
            text-align: right;
            line-height: 1.4;
            padding: 15px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }

        .header-table td {
            vertical-align: top;
        }

        .title-box {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-right: 4px solid #2563eb;
            padding: 8px 12px;
            margin-bottom: 14px;
        }

        .kpi-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .kpi-table td {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
            width: 25%;
            text-align: center;
        }

        .kpi-label {
            font-size: 7.5pt;
            color: #64748b;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .kpi-val {
            font-size: 11pt;
            font-weight: bold;
            color: #0f172a;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            margin-bottom: 15px;
        }

        .data-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-size: 7.5pt;
            font-weight: bold;
            padding: 5px 6px;
            border: 1px solid #0f172a;
            text-align: right;
        }

        .data-table td {
            font-size: 7.5pt;
            padding: 5px 6px;
            border: 1px solid #cbd5e1;
            vertical-align: middle;
        }

        .data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        .sign-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
            border-top: 1px dashed #cbd5e1;
            padding-top: 10px;
        }

        .sign-table td {
            width: 50%;
            vertical-align: top;
            text-align: center;
            padding: 10px;
        }
    </style>
</head>
<body>

    {{-- Official Header --}}
    <table class="header-table">
        <tr>
            <td style="width: 60%; text-align: right;">
                <div style="font-size: 14pt; font-weight: bold; color: #0f172a;">Smart Syndic Platform</div>
                <div style="font-size: 8pt; color: #64748b;">منظومة تدبير الإقامات والمجمعات السكنية بالمملكة المغربية</div>
                <div style="font-size: 9pt; font-weight: bold; color: #2563eb; margin-top: 4px;">التقرير التنفيذي والمالي الشامل</div>
            </td>
            <td style="width: 40%; text-align: left; direction: ltr; font-size: 7.5pt; color: #64748b;">
                <div>وثيقة رسمية معتمدة</div>
                <div>المرجع: <strong>{{ $summary['ref_number'] }}</strong></div>
                <div>تاريخ الاستخراج: <strong>{{ now()->format('Y/m/d H:i') }}</strong></div>
                <div>المشرف: <strong>Super Admin</strong></div>
            </td>
        </tr>
    </table>

    {{-- Subject Card --}}
    <div class="title-box">
        <table style="width: 100%;">
            <tr>
                <td style="text-align: right;">
                    <strong style="font-size: 10pt; color: #0f172a;">كشف إحصائي وتحليلي لحركة المقبوضات والنفقات وتدفقات الصناديق عبر المنصة</strong>
                </td>
                <td style="text-align: left; direction: ltr;">
                    <span style="font-size: 8pt; font-weight: bold; color: #2563eb;">الفترة: {{ $summary['period_title'] }}</span>
                </td>
            </tr>
        </table>
    </div>

    {{-- Executive KPIs --}}
    <table class="kpi-table">
        <tr>
            <td>
                <div class="kpi-label">إجمالي الإقامات المسجلة</div>
                <div class="kpi-val">{{ $summary['total_tenants'] }}</div>
                <div style="font-size: 6.5pt; color: #64748b;">{{ $summary['active_tenants'] }} نشطة • {{ $summary['dormant_count'] }} خاملة</div>
            </td>
            <td>
                <div class="kpi-label">مجموع المقبوضات المحصلة</div>
                <div class="kpi-val" style="color: #059669;">{{ number_format($summary['total_collected'], 2) }} DH</div>
                <div style="font-size: 6.5pt; color: #64748b;">المداخيل الإجمالية</div>
            </td>
            <td>
                <div class="kpi-label">مجموع المصاريف والنفقات</div>
                <div class="kpi-val" style="color: #dc2626;">{{ number_format($summary['total_expenses'], 2) }} DH</div>
                <div style="font-size: 6.5pt; color: #64748b;">النفقات المعتمدة</div>
            </td>
            <td>
                <div class="kpi-label">صافي رصيد الصناديق</div>
                <div class="kpi-val" style="color: {{ $summary['platform_net'] >= 0 ? '#2563eb' : '#dc2626' }};">
                    {{ number_format($summary['platform_net'], 2) }} DH
                </div>
                <div style="font-size: 6.5pt; color: #64748b;">{{ $summary['platform_net'] >= 0 ? 'فائض مالي عام' : 'عجز في الصناديق' }}</div>
            </td>
        </tr>
    </table>

    {{-- Table of Tenants --}}
    <div style="font-size: 9pt; font-weight: bold; margin-bottom: 3px; color: #0f172a;">كشف تفصيلي بوضع كل إقامة:</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25px; text-align: center;">#</th>
                <th>اسم الإقامة / المجمع</th>
                <th>المدينة</th>
                <th>الحالة</th>
                <th style="text-align: center;">الشقق</th>
                <th style="text-align: center;">السكان</th>
                <th style="text-align: right;">المقبوضات (DH)</th>
                <th style="text-align: right;">المصاريف (DH)</th>
                <th style="text-align: right;">الرصيد الصافي (DH)</th>
                <th>آخر حركة</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tenants as $index => $t)
                <tr>
                    <td style="text-align: center; color: #64748b;">{{ $index + 1 }}</td>
                    <td><strong>{{ $t->name }}</strong></td>
                    <td>{{ $t->city ?? '—' }}</td>
                    <td>
                        @if($t->status === 'ACTIVE')
                            نشطة
                        @elseif($t->status === 'PENDING_APPROVAL')
                            قيد الموافقة
                        @else
                            موقوفة
                        @endif
                    </td>
                    <td style="text-align: center;">{{ $t->apartments_count }}</td>
                    <td style="text-align: center;">{{ $t->users_count }}</td>
                    <td style="text-align: right; color: #059669; font-weight: bold;">
                        {{ number_format($t->total_collected, 2) }}
                    </td>
                    <td style="text-align: right; color: #dc2626;">
                        {{ number_format($t->total_expenses, 2) }}
                    </td>
                    <td style="text-align: right; font-weight: bold; color: {{ $t->net_treasury >= 0 ? '#2563eb' : '#dc2626' }};">
                        {{ number_format($t->net_treasury, 2) }}
                    </td>
                    <td style="font-size: 6.5pt; color: #64748b;">
                        {{ $t->last_payment_date ? \Carbon\Carbon::parse($t->last_payment_date)->format('Y/m/d') : '—' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" style="text-align: center; padding: 15px; color: #64748b;">
                        لا توجد إقامات مسجلة في هذا النطاق الزمني.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Signatures --}}
    <table class="sign-table">
        <tr>
            <td>
                <div style="font-size: 8pt; font-weight: bold; margin-bottom: 30px;">قسم المراقبة والتدقيق المالي</div>
                <div style="font-size: 7.5pt; color: #64748b;">التوقيع والتاريخ</div>
            </td>
            <td>
                <div style="font-size: 8pt; font-weight: bold; margin-bottom: 30px;">الإدارة العامة لمنصة Smart Syndic</div>
                <div style="font-size: 7.5pt; color: #64748b;">الخاتم والاعتماد الرسمي</div>
            </td>
        </tr>
    </table>

</body>
</html>
