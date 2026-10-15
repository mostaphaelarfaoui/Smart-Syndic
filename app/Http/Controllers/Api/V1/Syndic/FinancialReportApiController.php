<?php

namespace App\Http\Controllers\Api\V1\Syndic;

use App\Http\Controllers\Controller;
use App\Services\FinancialReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FinancialReportApiController extends Controller
{
    public function __construct(
        protected FinancialReportService $reportService
    ) {}

    /**
     * GET /api/v1/syndic/reports/financial-pdf
     *
     * Download or preview financial report PDF.
     */
    public function generate(Request $request)
    {
        $tenant = $request->user()->tenant;
        if (! $tenant) {
            return response()->json([
                'message' => 'غير مصرح: لا توجد إقامة مرتبطة بهذا الحساب.',
            ], 403);
        }

        $validated = $request->validate([
            'start_month' => ['required', 'date_format:Y-m'],
            'end_month'   => ['nullable', 'date_format:Y-m'],
            'scope'       => ['nullable', 'in:both,revenues,expenses'],
            'mode'        => ['nullable', 'in:download,preview'],
        ]);

        $startMonth = $validated['start_month'];
        $endMonth   = $validated['end_month'] ?? $startMonth;
        $scope      = $validated['scope'] ?? 'both';
        $mode       = $validated['mode'] ?? 'download';

        if ($startMonth > $endMonth) {
            [$startMonth, $endMonth] = [$endMonth, $startMonth];
        }

        $pdfContent = $this->reportService->generatePdf(
            tenant: $tenant,
            startMonth: $startMonth,
            endMonth: $endMonth,
            scope: $scope,
            actor: $request->user()
        );

        $tenantSlug = Str::slug($tenant->name, '-');
        $periodSlug = ($startMonth === $endMonth) ? $startMonth : "{$startMonth}_to_{$endMonth}";
        $filename   = "rapport-financier-{$tenantSlug}-{$scope}-{$periodSlug}.pdf";

        $disposition = ($mode === 'preview') ? 'inline' : 'attachment';

        return response($pdfContent, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "{$disposition}; filename=\"{$filename}\"",
        ]);
    }
}
