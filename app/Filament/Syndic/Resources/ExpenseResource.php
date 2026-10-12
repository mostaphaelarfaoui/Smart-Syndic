<?php

namespace App\Filament\Syndic\Resources;

use App\Filament\Syndic\Resources\ExpenseResource\Pages;
use App\Models\Expense;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationLabel = 'المصاريف والنفقات';

    protected static ?string $navigationGroup = 'المالية والحسابات';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return 'مصروف';
    }

    public static function getPluralModelLabel(): string
    {
        return 'المصاريف والنفقات';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('بيانات المصروف')
                    ->schema([
                        Forms\Components\Select::make('building_id')
                            ->label('العمارة')
                            ->relationship('building', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('مشترك لجميع العمارات')
                            ->nullable(),
                        Forms\Components\Select::make('category')
                            ->label('نوع المصروف / الصنف')
                            ->options([
                                'ELEVATOR'          => '🛗 المصعد',
                                'CLEANING'          => '🧹 النظافة',
                                'SECURITY'          => '🔒 الحراسة والأمن',
                                'WATER_ELECTRICITY' => '💡 الماء والكهرباء المشتركة',
                                'GARDENING'         => '🌿 البستنة والمساحات الخضراء',
                                'MAINTENANCE'       => '🔧 الصيانة العامة',
                                'OTHER'             => '📋 مصاريف أخرى',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('title')
                            ->label('بيان / موضوع المصروف')
                            ->placeholder('مثال: صيانة المصعد لشهر يناير')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('amount')
                            ->label('المبلغ (درهم)')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->maxValue(999999.99)
                            ->prefix('درهم'),
                        Forms\Components\DatePicker::make('expense_date')
                            ->label('تاريخ الصرف')
                            ->required()
                            ->maxDate(now()),
                        Forms\Components\Select::make('payment_method')
                            ->label('طريقة الدفع ومصدر الخصم')
                            ->options([
                                'CASH'          => '💵 نقداً (يخصم من الصندوق)',
                                'BANK_TRANSFER' => '🏦 تحويل بنكي (يخصم من البنك)',
                                'CHEQUE'        => '🧾 شيك بنكي (يخصم من البنك)',
                            ])
                            ->default('CASH')
                            ->required(),
                    ])->columns(2),
                Forms\Components\Section::make('الفاتورة أو سند الصرف (اختياري)')
                    ->schema([
                        Forms\Components\FileUpload::make('invoice_proof_path')
                            ->label('الفاتورة أو إثبات الصرف')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                            ->maxSize(5120)
                            ->directory('expense-proofs')
                            ->visibility('private')
                            ->nullable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('بيان المصروف')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('category')
                    ->label('الصنف')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'ELEVATOR'          => 'المصعد',
                        'CLEANING'          => 'النظافة',
                        'SECURITY'          => 'الحراسة',
                        'WATER_ELECTRICITY' => 'الماء والكهرباء',
                        'GARDENING'         => 'البستنة',
                        'MAINTENANCE'       => 'الصيانة',
                        'OTHER'             => 'أخرى',
                        default             => $state,
                    })
                    ->colors([
                        'primary'   => 'ELEVATOR',
                        'success'   => 'CLEANING',
                        'danger'    => 'SECURITY',
                        'warning'   => 'WATER_ELECTRICITY',
                        'info'      => 'GARDENING',
                        'gray'      => 'MAINTENANCE',
                        'secondary' => 'OTHER',
                    ]),
                Tables\Columns\TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money('MAD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('building.name')
                    ->label('العمارة')
                    ->placeholder('مشترك'),
                Tables\Columns\TextColumn::make('expense_date')
                    ->label('تاريخ الصرف')
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort('expense_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('تصنيف المصاريف')
                    ->options([
                        'ELEVATOR'          => 'المصعد',
                        'CLEANING'          => 'النظافة',
                        'SECURITY'          => 'الحراسة',
                        'WATER_ELECTRICITY' => 'الماء والكهرباء',
                        'GARDENING'         => 'البستنة',
                        'MAINTENANCE'       => 'الصيانة',
                        'OTHER'             => 'أخرى',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit'   => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}
