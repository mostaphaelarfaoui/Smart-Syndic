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
                    ->placeholder('مثال: عمارة الأندلس، عمارة 1')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('block')
                    ->label('البلوك / الشطر (اختياري بالمجمع)')
                    ->placeholder('مثال: بلوك أ، الشطر 1')
                    ->maxLength(100),
                Forms\Components\Hidden::make('total_floors')
                    ->default(1),
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
                Tables\Columns\TextColumn::make('block')
                    ->label('البلوك / الشطر')
                    ->placeholder('—')
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
            \App\Filament\Syndic\Resources\BuildingResource\RelationManagers\ApartmentsRelationManager::class,
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
