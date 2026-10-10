<?php
namespace App\Filament\Syndic\Resources\PollResource\Pages;
use App\Filament\Syndic\Resources\PollResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListPolls extends ListRecords
{
    protected static string $resource = PollResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('إنشاء تصويت جديد')];
    }
}
