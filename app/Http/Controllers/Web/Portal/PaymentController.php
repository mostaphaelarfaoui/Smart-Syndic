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
        return redirect()->route('portal.apartments.index', array_merge(['tab' => 'receipts'], $request->all()));
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
            $coveredMonths = array_values(array_filter(array_map('trim', explode(',', $request->covered_months))));
        }
        if (empty($coveredMonths)) {
            $coveredMonths = [now()->format('Y-m')];
        }
        $validated['covered_months'] = $coveredMonths;

        if ($request->hasFile('bank_receipt_proof')) {
            $validated['bank_receipt_proof'] = $this->uploadService->upload(
                $request->file('bank_receipt_proof'),
                'payment-proofs'
            );
        }

        $payments = $this->paymentService->recordPayments(
            $validated,
            auth()->id(),
            $tenantId
        );

        // Auto-confirm
        foreach ($payments as $payment) {
            $this->paymentService->confirmPayment($payment, auth()->id());
            $this->paymentService->sendToResident($payment, auth()->id());
        }

        $receiptData = array_map(fn ($p) => [
            'id' => $p->id,
            'receipt_number' => $p->receipt_number,
            'month' => is_array($p->covered_months) ? ($p->covered_months[0] ?? '') : $p->covered_months,
            'amount' => (float) $p->amount_paid,
            'preview_url' => route('receipts.download', $p),
            'download_url' => route('receipts.download', ['payment' => $p->id, 'download' => 1]),
        ], $payments);

        $receiptNumbers = implode(', ', array_map(fn ($p) => $p->receipt_number, $payments));
        $totalPaid = array_sum(array_map(fn ($p) => (float) $p->amount_paid, $payments));

        // In-app notification
        app(\App\Services\NotificationService::class)->send(
            user: auth()->user(),
            title: 'تم استخلاص دفعات مالية جديدة',
            message: count($payments) > 1
                ? "تم استخلاص " . count($payments) . " وصولات بقيمة إجمالية {$totalPaid} درهم (أرقام الوصولات: {$receiptNumbers})."
                : "تم استخلاص دفعة بقيمة {$totalPaid} درهم برقم وصل {$receiptNumbers}.",
            type: 'success',
            url: route('portal.payments.index'),
            icon: '💰',
            extra: [
                'target_screen' => 'payments',
                'target_id'     => $payments[0]->id,
            ]
        );

        return back()
            ->with('success', count($payments) > 1
                ? "تم تسجيل واستخلاص " . count($payments) . " وصولات بنجاح (وصل مستقل لكل شهر): {$receiptNumbers}"
                : "تم تسجيل واستخلاص الدفعة بنجاح برقم وصل: {$receiptNumbers}")
            ->with('generated_payments', $receiptData);
    }


    public function confirm($id)
    {
        $payment = Payment::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
        $this->paymentService->confirmPayment($payment, auth()->id());
        $this->paymentService->sendToResident($payment, auth()->id());

        return back()->with('success', 'تم تأكيد وإرسال الوصل بنجاح.');
    }
}
