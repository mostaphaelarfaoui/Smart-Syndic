<?php

namespace App\Filament\Syndic\Resources\ApartmentResource\Pages;

use App\Filament\Syndic\Resources\ApartmentResource;
use App\Models\Apartment;
use App\Models\Building;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;

class ListApartments extends ListRecords
{
    protected static string $resource = ApartmentResource::class;

    protected function getHeaderActions(): array
    {
        $tenantId = auth()->user()?->tenant_id;

        return [
            Actions\CreateAction::make()
                ->label('إضافة شقة فردية')
                ->icon('heroicon-o-plus-circle'),

            Actions\Action::make('bulk_generate')
                ->label('توليد شقق متعددة دفعة واحدة')
                ->icon('heroicon-o-sparkles')
                ->color('success')
                ->modalHeading('توليد شقق العمارة تلقائياً')
                ->modalDescription('حدد العمارة وعدد الطوابق والشقق ليتم إنشاء الوحدات وحساب أرقامها تلقائياً.')
                ->modalSubmitActionLabel('بدء التوليد التلقائي')
                ->form([
                    Forms\Components\Select::make('building_id')
                        ->label('العمارة / البلوك')
                        ->options(Building::where('tenant_id', $tenantId)->pluck('name', 'id'))
                        ->required()
                        ->searchable(),

                    Forms\Components\TextInput::make('prefix')
                        ->label('بادئة رقم الشقة (اختياري)')
                        ->placeholder('مثال: Apt أو A أو اتركها فارغة'),

                    Forms\Components\TextInput::make('total_units')
                        ->label('عدد الشقق الإجمالي المراد إنشاؤها')
                        ->numeric()
                        ->default(10)
                        ->required()
                        ->minValue(1)
                        ->maxValue(200),

                    Forms\Components\TextInput::make('monthly_fee')
                        ->label('الواجب الشهري الافتراضي (درهم)')
                        ->numeric()
                        ->default(150.00)
                        ->required()
                        ->prefix('درهم'),
                ])
                ->action(function (array $data) use ($tenantId) {
                    $building = Building::where('tenant_id', $tenantId)->findOrFail($data['building_id']);
                    $prefix = trim($data['prefix'] ?? '');
                    $totalUnits = (int) $data['total_units'];
                    $monthlyFee = (float) $data['monthly_fee'];

                    $createdCount = 0;
                    $skippedCount = 0;

                    DB::transaction(function () use (
                        $building,
                        $prefix,
                        $totalUnits,
                        $monthlyFee,
                        &$createdCount,
                        &$skippedCount
                    ) {
                        for ($u = 1; $u <= $totalUnits; $u++) {
                            $unitNum = $prefix
                                ? "{$prefix}-{$u}"
                                : (string) $u;

                            // Check if apartment unit number exists in this building
                            $exists = Apartment::where('building_id', $building->id)
                                ->where('unit_number', $unitNum)
                                ->exists();

                            if (! $exists) {
                                Apartment::create([
                                    'building_id' => $building->id,
                                    'unit_number' => $unitNum,
                                    'floor'       => 0,
                                    'monthly_fee' => $monthlyFee,
                                    'is_billable' => true,
                                ]);
                                $createdCount++;
                            } else {
                                $skippedCount++;
                            }
                        }
                    });

                    Notification::make()
                        ->title("تم إنشاء {$createdCount} شقة بنجاح في {$building->name}")
                        ->body($skippedCount > 0 ? "تم تخطي {$skippedCount} شقة لأنها موجودة مسبقاً." : null)
                        ->success()
                        ->send();
                }),
        ];
    }
}
