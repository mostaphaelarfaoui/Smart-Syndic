<?php
namespace App\Filament\Syndic\Resources\PaymentResource\Pages;
use App\Filament\Syndic\Resources\PaymentResource;
use App\Services\PaymentService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $paymentService = app(PaymentService::class);
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        $months = $data['covered_months'] ?? [];
        if (is_string($months)) {
            $months = array_filter(array_map('trim', explode(',', $months)));
            $data['covered_months'] = $months;
        }

        $payments = $paymentService->recordPayments($data, (string)$user->id, (string)$tenantId);
        foreach ($payments as $payment) {
            $paymentService->confirmPayment($payment, (string)$user->id);
        }

        return $payments[0];
    }
}
