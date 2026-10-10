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
     * GET /api/v1/syndic/reports/export?format=pdf|xlsx&from=YYYY-MM-DD&to=YYYY-MM-DD
     */
    public function export(Request $request): JsonResponse
    {
        $request->validate([
            'from'        => ['required', 'date'],
            'to'          => ['required', 'date', 'after_or_equal:from'],
            'format'      => ['sometimes', 'string', 'in:pdf,xlsx'],
            'building_id' => ['nullable', 'uuid', 'exists:buildings,id'],
        ]);

        $result = $this->reportService->generateReport(
            $request->user()->tenant,
            $request->from,
            $request->to,
            $request->building_id,
            $request->format ?? 'pdf',
        );

        return response()->json([
            'message'  => __('reports.generated'),
            'download' => $result,
        ]);
    }
}
