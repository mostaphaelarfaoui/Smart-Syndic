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
     * Record a new payment in DRAFT state (Feature 12).
     * Syndic must then confirm and send separately.
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

        // Feature 12: Auto-confirm and deliver to resident unless explicitly marked as draft
        if (! $request->boolean('save_as_draft', false)) {
            $payment = $this->paymentService->confirmPayment($payment, $request->user()->id);
            $payment = $this->paymentService->sendToResident($payment, $request->user()->id);
        }

        return response()->json([
            'message' => __('payments.recorded'),
            'payment' => $this->formatPayment($payment),
        ], 201);
    }

    /**
     * GET /api/v1/syndic/payments
     *
     * Paginated list with workflow status (Feature 12).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Payment::where('tenant_id', $request->user()->tenant_id)
            ->with('apartment.building', 'recorder')
            ->orderByDesc('payment_date');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', strtoupper($request->status));
        }

        $payments = $query->paginate(25);

        return response()->json($payments->through(fn ($p) => $this->formatPayment($p)));
    }

    /**
     * POST /api/v1/syndic/payments/{id}/confirm
     *
     * Feature 12 — Confirm a DRAFT receipt. Locks editing/deleting.
     * Also creates an immutable snapshot in audit_logs (Feature 3).
     */
    public function confirm(Request $request, string $id): JsonResponse
    {
        $payment = Payment::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);

        $confirmed = $this->paymentService->confirmPayment($payment, $request->user()->id);

        return response()->json([
            'message' => 'تم تأكيد الوصل بنجاح. يمكنك الآن إرساله للساكن.',
            'payment' => $this->formatPayment($confirmed),
        ]);
    }

    /**
     * POST /api/v1/syndic/payments/{id}/send
     *
     * Feature 12 — Send a CONFIRMED receipt to the resident's account.
     * After this, the receipt is immutable (no edit/delete).
     */
    public function send(Request $request, string $id): JsonResponse
    {
        $payment = Payment::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);

        $sent = $this->paymentService->sendToResident($payment, $request->user()->id);

        return response()->json([
            'message' => 'تم إرسال الوصل لحساب الساكن بنجاح.',
            'payment' => $this->formatPayment($sent),
        ]);
    }

    /**
     * PUT /api/v1/syndic/payments/{id}
     *
     * Feature 12 — Edit is only allowed on DRAFT payments.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'amount_paid'    => ['sometimes', 'numeric', 'min:0.01'],
            'payment_date'   => ['sometimes', 'date'],
            'covered_months' => ['sometimes', 'array', 'min:1'],
            'payment_method' => ['sometimes', 'string', 'in:CASH,BANK_TRANSFER,CHEQUE'],
        ]);

        $payment = Payment::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);

        if (! $payment->isDraft()) {
            return response()->json([
                'message' => 'لا يمكن تعديل الوصل بعد تأكيده.',
            ], 422);
        }

        $payment->update($request->only([
            'amount_paid', 'payment_date', 'covered_months', 'payment_method', 'cheque_reference',
        ]));

        return response()->json([
            'message' => 'تم تحديث الوصل بنجاح.',
            'payment' => $this->formatPayment($payment->fresh('apartment.building')),
        ]);
    }

    /**
     * DELETE /api/v1/syndic/payments/{id}
     *
     * Feature 12 — Delete only DRAFT payments.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $payment = Payment::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);

        $this->paymentService->deleteDraftPayment($payment, $request->user()->id);

        return response()->json(['message' => 'تم حذف مسودة الوصل بنجاح.']);
    }

    /**
     * Format a payment for API response.
     */
    private function formatPayment(Payment $payment): array
    {
        return [
            'id'                  => $payment->id,
            'receipt_number'      => $payment->receipt_number,
            'amount_paid'         => (float) $payment->amount_paid,
            'payment_method'      => $payment->payment_method,
            'covered_months'      => $payment->covered_months,
            'is_partial'          => $payment->is_partial,
            'remaining'           => (float) $payment->remaining_balance,
            'status'              => $payment->status,
            'confirmed_at'        => $payment->confirmed_at?->toIso8601String(),
            'sent_to_resident_at' => $payment->sent_to_resident_at?->toIso8601String(),
            'receipt_url'         => $payment->receipt_url,
            'bank_proof_url'      => $payment->bank_proof_url,
            'payment_date'        => $payment->payment_date?->format('Y-m-d'),
            'apartment'           => $payment->apartment ? [
                'id'          => $payment->apartment->id,
                'unit_number' => $payment->apartment->unit_number,
                'building'    => $payment->apartment->building?->name,
            ] : null,
            'recorder'            => $payment->recorder?->full_name,
        ];
    }
}
