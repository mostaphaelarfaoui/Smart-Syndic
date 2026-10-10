<?php
namespace App\Filament\Syndic\Resources\BuildingResource\Pages;
use App\Filament\Syndic\Resources\BuildingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListBuildings extends ListRecords
{
    protected static string $resource = BuildingResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('إضافة عمارة')];
    }
}
