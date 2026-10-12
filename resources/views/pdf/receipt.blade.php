<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $receipt_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'xbriyaz', 'dejavusans', 'cairo', sans-serif;
            font-size: 11px;
            color: #1e293b;
            background: #ffffff;
            direction: rtl;
            text-align: right;
            padding: 8px;
        }
        .receipt-card {
            border: 2px solid #0f3460;
            border-radius: 8px;
            padding: 14px;
            background: #ffffff;
        }
        /* ─── Header Table ────────────────────────── */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #0f3460;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .header-title {
            font-size: 16px;
            font-weight: bold;
            color: #0f3460;
        }
        .header-sub {
            font-size: 11px;
            color: #475569;
            margin-top: 2px;
        }
        .receipt-badge {
            text-align: left;
            direction: ltr;
        }
        .receipt-number {
            font-size: 14px;
            font-weight: bold;
            color: #e11d48;
        }
        .receipt-date {
            font-size: 10px;
            color: #64748b;
            margin-top: 2px;
        }

        /* ─── Info Table ─────────────────────────── */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .info-table td {
            padding: 6px 8px;
            border: 1px solid #cbd5e1;
            font-size: 11px;
            vertical-align: middle;
        }
        .label-cell {
            background-color: #f1f5f9;
            font-weight: bold;
            color: #0f3460;
            width: 22%;
        }
        .value-cell {
            color: #0f172a;
            width: 28%;
        }

        /* ─── Amount Box ─────────────────────────── */
        .amount-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            background-color: #0f3460;
            color: #ffffff;
            border-radius: 6px;
            text-align: center;
        }
        .amount-table td {
            padding: 8px 12px;
            text-align: center;
        }
        .amount-label {
            font-size: 11px;
            color: #e2e8f0;
            margin-bottom: 2px;
        }
        .amount-value {
            font-size: 20px;
            font-weight: bold;
            color: #ffffff;
            margin-bottom: 2px;
        }
        .amount-words-ar {
            font-size: 11px;
            color: #f8fafc;
            font-weight: bold;
        }
        .amount-words-fr {
            font-size: 10px;
            color: #cbd5e1;
            font-style: italic;
            direction: ltr;
        }

        /* ─── Partial Notice ─────────────────────── */
        .partial-box {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            color: #92400e;
            padding: 6px 10px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 8px;
            text-align: center;
        }

        /* ─── Footer Table ───────────────────────── */
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            border-top: 1px dashed #cbd5e1;
            padding-top: 6px;
            margin-top: 6px;
        }
        .footer-table td {
            vertical-align: middle;
        }
        .footer-notice {
            font-size: 9px;
            color: #64748b;
            text-align: center;
            line-height: 1.4;
        }
        .signature-box {
            text-align: left;
            direction: ltr;
            width: 180px;
        }
        .signature-title {
            font-size: 9px;
            font-weight: bold;
            color: #0f3460;
            text-align: center;
        }
        .signature-line {
            margin-top: 25px;
            border-top: 1px solid #475569;
            text-align: center;
            font-size: 8px;
            color: #64748b;
            padding-top: 2px;
        }
    </style>
