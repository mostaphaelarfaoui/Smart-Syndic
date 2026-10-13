<?php
namespace App\Filament\Syndic\Resources\ResidentResource\Pages;
use App\Filament\Syndic\Resources\ResidentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditResident extends EditRecord
{
    protected static string $resource = ResidentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('حذف الحساب وإخلاء الشقة')
                ->requiresConfirmation()
                ->modalHeading('تأكيد حذف حساب الساكن')
                ->modalDescription('سيتم حذف حساب الساكن نهائياً وتفريغ شقته لتصبح شاغرة وتصفير مبالغها لتبدأ من جديد مع الساكن القادم.')
                ->modalSubmitActionLabel('نعم، حذف الحساب وتفريغ الشقة'),
        ];
    }
}
