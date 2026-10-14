<?php

namespace App\Http\Controllers\Web\Portal;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Services\AuditService;
use App\Services\FileUploadService;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function __construct(
        private readonly FileUploadService $uploadService,
        private readonly AuditService $auditService,
    ) {}

    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $query = Expense::where('tenant_id', $tenantId);

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('search')) {
            $query->where('title', 'like', "%{$request->search}%");
        }

        if ($request->filled('month')) {
            $parts = explode('-', $request->month);
            if (count($parts) === 2) {
                $query->whereYear('expense_date', $parts[0])
                      ->whereMonth('expense_date', $parts[1]);
            }
        }

        $expenses = $query->orderByDesc('expense_date')->paginate(20)->withQueryString();

        $totalExpenses = Expense::where('tenant_id', $tenantId)->sum('amount');
        $thisMonthExpenses = Expense::where('tenant_id', $tenantId)
            ->whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)
            ->sum('amount');

        return view('portal.expenses.index', compact('expenses', 'totalExpenses', 'thisMonthExpenses'));
    }

    public function store(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'title'          => ['required', 'string', 'max:255'],
            'amount'         => ['required', 'numeric', 'min:0.01'],
            'category'       => ['required', 'in:ELEVATOR,CLEANING,SECURITY,WATER_ELECTRICITY,GARDENING,MAINTENANCE,OTHER'],
            'payment_method' => ['required', 'in:CASH,BANK_TRANSFER,CHEQUE'],
            'expense_date'   => ['required', 'date'],
            'invoice_proof'  => ['nullable', 'file', 'image', 'max:5120'],
        ]);

        $proofPath = '';
        if ($request->hasFile('invoice_proof')) {
            $proofPath = $this->uploadService->upload(
                $request->file('invoice_proof'),
                'expense-proofs'
            );
        }

        $expense = Expense::create([
            'tenant_id'          => $tenantId,
            'title'              => $validated['title'],
            'amount'             => $validated['amount'],
            'category'           => $validated['category'],
            'payment_method'     => $validated['payment_method'],
            'expense_date'       => $validated['expense_date'],
            'invoice_proof_path' => $proofPath,
        ]);

        $this->auditService->log(
            auth()->id(),
            'EXPENSE_RECORDED',
            $tenantId,
            ['expense_id' => $expense->id, 'amount' => $expense->amount, 'title' => $expense->title]
        );

        return back()->with('success', 'تم تسجيل المصروف بنجاح.');
    }

    public function destroy(Request $request, $id)
    {
        if ($error = $this->verifySensitivePassword($request, 'حذف المصروف')) {
            return $error;
        }

        $tenantId = auth()->user()->tenant_id;

        $expense = Expense::where('tenant_id', $tenantId)->findOrFail($id);
        $title   = $expense->title;

        $expense->delete();

        $this->auditService->log(
            auth()->id(),
            'EXPENSE_DELETED',
            $tenantId,
            ['title' => $title]
        );

        return back()->with('success', "تم حذف المصروف \"{$title}\" بنجاح.");
    }
}