</head>
<body>
    <div class="receipt-card">
        {{-- ─── Header ──────────────────────────────────────── --}}
        <table class="header-table">
            <tr>
                <td style="text-align: right; vertical-align: top;">
                    <div class="header-title">{{ $tenant->name }}</div>
                    <div class="header-sub">وصل أداء واجب السانديك / Reçu de Cotisation Syndic</div>
                </td>
                <td style="text-align: center; vertical-align: middle; width: 90px;">
                    {{-- Feature 1 — Stamp image (if configured by syndic) --}}
                    @if(!empty($stamp_base64))
                        <img src="{{ $stamp_base64 }}" style="max-width:80px; max-height:80px; opacity:0.85;" alt="طابع" />
                    @endif
                </td>
                <td style="text-align: left; vertical-align: top;" class="receipt-badge">
                    <div class="receipt-number">{{ $receipt_number }}</div>
                    <div class="receipt-date">{{ now()->format('d/m/Y H:i') }}</div>
                </td>
            </tr>
        </table>

        {{-- ─── Info Grid ───────────────────────────────────── --}}
        <table class="info-table">
            <tr>
                <td class="label-cell">الساكن / Résident</td>
                <td class="value-cell"><strong>{{ $resident?->full_name ?? '—' }}</strong></td>
                <td class="label-cell">الصفة / Statut</td>
                <td class="value-cell">{{ $resident?->apartments?->first()?->pivot?->occupancy_type === 'OWNER' ? 'مالك (Propriétaire)' : 'مكترٍ (Locataire)' }}</td>
            </tr>
            <tr>
                <td class="label-cell">العمارة / Immeuble</td>
                <td class="value-cell">{{ $building->name }}</td>
                <td class="label-cell">الشقة / Appartement</td>
                <td class="value-cell"><strong>شقة {{ $apartment->unit_number }} (الطابق {{ $apartment->floor }})</strong></td>
            </tr>
            <tr>
                <td class="label-cell">الشهور المستخلصة / Mois</td>
                <td class="value-cell" colspan="3"><strong>{{ implode(', ', $payment->covered_months ?? []) }}</strong></td>
            </tr>
            <tr>
                <td class="label-cell">طريقة الأداء / Règlement</td>
                <td class="value-cell">
                    @switch($payment->payment_method)
                        @case('CASH') نقداً (Espèces) @break
                        @case('BANK_TRANSFER') تحويل بنكي (Virement) @break
                        @case('CHEQUE') شيك بنكي (Chèque N° {{ $payment->cheque_reference }}) @break
                        @default {{ $payment->payment_method }}
                    @endswitch
                </td>
                <td class="label-cell">تاريخ الأداء / Date</td>
                <td class="value-cell">{{ $payment->payment_date->format('d/m/Y') }}</td>
            </tr>
        </table>

        {{-- ─── Amount Section ──────────────────────────────── --}}
        <table class="amount-table">
            <tr>
                <td>
                    <div class="amount-label">المبلغ المستخلص المؤدى / Montant Réglé</div>
                    <div class="amount-value">{{ number_format($payment->amount_paid, 2) }} DH</div>
                    @if(!empty($amount_words['ar']))
                        <div class="amount-words-ar">{{ $amount_words['ar'] }}</div>
                    @endif
                    @if(!empty($amount_words['fr']))
                        <div class="amount-words-fr">{{ $amount_words['fr'] }}</div>
                    @endif
                </td>
            </tr>
        </table>

        {{-- ─── Partial Payment Alert (if applicable) ───────── --}}
        @if($payment->is_partial)
            <div class="partial-box">
                ⚠️ أداء جزئي — الرصيد المتبقي بذمة الشقة: {{ number_format($payment->remaining_balance, 2) }} درهم (Solde restant)
            </div>
        @endif

        {{-- ─── Footer ──────────────────────────────────────── --}}
        <table class="footer-table">
            <tr>
                <td style="width: 70px; text-align: right;">
                    <barcode code="{{ $qr_data }}" type="QR" class="barcode" size="0.75" error="M" disableborder="1" />
                </td>
                <td class="footer-notice">
                    <strong>وصل رسمي إلكتروني مؤمن صادرة عن منصة Smart Syndic</strong><br>
                    تم التحصيل والتسجيل في سجلات الإقامة بتاريخ {{ $payment->payment_date->format('d/m/Y') }}
                </td>
                <td class="signature-box">
                    @if(!empty($stamp_base64))
                        {{-- Feature 1 — Show stamp in signature area too --}}
                        <div style="text-align:center;">
                            <img src="{{ $stamp_base64 }}" style="max-width:70px; max-height:70px; opacity:0.80;" alt="طابع" />
                        </div>
                    @else
                        <div class="signature-title">Cachet et Signature du Syndic<br>ختم وتوقيع السانديك</div>
                        <div class="signature-line">إدارة الإقامة</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
