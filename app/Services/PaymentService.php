<?php

namespace App\Services;

use App\Models\Apartment;
use App\Models\Payment;
use App\Events\PaymentRecorded;
use Illuminate\Support\Facades\DB;

/**
 * PaymentService — Core payment recording with transactional integrity (§9.3).
 *
 * All payment creation executes within isolated database transactions.
 * Duplicate concurrent submissions are blocked by DB-level constraints.
 */
class PaymentService
{
    public function __construct(
        private readonly PdfReceiptService $pdfService,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Record a new payment within a database transaction.
     *
     * @throws \Throwable
     */
    public function recordPayment(array $data, string $recordedBy, string $tenantId): Payment
    {
        return DB::transaction(function () use ($data, $recordedBy, $tenantId) {
            // Generate unique receipt number
            $receiptNumber = Payment::generateReceiptNumber();

            // Ensure receipt number uniqueness (retry if collision)
            while (Payment::where('receipt_number', $receiptNumber)->exists()) {
                $receiptNumber = Payment::generateReceiptNumber();
            }

            // Calculate remaining balance
            $apartment   = Apartment::findOrFail($data['apartment_id']);
            $totalDue    = $apartment->monthly_fee * count($data['covered_months']);
            $remaining   = max(0, $totalDue - $data['amount_paid']);
            $isPartial   = $remaining > 0;

            // Create the payment record
            $payment = Payment::create([
                'tenant_id'          => $tenantId,
                'apartment_id'       => $data['apartment_id'],
                'recorded_by'        => $recordedBy,
                'amount_paid'        => $data['amount_paid'],
                'payment_method'     => $data['payment_method'],
                'cheque_reference'   => $data['cheque_reference'] ?? null,
                'payment_date'       => $data['payment_date'],
                'covered_months'     => $data['covered_months'],
                'is_partial'         => $isPartial,
                'remaining_balance'  => $remaining,
                'receipt_number'     => $receiptNumber,
                'receipt_pdf_path'   => '', // Placeholder, updated after PDF generation
                'bank_receipt_proof' => $data['bank_receipt_proof'] ?? null,
            ]);

            // Generate PDF receipt
            $pdfPath = $this->pdfService->generateReceipt($payment);
            $payment->update(['receipt_pdf_path' => $pdfPath]);

            // Audit trail
            $this->auditService->logPayment(
                $recordedBy,
                $tenantId,
                $payment->id,
                'PAYMENT_RECORDED',
                [
                    'amount'         => $data['amount_paid'],
                    'apartment_id'   => $data['apartment_id'],
                    'covered_months' => $data['covered_months'],
                    'receipt_number' => $receiptNumber,
                ]
            );

            // Dispatch event for notification listeners
            event(new PaymentRecorded($payment));

            return $payment;
        });
    }

    /**
     * Get payment summary for an apartment.
     */
    public function getApartmentBalance(Apartment $apartment): array
    {
        $totalDueMonths = $this->getExpectedMonths($apartment);
        $paidMonths     = $this->getPaidMonths($apartment);
        $unpaidMonths   = array_diff($totalDueMonths, $paidMonths);

        $totalDue   = count($totalDueMonths) * $apartment->monthly_fee;
        $totalPaid  = (float) $apartment->payments()->sum('amount_paid');
        $arrears    = max(0, $totalDue - $totalPaid);

        return [
            'monthly_fee'   => $apartment->monthly_fee,
            'total_due'     => $totalDue,
            'total_paid'    => $totalPaid,
            'arrears'       => $arrears,
            'paid_months'   => $paidMonths,
            'unpaid_months' => array_values($unpaidMonths),
            'is_settled'    => $arrears <= 0,
        ];
    }

    /**
     * Get all months that should have been paid (from apartment creation to now).
     */
    private function getExpectedMonths(Apartment $apartment): array
    {
        $start  = $apartment->created_at->startOfMonth();
        $end    = now()->startOfMonth();
        $months = [];

        while ($start->lte($end)) {
            $months[] = $start->format('Y-m');
            $start->addMonth();
        }

        return $months;
    }

    /**
     * Get all months covered by existing payments.
     */
    private function getPaidMonths(Apartment $apartment): array
    {
        $payments = $apartment->payments()->get();
        $months   = [];

        foreach ($payments as $payment) {
            $covered = is_array($payment->covered_months) ? $payment->covered_months : [];
            $months  = array_merge($months, $covered);
        }

        return array_unique($months);
    }
}
