<?php

namespace App\Filament\Syndic\Resources;

use App\Filament\Syndic\Resources\PollResource\Pages;
use App\Models\Poll;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PollResource extends Resource
{
    protected static ?string $model = Poll::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'استطلاعات الرأي والتصويت';

    protected static ?string $navigationGroup = 'التواصل والتصويت';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return 'استطلاع رأي / تصويت';
    }

    public static function getPluralModelLabel(): string
    {
        return 'استطلاعات الرأي والتصويت';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('بيانات الاستطلاع')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('عنوان الاستطلاع / موضوع التصويت')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('شرح وتفاصيل موضوع التصويت')
                            ->maxLength(2000),
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('تاريخ ووقت انتهاء التصويت')
                            ->required()
                            ->minDate(now()),
                        Forms\Components\Toggle::make('is_active')
                            ->label('تصويت نشط ومفتوح')
                            ->default(true),
                    ]),
                Forms\Components\Section::make('خيارات التصويت')
                    ->schema([
                        Forms\Components\Repeater::make('options')
                            ->label('الخيارات المتاحة')
                            ->addActionLabel('إضافة خيار تصويت')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('option_text')
                                    ->label('نص الخيار')
                                    ->placeholder('مثال: نعم، أوافق على المقترح')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->minItems(2)
                            ->maxItems(10)
                            ->defaultItems(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('موضوع الاستطلاع')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('votes_count')
                    ->counts('votes')
                    ->label('عدد الأصوات')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('نشط'),
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('ينتهي في')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('creator.full_name')
                    ->label('أنشئ بواسطة'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('close')
                    ->label('إغلاق التصويت')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->visible(fn (Poll $r) => $r->is_active)
                    ->requiresConfirmation()
                    ->modalHeading('إغلاق الاستطلاع والتصويت')
                    ->modalDescription('هل أنت متأكد من رغبتك في إغلاق هذا التصويت نهائياً؟ لن يتمكن السكان من المشاركة بعد الآن.')
                    ->action(fn (Poll $r) => $r->update(['is_active' => false])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPolls::route('/'),
            'create' => Pages\CreatePoll::route('/create'),
            'edit'   => Pages\EditPoll::route('/{record}/edit'),
        ];
    }
}
