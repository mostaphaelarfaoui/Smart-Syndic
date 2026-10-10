<?php

namespace App\Services;

use App\Models\Apartment;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * FinancialReportService — Assembly-ready financial reports (§8.2).
 *
 * Generates Moroccan Law 18-00 compliant annual accounting reports with:
 * - Table A: Revenue / Recovery per apartment
 * - Table B: Classified expenditures
 * - Table C: Treasury reconciliation
 */
class FinancialReportService
{
    /**
     * Generate a comprehensive financial report for a given period.
     */
    public function generateReport(
        Tenant $tenant,
        string $startDate,
        string $endDate,
        ?string $buildingId = null,
        string $format = 'pdf',
    ): array {
        $data = $this->compileReportData($tenant, $startDate, $endDate, $buildingId);

        if ($format === 'xlsx') {
            return $this->generateExcel($data);
        }

        return $this->generatePdf($data);
    }

    /**
     * Compile all financial data for the report period.
     */
    public function compileReportData(
        Tenant $tenant,
        string $startDate,
        string $endDate,
        ?string $buildingId = null,
    ): array {
        // ─── Revenue / Collections (Table A) ──────────────────────────
        $paymentsQuery = Payment::where('tenant_id', $tenant->id)
            ->whereBetween('payment_date', [$startDate, $endDate]);

        if ($buildingId) {
            $paymentsQuery->whereHas('apartment.building', fn ($q) => $q->where('id', $buildingId));
        }

        $payments     = $paymentsQuery->with('apartment.building', 'apartment.residents')->get();
        $totalRevenue = $payments->sum('amount_paid');

        // Group payments by apartment for itemized view
        $revenueByApartment = $payments->groupBy('apartment_id')->map(function ($aptPayments) {
            $apartment = $aptPayments->first()->apartment;
            return [
                'apartment'    => $apartment->display_label ?? $apartment->unit_number,
                'resident'     => $apartment->residents->first()?->full_name ?? '—',
                'total_due'    => $apartment->monthly_fee * 12,
                'total_paid'   => $aptPayments->sum('amount_paid'),
                'recovery_pct' => $apartment->monthly_fee > 0
                    ? round(($aptPayments->sum('amount_paid') / ($apartment->monthly_fee * 12)) * 100, 1)
                    : 0,
            ];
        })->values();

        // ─── Expenditures (Table B) ───────────────────────────────────
        $expensesQuery = Expense::where('tenant_id', $tenant->id)
            ->whereBetween('expense_date', [$startDate, $endDate]);

        if ($buildingId) {
            $expensesQuery->where('building_id', $buildingId);
        }

        $expenses       = $expensesQuery->get();
        $totalExpenses  = $expenses->sum('amount');

        // Group expenses by category
        $expensesByCategory = $expenses->groupBy('category')->map(function ($catExpenses, $category) {
            return [
                'category'    => $category,
                'total'       => $catExpenses->sum('amount'),
                'item_count'  => $catExpenses->count(),
                'items'       => $catExpenses->map(fn ($e) => [
                    'title'  => $e->title,
                    'amount' => $e->amount,
                    'date'   => $e->expense_date->format('Y-m-d'),
                ])->values(),
            ];
        })->values();

        // ─── Treasury Reconciliation (Table C) ────────────────────────
        $netBalance = $totalRevenue - $totalExpenses;

        return [
            'tenant'              => $tenant,
            'period_start'        => $startDate,
            'period_end'          => $endDate,
            'total_revenue'       => $totalRevenue,
            'total_expenses'      => $totalExpenses,
            'net_balance'         => $netBalance,
            'revenue_by_apartment' => $revenueByApartment,
            'expenses_by_category' => $expensesByCategory,
            'generated_at'        => now()->toIso8601String(),
        ];
    }

    /**
     * Generate an A4 Portrait PDF report.
     */
    private function generatePdf(array $data): array
    {
        $html = view('pdf.assembly-report', $data)->render();

        $pdf = app('snappy.pdf.wrapper');
        $pdf->loadHTML($html);
        $pdf->setOptions([
            'page-size'     => 'A4',
            'orientation'   => 'Portrait',
            'margin-top'    => 15,
            'margin-bottom' => 15,
            'margin-left'   => 12,
            'margin-right'  => 12,
            'encoding'      => 'UTF-8',
            'footer-center' => '[page] / [topage]',
            'footer-font-size' => 8,
        ]);

        $filename = 'reports/' . Str::uuid() . '.pdf';
        $disk     = config('smartsyndic.uploads.storage_disk', 's3');

        Storage::disk($disk)->put($filename, $pdf->output(), 'private');

        return [
            'path'   => $filename,
            'format' => 'pdf',
            'url'    => Storage::disk($disk)->temporaryUrl($filename, now()->addMinutes(30)),
        ];
    }

    /**
     * Generate a multi-sheet Excel export.
     */
    private function generateExcel(array $data): array
    {
        // Uses maatwebsite/excel — export class would be AssemblyReportExport
        $filename = 'reports/' . Str::uuid() . '.xlsx';
        $disk     = config('smartsyndic.uploads.storage_disk', 's3');

        // Store via export class (to be implemented with maatwebsite/excel)
        // Excel::store(new AssemblyReportExport($data), $filename, $disk);

        return [
            'path'   => $filename,
            'format' => 'xlsx',
            'url'    => Storage::disk($disk)->temporaryUrl($filename, now()->addMinutes(30)),
        ];
    }
}
