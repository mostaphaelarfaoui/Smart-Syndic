<?php

namespace App\Filament\Syndic\Resources\ApartmentResource\Pages;

use App\Filament\Syndic\Resources\ApartmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditApartment extends EditRecord
{
    protected static string $resource = ApartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
