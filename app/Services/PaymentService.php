<?php

namespace App\Services;

use App\Models\Apartment;
use App\Models\Payment;
use App\Models\Tenant;
use App\Events\PaymentRecorded;
use Illuminate\Support\Facades\DB;

/**
 * PaymentService — Core payment recording with transactional integrity.
 *
 * Workflow (Feature 12):
 *   DRAFT → CONFIRMED → SENT_TO_RESIDENT
 *
 * Receipt numbering (Feature 13):
 *   Uses Tenant::nextReceiptNumber() — atomic DB-level sequential counter.
 */
class PaymentService
{
    public function __construct(
        private readonly PdfReceiptService $pdfService,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Record a new payment within a DB transaction.
     * Payment starts as DRAFT — syndic must confirm before sending to resident.
     *
     * @throws \Throwable
     */
    public function recordPayment(array $data, string $recordedBy, string $tenantId): Payment
    {
        return DB::transaction(function () use ($data, $recordedBy, $tenantId) {
            $tenant    = Tenant::findOrFail($tenantId);
            $apartment = Apartment::findOrFail($data['apartment_id']);

            // Feature 13 — Atomic sequential receipt number (replaces mt_rand)
            $receiptNumber = $tenant->nextReceiptNumber();

            // Calculate remaining balance
            $totalDue  = $apartment->monthly_fee * count($data['covered_months']);
            $remaining = max(0, $totalDue - $data['amount_paid']);
            $isPartial = $remaining > 0;

            // Create the payment record in DRAFT state (Feature 12)
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
                'status'             => 'DRAFT', // Feature 12
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
                    'status'         => 'DRAFT',
                ]
            );

            return $payment;
        });
    }

    /**
     * Feature 12 — Confirm a DRAFT payment.
     * Locks the receipt — no more edits or deletes allowed.
     * Stores an immutable snapshot in the audit log (Feature 3).
     *
     * @throws \RuntimeException
     */
    public function confirmPayment(Payment $payment, string $actorId): Payment
    {
        if (! $payment->isDraft()) {
            throw new \RuntimeException('يمكن تأكيد الوصولات في حالة المسودة فقط.');
        }

        return DB::transaction(function () use ($payment, $actorId) {
            $payment->update([
                'status'       => 'CONFIRMED',
                'confirmed_at' => now(),
            ]);

            // Feature 3 — Store immutable payment snapshot in audit for dispute resolution
            $this->auditService->logPaymentSnapshot(
                $actorId,
                $payment->tenant_id,
                $payment,
            );

            return $payment->fresh(['apartment.building', 'recorder']);
        });
    }

    /**
     * Feature 12 — Send a CONFIRMED payment to the resident's account.
     * After this, the syndic cannot edit or delete the receipt.
     *
     * @throws \RuntimeException
     */
    public function sendToResident(Payment $payment, string $actorId): Payment
    {
        if (! $payment->isConfirmed()) {
            throw new \RuntimeException('يجب تأكيد الوصل أولاً قبل إرساله للساكن.');
        }

        return DB::transaction(function () use ($payment, $actorId) {
            $payment->update([
                'status'              => 'SENT_TO_RESIDENT',
                'sent_to_resident_at' => now(),
            ]);

            // Audit the send action
            $this->auditService->logPayment(
                $actorId,
                $payment->tenant_id,
                $payment->id,
                'PAYMENT_SENT_TO_RESIDENT',
                ['receipt_number' => $payment->receipt_number],
            );

            // Dispatch event (e.g. WhatsApp notification)
            event(new PaymentRecorded($payment));

            return $payment->fresh();
        });
    }

    /**
     * Feature 12 — Delete a DRAFT payment. CONFIRMED/SENT payments cannot be deleted.
     *
     * @throws \RuntimeException
     */
    public function deleteDraftPayment(Payment $payment, string $actorId): void
    {
        if (! $payment->isDraft()) {
            throw new \RuntimeException('لا يمكن حذف الوصل بعد تأكيده.');
        }

        $this->auditService->logPayment(
            $actorId,
            $payment->tenant_id,
            $payment->id,
            'PAYMENT_DRAFT_DELETED',
            ['receipt_number' => $payment->receipt_number],
        );

        $payment->delete();
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
        $totalPaid  = (float) $apartment->payments()->where('status', '!=', 'DRAFT')->sum('amount_paid');
        $arrears    = max(0, $totalDue - $totalPaid);

        // Feature 7 — Check if current month and future months are covered in advance
        $currentMonth      = now()->format('Y-m');
        $isCurrentPaid     = in_array($currentMonth, $paidMonths, true);
        $advancePaidMonths = array_values(array_filter($paidMonths, fn ($m) => $m > $currentMonth));
        sort($advancePaidMonths);

        return [
            'monthly_fee'         => $apartment->monthly_fee,
            'total_due'           => $totalDue,
            'total_paid'          => $totalPaid,
            'arrears'             => $arrears,
            'paid_months'         => array_values($paidMonths),
            'unpaid_months'       => array_values($unpaidMonths),
            'advance_paid_months' => $advancePaidMonths, // Feature 7
            'is_settled'          => $arrears <= 0,
            'current_month'       => $currentMonth,
            'is_current_paid'     => $isCurrentPaid, // Feature 7
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
     * Get all months covered by existing confirmed/sent payments (excludes DRAFT).
     */
    private function getPaidMonths(Apartment $apartment): array
    {
        $payments = $apartment->payments()->where('status', '!=', 'DRAFT')->get();
        $months   = [];

        foreach ($payments as $payment) {
            $covered = is_array($payment->covered_months) ? $payment->covered_months : [];
            $months  = array_merge($months, $covered);
        }

        return array_unique($months);
    }
}
