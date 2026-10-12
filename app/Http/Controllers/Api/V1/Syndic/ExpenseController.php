<?php

namespace App\Http\Controllers\Api\V1\Syndic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Syndic\RecordExpenseRequest;
use App\Models\Expense;
use App\Services\AuditService;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function __construct(
        private readonly FileUploadService $uploadService,
        private readonly AuditService $auditService,
    ) {}

    /**
     * POST /api/v1/syndic/expenses
     */
    public function store(RecordExpenseRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Upload invoice proof if provided
        if ($request->hasFile('invoice_proof')) {
            $data['invoice_proof_path'] = $this->uploadService->upload(
                $request->file('invoice_proof'),
                'expense-proofs'
            );
        } else {
            $data['invoice_proof_path'] = $data['invoice_proof_path'] ?? '';
        }

        $expense = Expense::create([
            'tenant_id'          => $request->user()->tenant_id,
            'building_id'        => $data['building_id'] ?? null,
            'category'           => $data['category'],
            'title'              => $data['title'],
            'amount'             => $data['amount'],
            'payment_method'     => $data['payment_method'] ?? 'CASH',
            'expense_date'       => $data['expense_date'],
            'invoice_proof_path' => $data['invoice_proof_path'],
        ]);

        $this->auditService->log(
            $request->user()->id,
            'EXPENSE_RECORDED',
            $request->user()->tenant_id,
            ['expense_id' => $expense->id, 'amount' => $data['amount'], 'category' => $data['category']],
        );

        return response()->json([
            'message' => __('expenses.recorded'),
            'expense' => $expense,
        ], 201);
    }

    /**
     * GET /api/v1/syndic/expenses
     */
    public function index(Request $request): JsonResponse
    {
        $query = Expense::where('tenant_id', $request->user()->tenant_id)
            ->with('building');

        // Filter by category
        if ($request->filled('category') && $request->category !== 'ALL') {
            $query->where('category', $request->category);
        }

        // Filter by building
        if ($request->filled('building_id')) {
            $query->where('building_id', $request->building_id);
        }

        // Filter by date range
        if ($request->filled('from') && $request->filled('to')) {
            $query->whereBetween('expense_date', [$request->from, $request->to]);
        }

        $expenses = $query->orderByDesc('expense_date')->paginate(50);

        $expenses->getCollection()->transform(function ($e) {
            $e->amount = (float) $e->amount;
            return $e;
        });

        return response()->json($expenses);
    }
}
