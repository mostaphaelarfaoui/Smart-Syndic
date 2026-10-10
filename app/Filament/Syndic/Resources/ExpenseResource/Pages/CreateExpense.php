<?php
namespace App\Filament\Syndic\Resources\ExpenseResource\Pages;
use App\Filament\Syndic\Resources\ExpenseResource;
use Filament\Resources\Pages\CreateRecord;
class CreateExpense extends CreateRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['recorded_by'] = auth()->id();
        return $data;
    }
}
