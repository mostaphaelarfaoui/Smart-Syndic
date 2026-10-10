<?php

namespace App\Filament\Syndic\Resources;

use App\Filament\Syndic\Resources\PaymentResource\Pages;
use App\Models\Apartment;
use App\Models\Payment;
use App\Services\PaymentService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'المداخيل والواجبات';

    protected static ?string $navigationGroup = 'المالية والحسابات';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'دفعة / مدخول';
    }

    public static function getPluralModelLabel(): string
    {
        return 'المداخيل والواجبات';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('تفاصيل الدفعة')
                    ->schema([
                        Forms\Components\Select::make('apartment_id')
                            ->label('الشقة')
                            ->relationship('apartment', 'unit_number')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('amount_paid')
                            ->label('المبلغ المؤدى (درهم)')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->maxValue(999999.99)
                            ->prefix('درهم'),
                        Forms\Components\Select::make('payment_method')
                            ->label('طريقة الأداء')
                            ->options([
                                'CASH'          => 'نقداً (Espèces)',
                                'BANK_TRANSFER' => 'تحويل بنكي (Virement)',
                                'CHEQUE'        => 'شيك (Chèque)',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('cheque_reference')
                            ->label('رقم / مرجع الشيك')
                            ->maxLength(100)
                            ->visible(fn (Forms\Get $get) => $get('payment_method') === 'CHEQUE'),
                        Forms\Components\DatePicker::make('payment_date')
                            ->label('تاريخ الأداء')
                            ->required()
                            ->maxDate(now()),
                    ])->columns(2),

                Forms\Components\Section::make('فترة التغطية والاستحقاق')
                    ->schema([
                        Forms\Components\TagsInput::make('covered_months')
                            ->label('الأشهر المغطاة (السنة-الشهر)')
                            ->placeholder('مثال: 2026-01')
                            ->required(),
                        Forms\Components\Toggle::make('is_partial')
                            ->label('أداء جزئي'),
                        Forms\Components\TextInput::make('remaining_balance')
                            ->label('المبلغ المتبقي')
                            ->numeric()
                            ->default(0)
                            ->prefix('درهم'),
                    ])->columns(2),

                Forms\Components\Section::make('إثبات الأداء')
                    ->schema([
                        Forms\Components\FileUpload::make('bank_receipt_proof')
                            ->label('إثبات التحويل أو وصل الأداء')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                            ->maxSize(5120)
                            ->directory('payment-proofs')
                            ->visibility('private'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('receipt_number')
                    ->label('رقم الوصل')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('apartment.unit_number')
                    ->label('الشقة')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount_paid')
                    ->label('المبلغ المؤدى')
                    ->money('MAD')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('payment_method')
                    ->label('طريقة الأداء')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'CASH'          => 'نقداً',
                        'BANK_TRANSFER' => 'تحويل بنكي',
                        'CHEQUE'        => 'شيك',
                        default         => $state,
                    })
                    ->colors([
                        'success' => 'CASH',
                        'info'    => 'BANK_TRANSFER',
                        'warning' => 'CHEQUE',
                    ]),
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('تاريخ الأداء')
                    ->date()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_partial')
                    ->boolean()
                    ->label('أداء جزئي'),
                Tables\Columns\TextColumn::make('remaining_balance')
                    ->label('المتبقي')
                    ->money('MAD')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('recorder.full_name')
                    ->label('سجل بواسطة')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('payment_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('طريقة الأداء')
                    ->options([
                        'CASH'          => 'نقداً',
                        'BANK_TRANSFER' => 'تحويل بنكي',
                        'CHEQUE'        => 'شيك',
                    ]),
                Tables\Filters\Filter::make('arrears')
                    ->label('دفعات بمبالغ متبقية (متأخرات)')
                    ->query(fn ($query) => $query->where('remaining_balance', '>', 0)),
            ])
            ->actions([
                Action::make('download_receipt')
                    ->label('تحميل الوصل PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Payment $record) => $record->receipt_url)
                    ->openUrlInNewTab(),
                Action::make('send_whatsapp')
                    ->label('إرسال عبر واتساب')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->action(function (Payment $record) {
                        $resident = $record->apartment->residents()->first();
                        if ($resident) {
                            $url = "https://wa.me/{$resident->phone}?text=" . urlencode(
                                "السلام عليكم، وصل أداء الواجب الشهري رقم {$record->receipt_number} جاهز للتحميل: {$record->receipt_url}"
                            );
                            redirect($url);
                        }
                    }),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit'   => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
