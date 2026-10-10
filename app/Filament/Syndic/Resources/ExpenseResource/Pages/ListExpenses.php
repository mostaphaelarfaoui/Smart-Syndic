<?php
namespace App\Filament\Syndic\Resources\ExpenseResource\Pages;
use App\Filament\Syndic\Resources\ExpenseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListExpenses extends ListRecords
{
    protected static string $resource = ExpenseResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('تسجيل مصروف جديد')];
    }
}
