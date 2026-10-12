<?php

namespace App\Filament\SuperAdmin\Resources\PaymentReceiptsResource\Pages;

use App\Filament\SuperAdmin\Resources\PaymentReceiptsResource;
use Filament\Resources\Pages\ListRecords;

class ListPaymentReceipts extends ListRecords
{
    protected static string $resource = PaymentReceiptsResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
