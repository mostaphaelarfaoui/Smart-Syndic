<?php

namespace App\Services;

use App\Models\Apartment;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FinancialReportService
{
    /**
     * Arabic Month Names Mapping
     */
    public const ARABIC_MONTHS = [
        1  => 'يناير',
        2  => 'فبراير',
        3  => 'مارس',
        4  => 'أبريل',
        5  => 'مايو',
        6  => 'يونيو',
        7  => 'يوليوز',
        8  => 'غشت',
        9  => 'شتنبر',
        10 => 'أكتوبر',
        11 => 'نونبر',
        12 => 'دجنبر',
    ];

    /**
     * Expense Categories Configuration
     */
    public const EXPENSE_CATEGORIES = [
        'CLEANING'          => ['label' => 'النظافة والصيانة الصحية'],
        'ELEVATOR'          => ['label' => 'صيانة المصعد والأجهزة'],
        'SECURITY'          => ['label' => 'الحراسة والأمن'],
        'WATER_ELECTRICITY' => ['label' => 'استهلاك الماء والكهرباء'],
        'GARDENING'         => ['label' => 'المساحات الخضراء والبستنة'],
        'MAINTENANCE'       => ['label' => 'الصيانة العامة والترميم'],
        'OTHER'             => ['label' => 'مصاريف وإصلاحات أخرى'],
    ];

    /**
     * Payment Methods Configuration
     */
    public const PAYMENT_METHODS = [
        'CASH'          => 'نقداً (Espèces)',
        'BANK_TRANSFER' => 'تحويل بنكي (Virement)',
        'CHEQUE'        => 'شيك بنكي (Chèque)',
    ];

    /**
     * Build report data payload.
     */
    public function buildReportData(
        Tenant $tenant,
        string $startMonth,
        string $endMonth,
        string $scope = 'both',
        ?User $actor = null
    ): array {
        // Normalize scope: 'both', 'revenues', 'expenses'
        $scope = in_array(strtolower($scope), ['revenues', 'expenses', 'both']) ? strtolower($scope) : 'both';

        // Normalize dates
        if ($startMonth > $endMonth) {
            [$startMonth, $endMonth] = [$endMonth, $startMonth];
        }

        $startDate = Carbon::createFromFormat('Y-m', $startMonth)->startOfMonth();
        $endDate   = Carbon::createFromFormat('Y-m', $endMonth)->endOfMonth();

        // Calculate months span
        $startCarbon = Carbon::createFromFormat('Y-m', $startMonth);
        $endCarbon   = Carbon::createFromFormat('Y-m', $endMonth);
        $monthsCount = $startCarbon->diffInMonths($endCarbon) + 1;

        $startMonthName = self::ARABIC_MONTHS[(int) $startDate->format('n')];
        $endMonthName   = self::ARABIC_MONTHS[(int) $endDate->format('n')];

        if ($startMonth === $endMonth) {
            $periodTitle = "شهر {$startMonthName} {$startDate->format('Y')}";
            $periodSubtitle = "عن شهر واحد ({$startDate->format('m/Y')})";
        } else {
            $periodTitle = "من {$startMonthName} {$startDate->format('Y')} إلى {$endMonthName} {$endDate->format('Y')}";
            $periodSubtitle = "الفترة الممتدة لـ {$monthsCount} أشهر متتالية";
        }

        // 1. Fetch Revenues (Payments)
        $payments = collect();
        $totalRevenues = 0.0;
        $revenuesByMethod = [
            'CASH'          => 0.0,
            'BANK_TRANSFER' => 0.0,
            'CHEQUE'        => 0.0,
        ];

        if ($scope === 'both' || $scope === 'revenues') {
            $payments = Payment::where('tenant_id', $tenant->id)
                ->where('status', '!=', 'DRAFT')
                ->whereBetween('payment_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->with(['apartment.building', 'apartment.residents', 'recorder'])
                ->orderBy('payment_date', 'asc')
                ->get();

            $totalRevenues = (float) $payments->sum('amount_paid');

            foreach ($payments as $p) {
                $method = strtoupper($p->payment_method ?? 'CASH');
                if (isset($revenuesByMethod[$method])) {
                    $revenuesByMethod[$method] += (float) $p->amount_paid;
                } else {
                    $revenuesByMethod['CASH'] += (float) $p->amount_paid;
                }
            }
        }

        // 2. Fetch Expenses
        $expenses = collect();
        $totalExpenses = 0.0;
        $expensesByCategory = [];
        $expensesByMethod = [
            'CASH'          => 0.0,
            'BANK_TRANSFER' => 0.0,
            'CHEQUE'        => 0.0,
        ];

        foreach (self::EXPENSE_CATEGORIES as $catKey => $catData) {
            $expensesByCategory[$catKey] = [
                'label'      => $catData['label'],
                'amount'     => 0.0,
                'count'      => 0,
                'percentage' => 0.0,
            ];
        }

        if ($scope === 'both' || $scope === 'expenses') {
            $expenses = Expense::where('tenant_id', $tenant->id)
                ->whereBetween('expense_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->with(['building'])
                ->orderBy('expense_date', 'asc')
                ->get();

            $totalExpenses = (float) $expenses->sum('amount');

            foreach ($expenses as $e) {
                $cat = strtoupper($e->category ?? 'OTHER');
                if (! isset($expensesByCategory[$cat])) {
                    $cat = 'OTHER';
                }
                $expensesByCategory[$cat]['amount'] += (float) $e->amount;
                $expensesByCategory[$cat]['count']++;

                $eMethod = strtoupper($e->payment_method ?? 'CASH');
                if (isset($expensesByMethod[$eMethod])) {
                    $expensesByMethod[$eMethod] += (float) $e->amount;
                } else {
                    $expensesByMethod['CASH'] += (float) $e->amount;
                }
            }

            // Calculate category percentages
            if ($totalExpenses > 0) {
                foreach ($expensesByCategory as $catKey => &$cData) {
                    $cData['percentage'] = round(($cData['amount'] / $totalExpenses) * 100, 1);
                }
                unset($cData);
            }
        }

        // 1.5 Calculate Apartments Payment Status (Paid vs Unpaid for the period)
        $periodMonths = [];
        $curMonth = Carbon::createFromFormat('Y-m', $startMonth);
        $endMonthC = Carbon::createFromFormat('Y-m', $endMonth);
        while ($curMonth <= $endMonthC) {
            $periodMonths[] = $curMonth->format('Y-m');
            $curMonth->addMonth();
        }

        $apartmentsStatus = [];
        $paidApartmentsCount = 0;
        $unpaidApartmentsCount = 0;
        $partialApartmentsCount = 0;
        $totalExpectedSubscriptions = 0.0;
        $totalCollectedSubscriptions = 0.0;
        $totalRemainingArrears = 0.0;

        if ($scope === 'both' || $scope === 'revenues') {
            $billableApartments = Apartment::where('tenant_id', $tenant->id)
                ->orWhereHas('building', fn($q) => $q->where('tenant_id', $tenant->id))
                ->where('is_billable', true)
                ->with(['building', 'residents'])
                ->get()
                ->sort(function ($a, $b) {
                    $bA = $a->building?->name ?? '';
                    $bB = $b->building?->name ?? '';
                    if ($bA !== $bB) {
                        return strnatcasecmp($bA, $bB);
                    }
                    return strnatcasecmp($a->unit_number ?? '', $b->unit_number ?? '');
                })
                ->values();

            $allTenantPayments = Payment::where('tenant_id', $tenant->id)
                ->where('status', '!=', 'DRAFT')
                ->get();

            foreach ($billableApartments as $apt) {
                $aptPayments = $allTenantPayments->where('apartment_id', $apt->id);

                // Payments matching the selected period
                $matchedPayments = $aptPayments->filter(function ($p) use ($periodMonths, $startDate, $endDate) {
                    $covered = is_array($p->covered_months) ? $p->covered_months : [];
                    $coversMonth = ! empty(array_intersect($covered, $periodMonths));
                    $inDateRange = $p->payment_date && ($p->payment_date >= $startDate->toDateString() && $p->payment_date <= $endDate->toDateString());
                    return $coversMonth || $inDateRange;
                });

                $expectedFee = (float) $apt->monthly_fee * $monthsCount;
                $paidAmount = (float) $matchedPayments->sum('amount_paid');
                $remainingDue = max(0, $expectedFee - $paidAmount);
                $receipts = $matchedPayments->pluck('receipt_number')->filter()->unique()->values()->all();
                $coveredMonthsList = $matchedPayments->flatMap(fn($p) => is_array($p->covered_months) ? $p->covered_months : [])->unique()->values()->all();

                $primaryResident = $apt->residents->first();

                if ($paidAmount >= $expectedFee && $expectedFee > 0) {
                    $status = 'PAID';
                    $paidApartmentsCount++;
                } elseif ($paidAmount > 0) {
                    $status = 'PARTIAL';
                    $partialApartmentsCount++;
                } else {
                    $status = 'UNPAID';
                    $unpaidApartmentsCount++;
                }

                $totalExpectedSubscriptions += $expectedFee;
                $totalCollectedSubscriptions += $paidAmount;
                $totalRemainingArrears += $remainingDue;

                $apartmentsStatus[] = [
                    'apartment'       => $apt,
                    'unit_number'     => $apt->unit_number,
                    'building_name'   => $apt->building?->name ?? '—',
                    'resident_name'   => $primaryResident?->full_name ?? 'بدون ساكن مسجل',
                    'phone'           => $primaryResident?->phone ?? '—',
                    'monthly_fee'     => (float) $apt->monthly_fee,
                    'expected_fee'    => $expectedFee,
                    'paid_amount'     => $paidAmount,
                    'remaining_due'   => $remainingDue,
                    'status'          => $status, // PAID, PARTIAL, UNPAID
                    'receipts'        => $receipts,
                    'covered_months'  => $coveredMonthsList,
                ];
            }
        }

        $totalBillableApartments = count($apartmentsStatus);
        $collectionRate = $totalBillableApartments > 0
            ? round(($paidApartmentsCount / $totalBillableApartments) * 100, 1)
            : 0;

        // 3. Balance Calculations
        $netBalance = $totalRevenues - $totalExpenses;
        $balanceStatus = $netBalance >= 0 ? 'SURPLUS' : 'DEFICIT';

        // 4. Amount to words
        $totalRevenuesWords = $this->amountToWords($totalRevenues);
        $totalExpensesWords = $this->amountToWords($totalExpenses);
        $netBalanceWords    = $this->amountToWords(abs($netBalance));

        // 5. Reference & Metadata
        $referenceNumber = 'RAP-FIN-' . strtoupper(substr(str_replace('-', '', $tenant->id), 0, 6)) . '-' . now()->format('ymd') . '-' . rand(100, 999);
        $stampBase64 = $this->getStampBase64($tenant);

        // Building summary
        $buildingsCount = $tenant->buildings()->count();
        $apartmentsCount = $tenant->apartments()->count();

        return [
            'tenant'                        => $tenant,
            'actor'                         => $actor,
            'scope'                         => $scope,
            'start_month'                   => $startMonth,
            'end_month'                     => $endMonth,
            'period_months'                 => $periodMonths,
            'months_count'                  => $monthsCount,
            'period_title'                  => $periodTitle,
            'period_subtitle'               => $periodSubtitle,
            'start_date'                    => $startDate,
            'end_date'                      => $endDate,
            'generated_at'                  => now(),
            'reference_number'              => $referenceNumber,
            // Financial Data
            'payments'                      => $payments,
            'total_revenues'                => $totalRevenues,
            'total_revenues_words'          => $totalRevenuesWords,
            'revenues_by_method'            => $revenuesByMethod,
            'payments_count'                => $payments->count(),
            // Apartments Subscriptions Status (Paid vs Unpaid)
            'apartments_status'             => $apartmentsStatus,
            'total_billable_apartments'     => $totalBillableApartments,
            'paid_apartments_count'         => $paidApartmentsCount,
            'partial_apartments_count'      => $partialApartmentsCount,
            'unpaid_apartments_count'       => $unpaidApartmentsCount,
            'total_expected_subscriptions'  => $totalExpectedSubscriptions,
            'total_collected_subscriptions' => $totalCollectedSubscriptions,
            'total_remaining_arrears'       => $totalRemainingArrears,
            'collection_rate'               => $collectionRate,
            // Expenses Data
            'expenses'                      => $expenses,
            'total_expenses'                => $totalExpenses,
            'total_expenses_words'          => $totalExpensesWords,
            'expenses_by_category'          => $expensesByCategory,
            'expenses_by_method'            => $expensesByMethod,
            'expenses_count'                => $expenses->count(),
            // Synthesis
            'net_balance'                   => $netBalance,
            'net_balance_words'             => $netBalanceWords,
            'balance_status'                => $balanceStatus,
            // Stamp & Property
            'stamp_base64'                  => $stampBase64,
            'buildings_count'               => $buildingsCount,
            'apartments_count'              => $apartmentsCount,
        ];
    }

    /**
     * Generate PDF binary stream for the report.
     */
    public function generatePdf(
        Tenant $tenant,
        string $startMonth,
        string $endMonth,
        string $scope = 'both',
        ?User $actor = null
    ): string {
        $data = $this->buildReportData($tenant, $startMonth, $endMonth, $scope, $actor);

        $html = view('pdf.financial-report', $data)->render();

        try {
            $mpdf = new \Mpdf\Mpdf([
                'mode'              => 'utf-8',
                'format'            => 'A4',
                'margin_left'       => 10,
                'margin_right'      => 10,
                'margin_top'        => 12,
                'margin_bottom'     => 14,
                'margin_header'     => 6,
                'margin_footer'     => 6,
                'autoArabic'        => true,
                'autoLangToFont'    => true,
                'useSubstitutions'  => true,
            ]);

            $mpdf->SetDirectionality('rtl');
            $mpdf->SetDisplayMode('fullpage');

            // Footer with page numbering
            $mpdf->SetHTMLFooter('
                <div style="border-top: 1px solid #cbd5e1; padding-top: 5px; font-size: 8px; color: #64748b; direction: rtl; text-align: left; font-family: dejavusans, sans-serif;">
                    <table width="100%" style="border-collapse: collapse;">
                        <tr>
                            <td width="40%" style="text-align: right; font-size: 8px; color: #64748b;">
                                تقرير مالي رسمي — ' . htmlspecialchars($tenant->name, ENT_QUOTES, 'UTF-8') . ' | ' . $data['reference_number'] . '
                            </td>
                            <td width="30%" style="text-align: center; font-size: 8px; color: #64748b;">
                                منصة سمارت سانديك الذكية &copy; ' . date('Y') . '
                            </td>
                            <td width="30%" style="text-align: left; font-size: 8px; color: #64748b;">
                                صفحة {PAGENO} من {nbpg}
                            </td>
                        </tr>
                    </table>
                </div>
            ');

            $mpdf->WriteHTML($html);

            return $mpdf->Output('', 'S');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('mPDF financial report failed: ' . $e->getMessage() . '. Falling back to DomPDF.');

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
            $pdf->setPaper('a4', 'portrait');
            $pdf->setWarnings(false);

            return $pdf->output();
        }
    }

    /**
     * Convert currency amount to Arabic words.
     */
    public function amountToWords(float $amount): string
    {
        $amount = abs($amount);
        $whole  = (int) $amount;
        $cents  = (int) round(($amount - $whole) * 100);

        try {
            $formatter = new \NumberFormatter('ar_MA', \NumberFormatter::SPELLOUT);
            $words = $formatter->format($whole) . ' درهم مغربي';

            if ($cents > 0) {
                $words .= ' و ' . $formatter->format($cents) . ' سنتيم';
            }

            return $words;
        } catch (\Throwable) {
            return number_format($amount, 2) . ' درهم';
        }
    }

    /**
     * Get tenant stamp image as base64.
     */
    protected function getStampBase64(Tenant $tenant): ?string
    {
        if (empty($tenant->stamp_image_path)) {
            return null;
        }

        $disk = config('smartsyndic.uploads.storage_disk', 'public');

        try {
            $contents = Storage::disk($disk)->get($tenant->stamp_image_path);
            if (! $contents) {
                return null;
            }

            $mime = Storage::disk($disk)->mimeType($tenant->stamp_image_path) ?? 'image/png';
            return 'data:' . $mime . ';base64,' . base64_encode($contents);
        } catch (\Throwable) {
            return null;
        }
    }
}
