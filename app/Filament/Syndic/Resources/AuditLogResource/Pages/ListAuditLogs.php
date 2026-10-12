<?php

namespace App\Filament\Syndic\Resources\AuditLogResource\Pages;

use App\Filament\Syndic\Resources\AuditLogResource;
use Filament\Resources\Pages\ListRecords;

class ListAuditLogs extends ListRecords
{
    protected static string $resource = AuditLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
