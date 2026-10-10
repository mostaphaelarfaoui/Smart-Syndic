<?php
namespace App\Filament\Syndic\Resources\PollResource\Pages;
use App\Filament\Syndic\Resources\PollResource;
use App\Events\PollCreated;
use Filament\Resources\Pages\CreateRecord;

class CreatePoll extends CreateRecord
{
    protected static string $resource = PollResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        return $data;
    }

    protected function afterCreate(): void
    {
        event(new PollCreated($this->record));
    }
}
