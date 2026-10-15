<?php

namespace App\Http\Controllers\Api\V1\Syndic;

use App\Http\Controllers\Controller;
use App\Services\FinancialReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        private readonly FinancialReportService $reportService,
    ) {}

    /**
     * GET /api/v1/syndic/reports/export?from=YYYY-MM-DD&to=YYYY-MM-DD&scope=both|revenues|expenses
     */
    public function export(Request $request): JsonResponse
    {
        $request->validate([
            'from'        => ['nullable', 'date'],
            'to'          => ['nullable', 'date'],
            'start_month' => ['nullable', 'date_format:Y-m'],
            'end_month'   => ['nullable', 'date_format:Y-m'],
            'scope'       => ['nullable', 'in:both,revenues,expenses'],
        ]);

        $tenant = $request->user()->tenant;
        if (! $tenant) {
            return response()->json(['message' => 'Tenant not found.'], 404);
        }

        if ($request->filled('start_month')) {
            $startMonth = $request->input('start_month');
            $endMonth   = $request->input('end_month', $startMonth);
        } else {
            $startMonth = $request->input('from') ? substr((string) $request->input('from'), 0, 7) : now()->format('Y-m');
            $endMonth   = $request->input('to') ? substr((string) $request->input('to'), 0, 7) : $startMonth;
        }

        $scope = $request->input('scope', 'both');

        $pdf = $this->reportService->generatePdf(
            tenant: $tenant,
            startMonth: $startMonth,
            endMonth: $endMonth,
            scope: $scope,
            actor: $request->user()
        );

        $disk = config('smartsyndic.uploads.storage_disk', 'public');
        $filename = 'reports/' . \Illuminate\Support\Str::uuid() . '.pdf';
        \Illuminate\Support\Facades\Storage::disk($disk)->put($filename, $pdf);

        return response()->json([
            'message'  => 'تم استخراج التقرير المالي بنجاح',
            'download' => [
                'url'      => \Illuminate\Support\Facades\Storage::disk($disk)->url($filename),
                'filename' => "rapport-financier-{$startMonth}-{$endMonth}.pdf",
                'scope'    => $scope,
            ],
        ]);
    }
}
