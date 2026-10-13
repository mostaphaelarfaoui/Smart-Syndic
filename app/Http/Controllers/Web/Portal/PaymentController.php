<?php

namespace App\Http\Controllers\Web\Portal;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\Payment;
use App\Services\FileUploadService;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly FileUploadService $uploadService,
    ) {}

    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $apartments = Apartment::whereHas('building', fn ($q) => $q->where('tenant_id', $tenantId))
            ->orderBy('unit_number')
            ->get();

        $query = Payment::where('tenant_id', $tenantId)
            ->with(['apartment.building', 'apartment.residents', 'recorder']);

        // Search & Filters
        if ($request->filled('apartment_id')) {
            $query->where('apartment_id', $request->apartment_id);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('month')) {
            $query->whereJsonContains('covered_months', $request->month);
        }

        $payments = $query->orderByDesc('payment_date')->paginate(20)->withQueryString();

        // Metrics
        $totalCollected = Payment::where('tenant_id', $tenantId)->where('status', '!=', 'DRAFT')->sum('amount_paid');
        $thisMonthCollected = Payment::where('tenant_id', $tenantId)
            ->where('status', '!=', 'DRAFT')
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('amount_paid');

        return view('portal.payments.index', compact('payments', 'apartments', 'totalCollected', 'thisMonthCollected'));
    }

    public function store(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'apartment_id'        => ['required', 'exists:apartments,id'],
            'amount_paid'         => ['required', 'numeric', 'min:1'],
            'payment_method'      => ['required', 'in:CASH,BANK_TRANSFER,CHEQUE'],
            'payment_date'        => ['required', 'date'],
            'covered_months'      => ['nullable', 'string'],
            'notes'               => ['nullable', 'string', 'max:500'],
            'bank_receipt_proof'  => ['nullable', 'file', 'image', 'max:5120'],
        ]);

        // Format covered months array
        $coveredMonths = [];
        if ($request->filled('covered_months')) {
            $coveredMonths = array_map('trim', explode(',', $request->covered_months));
        } else {
            $coveredMonths = [now()->format('Y-m')];
        }
        $validated['covered_months'] = $coveredMonths;

        if ($request->hasFile('bank_receipt_proof')) {
            $validated['bank_receipt_proof'] = $this->uploadService->upload(
                $request->file('bank_receipt_proof'),
                'payment-proofs'
            );
        }

        $payment = $this->paymentService->recordPayment(
            $validated,
            auth()->id(),
            $tenantId
        );

        // Auto-confirm
        $this->paymentService->confirmPayment($payment, auth()->id());
        $this->paymentService->sendToResident($payment, auth()->id());

        return back()->with('success', "تم تسجيل واستخلاص الدفعة بنجاح برقم وصل: {$payment->receipt_number}");
    }

    public function confirm($id)
    {
        $payment = Payment::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
        $this->paymentService->confirmPayment($payment, auth()->id());
        $this->paymentService->sendToResident($payment, auth()->id());

        return back()->with('success', 'تم تأكيد وإرسال الوصل بنجاح.');
    }
}
