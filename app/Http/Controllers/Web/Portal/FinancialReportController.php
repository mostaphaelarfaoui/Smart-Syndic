<?php

namespace App\Http\Controllers\Web\Portal;

use App\Http\Controllers\Controller;
use App\Services\FinancialReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FinancialReportController extends Controller
{
    public function __construct(
        protected FinancialReportService $reportService
    ) {}

    /**
     * Generate and stream/download the financial PDF report.
     */
    public function generate(Request $request)
    {
        $tenant = auth()->user()->tenant;
        if (! $tenant) {
            abort(403, 'غير مصرح: لا توجد إقامة مرتبطة بهذا الحساب.');
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

        // Order months if inverted
        if ($startMonth > $endMonth) {
            [$startMonth, $endMonth] = [$endMonth, $startMonth];
        }

        $pdfContent = $this->reportService->generatePdf(
            tenant: $tenant,
            startMonth: $startMonth,
            endMonth: $endMonth,
            scope: $scope,
            actor: auth()->user()
        );

        $tenantSlug = Str::slug($tenant->name, '-');
        $periodSlug = ($startMonth === $endMonth) ? $startMonth : "{$startMonth}_to_{$endMonth}";
        $filename   = "rapport-financier-{$tenantSlug}-{$scope}-{$periodSlug}.pdf";

        $disposition = ($mode === 'preview') ? 'inline' : 'attachment';

        return response($pdfContent, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "{$disposition}; filename=\"{$filename}\"",
            'Cache-Control'       => 'private, max-age=0, must-revalidate',
            'Pragma'              => 'public',
        ]);
    }
}
