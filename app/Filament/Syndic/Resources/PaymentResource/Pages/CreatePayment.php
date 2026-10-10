<?php
namespace App\Filament\Syndic\Resources\PaymentResource\Pages;
use App\Filament\Syndic\Resources\PaymentResource;
use Filament\Resources\Pages\CreateRecord;
class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['recorded_by'] = auth()->id();
        return $data;
    }
}
