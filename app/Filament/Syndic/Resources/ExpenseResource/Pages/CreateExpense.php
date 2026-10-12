<?php
namespace App\Filament\Syndic\Resources\ExpenseResource\Pages;
use App\Filament\Syndic\Resources\ExpenseResource;
use Filament\Resources\Pages\CreateRecord;
class CreateExpense extends CreateRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = auth()->user()?->tenant_id;
        $data['payment_method'] = $data['payment_method'] ?? 'CASH';
        unset($data['recorded_by']);
        return $data;
    }
}
