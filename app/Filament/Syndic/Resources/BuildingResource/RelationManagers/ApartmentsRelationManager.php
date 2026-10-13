<?php

namespace App\Filament\Syndic\Resources\BuildingResource\RelationManagers;

use App\Models\Apartment;
use App\Models\AuditLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;

class ApartmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'apartments';

    protected static ?string $title = 'شقق هذه العمارة';

    protected static ?string $modelLabel = 'شقة';

    protected static ?string $pluralModelLabel = 'الشقق';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('unit_number')
                    ->label('رقم الشقة')
                    ->required()
                    ->maxLength(50),

                Forms\Components\TextInput::make('floor')
                    ->label('الطابق')
                    ->numeric()
                    ->default(1)
                    ->required(),

                Forms\Components\TextInput::make('monthly_fee')
                    ->label('الواجب الشهري (درهم)')
                    ->numeric()
                    ->default(150.00)
                    ->required()
                    ->prefix('درهم'),

                Forms\Components\Toggle::make('is_billable')
                    ->label('خاضعة للواجبات الشهرية')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('unit_number')
            ->columns([
                Tables\Columns\TextColumn::make('unit_number')
                    ->label('رقم الشقة')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('floor')
                    ->label('الطابق')
                    ->formatStateUsing(fn ($state) => $state == 0 ? 'أرضي' : "طابق {$state}")
                    ->sortable(),

                Tables\Columns\TextColumn::make('monthly_fee')
                    ->label('الواجب الشهري')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2) . ' د.م.')
                    ->sortable(),

                Tables\Columns\TextColumn::make('residents.full_name')
                    ->label('الساكن')
                    ->badge()
                    ->placeholder('شاغرة'),

                Tables\Columns\IconColumn::make('is_billable')
                    ->label('خاضعة للأداء')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('warning'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('إضافة شقة للعمارة'),
            ])
            ->actions([
                Action::make('toggle_billable')
                    ->label(fn (Apartment $record) => $record->is_billable ? 'إعفاء' : 'إلغاء الإعفاء')
                    ->icon(fn (Apartment $record) => $record->is_billable ? 'heroicon-o-shield-exclamation' : 'heroicon-o-check-badge')
                    ->color(fn (Apartment $record) => $record->is_billable ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->action(function (Apartment $record) {
                        $newBillable = ! $record->is_billable;
                        $record->update(['is_billable' => $newBillable]);

                        AuditLog::record(
                            actorId: auth()->id(),
                            action: $newBillable ? 'APARTMENT_BILLABLE_ENABLED' : 'APARTMENT_BILLABLE_DISABLED',
                            targetTenantId: auth()->user()->tenant_id,
                            metadata: ['apartment_id' => $record->id, 'unit_number' => $record->unit_number]
                        );

                        Notification::make()
                            ->title($newBillable ? 'تم تفعيل الشقة' : 'تم إعفاء الشقة')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
