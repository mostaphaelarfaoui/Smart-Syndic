<?php

namespace App\Filament\Syndic\Resources;

use App\Filament\Syndic\Resources\BuildingResource\Pages;
use App\Models\Building;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BuildingResource extends Resource
{
    protected static ?string $model = Building::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'العمارات والمباني';

    protected static ?string $navigationGroup = 'إدارة الإقامة';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'عمارة';
    }

    public static function getPluralModelLabel(): string
    {
        return 'العمارات';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('اسم العمارة')
                    ->placeholder('مثال: عمارة الأندلس، بلوك أ')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('total_floors')
                    ->label('عدد الطوابق')
                    ->numeric()
                    ->required()
                    ->default(1)
                    ->minValue(1)
                    ->maxValue(100),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم العمارة')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_floors')
                    ->label('عدد الطوابق')
                    ->sortable(),
                Tables\Columns\TextColumn::make('apartments_count')
                    ->counts('apartments')
                    ->label('عدد الشقق')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBuildings::route('/'),
            'create' => Pages\CreateBuilding::route('/create'),
            'edit'   => Pages\EditBuilding::route('/{record}/edit'),
        ];
    }
}
