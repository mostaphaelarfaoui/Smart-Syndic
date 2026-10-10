<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport Financier — {{ $tenant->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            font-size: 10px;
            color: #1a1a2e;
            padding: 20px;
        }
        h1 { font-size: 18px; color: #0f3460; margin-bottom: 4px; }
        h2 { font-size: 14px; color: #16213e; margin: 16px 0 8px; border-bottom: 2px solid #0f3460; padding-bottom: 4px; }
        h3 { font-size: 12px; color: #333; margin: 10px 0 6px; }

        .header-section {
            border: 2px solid #0f3460;
            border-radius: 6px;
            padding: 14px;
            margin-bottom: 16px;
        }
        .header-meta { display: flex; justify-content: space-between; margin-top: 8px; font-size: 10px; color: #555; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 5px 8px;
            text-align: left;
        }
        th {
            background: #0f3460;
            color: #fff;
            font-weight: 600;
            font-size: 9px;
            text-transform: uppercase;
        }
        tr:nth-child(even) { background: #f8f9ff; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .summary-box {
            background: linear-gradient(135deg, #0f3460, #16213e);
            color: #fff;
            padding: 14px;
            border-radius: 6px;
            margin: 16px 0;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            font-size: 12px;
        }
        .summary-total {
            font-size: 16px;
            font-weight: 700;
            border-top: 1px solid rgba(255,255,255,0.3);
            padding-top: 8px;
            margin-top: 6px;
        }

        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            padding-top: 10px;
        }
        .sig-block {
            text-align: center;
            width: 30%;
        }
        .sig-line {
            border-top: 1px solid #333;
            margin-top: 50px;
            padding-top: 4px;
            font-size: 9px;
            color: #666;
        }

        .page-break { page-break-before: always; }
        .footer-note { font-size: 8px; color: #999; text-align: center; margin-top: 20px; }
    </style>
</head>
<body>
    {{-- ─── Header ──────────────────────────────────────────── --}}
    <div class="header-section">
        <h1>{{ $tenant->name }}</h1>
        <div style="font-size: 13px; color: #e94560; font-weight: 700;">
            التقرير المالي — الجمع العام / Rapport Financier — Assemblée Générale
        </div>
        <div class="header-meta">
            <span>الفترة / Période: {{ \Carbon\Carbon::parse($period_start)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($period_end)->format('d/m/Y') }}</span>
            <span>حرر في / Généré le: {{ \Carbon\Carbon::parse($generated_at)->format('d/m/Y H:i') }}</span>
        </div>
    </div>

    {{-- ─── Table A: Revenue / Recouvrement ─────────────────── --}}
    <h2>الجدول أ — تحصيل الواجبات حسب الشقق / Tableau A — Recouvrement par Lot</h2>
    <table>
        <thead>
            <tr>
                <th>الشقة / Lot</th>
                <th>الساكن / Résident</th>
                <th class="text-right">الواجب السنوي / Cotisation (DH)</th>
                <th class="text-right">المحصل / Total Recouvré (DH)</th>
                <th class="text-center">النسبة / Taux (%)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($revenue_by_apartment as $apt)
            <tr>
                <td>{{ $apt['apartment'] }}</td>
                <td>{{ $apt['resident'] }}</td>
                <td class="text-right">{{ number_format($apt['total_due'], 2) }}</td>
                <td class="text-right">{{ number_format($apt['total_paid'], 2) }}</td>
                <td class="text-center">{{ $apt['recovery_pct'] }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ─── Table B: Expenditures / Dépenses ───────────────── --}}
    <h2>الجدول ب — المصاريف حسب الصنف / Tableau B — Dépenses par Catégorie</h2>
    <table>
        <thead>
            <tr>
                <th>الصنف / Catégorie</th>
                <th class="text-center">عدد العمليات / Opérations</th>
                <th class="text-right">المجموع / Total (DH)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($expenses_by_category as $cat)
            <tr>
                <td>{{ $cat['category'] }}</td>
                <td class="text-center">{{ $cat['item_count'] }}</td>
                <td class="text-right">{{ number_format($cat['total'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ─── Table C: Treasury Reconciliation ────────────────── --}}
    <h2>الجدول ج — وضعية الخزينة / Tableau C — Situation de Trésorerie</h2>
    <div class="summary-box">
        <div class="summary-row">
            <span>مجموع المداخيل والتحصيلات / Total des Recettes</span>
            <span>{{ number_format($total_revenue, 2) }} DH</span>
        </div>
        <div class="summary-row">
            <span>مجموع المصاريف والنفقات / Total des Dépenses</span>
            <span>- {{ number_format($total_expenses, 2) }} DH</span>
        </div>
        <div class="summary-row summary-total">
            <span>الرصيد الصافي المتاح / Solde Net / Fonds de Réserve</span>
            <span>{{ number_format($net_balance, 2) }} DH</span>
        </div>
    </div>

    {{-- ─── Signatures ──────────────────────────────────────── --}}
    <div class="signatures">
        <div class="sig-block">
            <div class="sig-line">رئيس مجلس السانديك<br>Président du Syndic</div>
        </div>
        <div class="sig-block">
            <div class="sig-line">أمين المال<br>Trésorier</div>
        </div>
        <div class="sig-block">
            <div class="sig-line">مراقب الحسابات<br>Commissaire aux Comptes</div>
        </div>
    </div>

    <div class="footer-note">
        وثيقة منشأة آلياً بواسطة Smart Syndic — مطابقة للقانون 18-00 المتعلق بالملكية المشتركة بالمملكة المغربية<br>
        Document généré automatiquement par Smart Syndic — Conforme à la Loi 18-00 relative à la copropriété
    </div>
</body>
</html>
