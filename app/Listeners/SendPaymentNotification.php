<?php

namespace App\Listeners;

use App\Events\PaymentRecorded;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendPaymentNotification implements ShouldQueue
{
    public $queue = 'notifications';

    public function __construct(
        private readonly WhatsAppService $whatsApp,
    ) {}

    public function handle(PaymentRecorded $event): void
    {
        $payment  = $event->payment;
        $resident = $payment->apartment->residents()->first();

        if (! $resident) {
            return;
        }

        $message = __('payments.recorded', [], $resident->locale);

        // Send WhatsApp notification with receipt
        $this->whatsApp->sendDocument(
            $resident->phone,
            $payment->receipt_url ?? '',
            "Receipt-{$payment->receipt_number}.pdf",
            $message,
        );

        // TODO: Send FCM push notification
    }
}
