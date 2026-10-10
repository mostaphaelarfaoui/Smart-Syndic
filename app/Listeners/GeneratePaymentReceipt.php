<?php

namespace App\Listeners;

use App\Events\PaymentRecorded;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Stub: PDF receipt generation is handled inline by PaymentService.
 * This listener exists as an extension point for async re-generation.
 */
class GeneratePaymentReceipt implements ShouldQueue
{
    public $queue = 'receipts';

    public function handle(PaymentRecorded $event): void
    {
        // PDF is already generated synchronously in PaymentService.
        // This listener can be used for async re-processing or auditing.
    }
}
