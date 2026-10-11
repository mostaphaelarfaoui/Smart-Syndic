<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ $receipt_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Cairo', 'Segoe UI', sans-serif;
            font-size: 11px;
            color: #1a1a2e;
            background: #fff;
            padding: 12px;
        }
        .receipt-container {
            border: 2px solid #16213e;
            border-radius: 8px;
            padding: 16px;
            max-width: 100%;
        }
        /* ─── Header ───────────────────────────────── */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #0f3460;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }
        .header-left { text-align: right; }
        .header-right { text-align: left; }
        .org-name { font-size: 16px; font-weight: 700; color: #0f3460; }
        .receipt-ref { font-size: 13px; font-weight: 600; color: #e94560; }
        .receipt-date { font-size: 10px; color: #555; }

        /* ─── Main Grid ────────────────────────────── */
        .info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .info-grid td {
            padding: 5px 8px;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        .info-grid .label {
            background: #f0f4ff;
            font-weight: 600;
            width: 35%;
            color: #0f3460;
        }

        /* ─── Amount Section ───────────────────────── */
        .amount-section {
            background: linear-gradient(135deg, #0f3460, #16213e);
            color: #fff;
            padding: 10px 14px;
            border-radius: 6px;
            margin-bottom: 12px;
            text-align: center;
        }
        .amount-numeric { font-size: 22px; font-weight: 700; }
        .amount-words { font-size: 10px; margin-top: 4px; font-style: italic; }

        /* ─── Footer ───────────────────────────────── */
        .footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 12px;
            padding-top: 8px;
            border-top: 1px dashed #ccc;
        }
        .qr-code { text-align: center; }
        .signature-block {
            text-align: center;
            min-width: 150px;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 30px;
            padding-top: 4px;
            font-size: 9px;
            color: #666;
        }
        .partial-notice {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 6px 10px;
            border-radius: 4px;
            margin-bottom: 10px;
            font-weight: 600;
        }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; background: #fff; }
            .receipt-container { border: 1.5px solid #16213e; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="max-width: 600px; margin: 0 auto 12px auto; display: flex; justify-content: space-between; align-items: center;">
        <button onclick="window.print()" style="padding: 8px 18px; background: #0f3460; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: bold; font-family: inherit;">
            🖨️ طباعة الوصل / Imprimer le reçu
        </button>
        <span style="font-size: 12px; color: #666;">وصل دفع رسمي معتمد</span>
    </div>
    <div class="receipt-container">
        {{-- ─── Header ──────────────────────────────────────── --}}
        <div class="header">
            <div class="header-left">
                <div class="org-name">{{ $tenant->name }}</div>
                <div class="receipt-date">{{ now()->format('d/m/Y H:i') }}</div>
            </div>
            <div class="header-right">
                <div class="receipt-ref">{{ $receipt_number }}</div>
                <div style="font-size: 10px; color: #666;">Reçu de Paiement / إيصال دفع</div>
            </div>
        </div>

        {{-- ─── Info Grid ───────────────────────────────────── --}}
        <table class="info-grid">
            <tr>
                <td class="label">Résident / المقيم</td>
                <td>{{ $resident?->full_name ?? '—' }}</td>
                <td class="label">Statut / الصفة</td>
                <td>{{ $resident?->apartments?->first()?->pivot?->occupancy_type === 'OWNER' ? 'Propriétaire / مالك' : 'Locataire / مستأجر' }}</td>
            </tr>
            <tr>
                <td class="label">Immeuble / المبنى</td>
                <td>{{ $building->name }}</td>
                <td class="label">Appartement / الشقة</td>
                <td>Étage {{ $apartment->floor }} — {{ $apartment->unit_number }}</td>
            </tr>
            <tr>
                <td class="label">Mois couverts / الأشهر</td>
                <td colspan="3">{{ implode(', ', $payment->covered_months ?? []) }}</td>
            </tr>
            <tr>
                <td class="label">Mode de paiement / طريقة الدفع</td>
                <td>
                    @switch($payment->payment_method)
                        @case('CASH') Espèces / نقداً @break
                        @case('BANK_TRANSFER') Virement bancaire / تحويل بنكي @break
                        @case('CHEQUE') Chèque N° {{ $payment->cheque_reference }} / شيك @break
                    @endswitch
                </td>
                <td class="label">Date / التاريخ</td>
                <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
            </tr>
        </table>

        {{-- ─── Amount ──────────────────────────────────────── --}}
        <div class="amount-section">
            <div class="amount-numeric">{{ number_format($payment->amount_paid, 2) }} DH</div>
            <div class="amount-words">{{ $amount_words['fr'] ?? '' }}</div>
            <div class="amount-words">{{ $amount_words['ar'] ?? '' }}</div>
        </div>

        {{-- ─── Partial Payment Notice ──────────────────────── --}}
        @if($payment->is_partial)
            <div class="partial-notice">
                ⚠️ Paiement partiel — Solde restant / رصيد متبقي: {{ number_format($payment->remaining_balance, 2) }} DH
            </div>
        @endif

        {{-- ─── Footer ──────────────────────────────────────── --}}
        <div class="footer">
            <div class="qr-code">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data={{ urlencode($qr_data) }}" alt="QR" width="80" height="80">
                <div style="font-size: 8px; color: #999; margin-top: 2px;">Code de vérification</div>
            </div>
            <div class="signature-block">
                <div class="signature-line">Cachet et Signature du Syndic<br>ختم وتوقيع المسير</div>
            </div>
        </div>
    </div>
</body>
</html>
