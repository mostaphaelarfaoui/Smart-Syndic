<?php

namespace App\Http\Controllers\Api\V1\Syndic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Syndic\RecordPaymentRequest;
use App\Models\Payment;
use App\Services\FileUploadService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly FileUploadService $uploadService,
    ) {}

    /**
     * POST /api/v1/syndic/payments
     *
     * Record a new payment (financial mutation — limited to 15 ops/min).
     */
    public function store(RecordPaymentRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Handle optional bank receipt proof upload
        if ($request->hasFile('bank_receipt_proof')) {
            $data['bank_receipt_proof'] = $this->uploadService->upload(
                $request->file('bank_receipt_proof'),
                'payment-proofs'
            );
        }

        $payment = $this->paymentService->recordPayment(
            $data,
            $request->user()->id,
            $request->user()->tenant_id,
        );

        return response()->json([
            'message'        => __('payments.recorded'),
            'payment'        => [
                'id'             => $payment->id,
                'receipt_number' => $payment->receipt_number,
                'amount_paid'    => $payment->amount_paid,
                'payment_method' => $payment->payment_method,
                'covered_months' => $payment->covered_months,
                'is_partial'     => $payment->is_partial,
                'remaining'      => $payment->remaining_balance,
                'receipt_url'    => $payment->receipt_url,
            ],
        ], 201);
    }

    /**
     * GET /api/v1/syndic/payments (paginated list for syndic view)
     */
    public function index(Request $request): JsonResponse
    {
        $payments = Payment::where('tenant_id', $request->user()->tenant_id)
            ->with('apartment.building', 'recorder')
            ->orderByDesc('payment_date')
            ->paginate(25);

        return response()->json($payments);
    }
}
