<?php

namespace App\Filament\Syndic\Resources\ApartmentResource\Pages;

use App\Filament\Syndic\Resources\ApartmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateApartment extends CreateRecord
{
    protected static string $resource = ApartmentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
