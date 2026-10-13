<?php

namespace App\Filament\Syndic\Resources;

use App\Filament\Syndic\Resources\ApartmentResource\Pages;
use App\Models\Apartment;
use App\Models\AuditLog;
use App\Models\Building;
use App\Models\Payment;
use App\Services\PaymentService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ApartmentResource extends Resource
{
    protected static ?string $model = Apartment::class;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';

    protected static ?string $navigationLabel = 'الشقق والوحدات السكنية';

    protected static ?string $navigationGroup = 'إدارة الإقامة';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return 'شقة';
    }

    public static function getPluralModelLabel(): string
    {
        return 'الشقق والوحدات';
    }

    public static function getEloquentQuery(): Builder
    {
        $tenantId = auth()->user()?->tenant_id;

        return parent::getEloquentQuery()
            ->with(['building', 'residents', 'payments' => function ($query) {
                $query->whereMonth('payment_date', now()->month)
                    ->whereYear('payment_date', now()->year);
            }])
            ->whereHas('building', fn ($q) => $q->where('tenant_id', $tenantId));
    }

    public static function form(Form $form): Form
    {
        $tenantId = auth()->user()?->tenant_id;

        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الشقة')
                    ->description('تحديد العمارة ورقم الشقة والطابق والواجب الشهري')
                    ->schema([
                        Forms\Components\Select::make('building_id')
                            ->label('العمارة / البلوك')
                            ->options(
                                Building::where('tenant_id', $tenantId)->pluck('name', 'id')
                            )
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\TextInput::make('unit_number')
                            ->label('رقم الشقة')
                            ->placeholder('مثال: 12، A-04، B-101')
                            ->required()
                            ->maxLength(50),

                        Forms\Components\TextInput::make('floor')
                            ->label('الطابق')
                            ->numeric()
                            ->default(1)
                            ->required()
                            ->helperText('0 للطابق الأرضي، 1، 2، 3...'),

                        Forms\Components\TextInput::make('monthly_fee')
                            ->label('الواجب الشهري (درهم)')
                            ->numeric()
                            ->prefix('درهم')
                            ->default(150.00)
                            ->required()
                            ->minValue(0),

                        Forms\Components\Toggle::make('is_billable')
                            ->label('خاضعة للواجبات الشهرية (نشطة)')
                            ->default(true)
                            ->helperText('إذا تم إيقاف التفعيل تصبح الشقة معفاة من السانديك ولا تدخل في حساب المتأخرات أو الميزانية المتوقعة.'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        $currentMonth = now()->format('Y-m');

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('unit_number')
                    ->label('رقم الشقة')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-m-home'),

                Tables\Columns\TextColumn::make('building.name')
                    ->label('العمارة / البلوك')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('floor')
                    ->label('الطابق')
                    ->formatStateUsing(fn ($state) => $state == 0 ? 'الطابق الأرضي' : "الطابق {$state}")
                    ->sortable(),

                Tables\Columns\TextColumn::make('monthly_fee')
                    ->label('الواجب الشهري')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2) . ' د.م.')
                    ->sortable(),

                Tables\Columns\TextColumn::make('residents_info')
                    ->label('الساكن والمقيم')
                    ->getStateUsing(function (Apartment $record) {
                        $resident = $record->residents->first();
                        if (! $resident) {
                            return 'شاغرة (بدون ساكن)';
                        }
                        return "{$resident->full_name} ({$resident->phone})";
                    })
                    ->badge()
                    ->color(fn (Apartment $record) => $record->residents->isNotEmpty() ? 'success' : 'gray'),

                Tables\Columns\BadgeColumn::make('month_status')
                    ->label('حالة أداء الشهر الجاري')
                    ->getStateUsing(function (Apartment $record) use ($currentMonth) {
                        if (! $record->is_billable) {
                            return 'معفاة من السانديك';
                        }
                        $hasPaid = Payment::where('apartment_id', $record->id)
                            ->where('status', '!=', 'DRAFT')
                            ->whereJsonContains('covered_months', $currentMonth)
                            ->exists();

                        return $hasPaid ? 'مؤدى بالكامل' : 'في الانتظار (متأخر)';
                    })
                    ->colors([
                        'warning' => 'معفاة من السانديك',
                        'success' => 'مؤدى بالكامل',
                        'danger'  => 'في الانتظار (متأخر)',
                    ])
                    ->icons([
                        'heroicon-m-no-symbol'   => 'معفاة من السانديك',
                        'heroicon-m-check-badge' => 'مؤدى بالكامل',
                        'heroicon-m-clock'       => 'في الانتظار (متأخر)',
                    ]),

                Tables\Columns\IconColumn::make('is_billable')
                    ->label('الخضوع للواجبات')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->tooltip(fn (Apartment $record) => $record->is_billable ? 'شقة خاضعة للواجبات' : 'شقة معفاة من السانديك'),
            ])
            ->defaultSort('unit_number', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('building_id')
                    ->label('العمارة / البلوك')
                    ->options(fn () => Building::where('tenant_id', auth()->user()?->tenant_id)->pluck('name', 'id')),

                Tables\Filters\TernaryFilter::make('is_billable')
                    ->label('حالة الإعفاء')
                    ->placeholder('الكل')
                    ->trueLabel('خاضعة للواجبات فقط')
                    ->falseLabel('معفاة من السانديك فقط'),

                Tables\Filters\Filter::make('has_residents')
                    ->label('شقق مسكونة فقط')
                    ->query(fn (Builder $q) => $q->whereHas('residents')),

                Tables\Filters\Filter::make('vacant')
                    ->label('شقق شاغرة (بدون ساكن)')
                    ->query(fn (Builder $q) => $q->whereDoesntHave('residents')),
            ])
            ->actions([
                // Toggle Exemption Action
                Action::make('toggle_billable')
                    ->label(fn (Apartment $record) => $record->is_billable ? 'إعفاء الشقة' : 'إلغاء الإعفاء')
                    ->icon(fn (Apartment $record) => $record->is_billable ? 'heroicon-o-shield-exclamation' : 'heroicon-o-check-badge')
                    ->color(fn (Apartment $record) => $record->is_billable ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (Apartment $record) => $record->is_billable ? "تأكيد إعفاء الشقة {$record->unit_number} من واجبات السانديك" : "تأكيد إلغاء الإعفاء وتفعيل الشقة {$record->unit_number}")
                    ->modalDescription(fn (Apartment $record) => $record->is_billable
                        ? 'عند إعفاء هذه الشقة، لن يتم احتسابها في الميزانية الشهرية المتوقعة ولن تسجل عليها متأخرات.'
                        : 'سيتم إعادة تفعيل الشقة وإدراجها فوراً في المطالبات الشهرية والميزانية المتوقعة.')
                    ->modalSubmitActionLabel(fn (Apartment $record) => $record->is_billable ? 'نعم، إعفاء الشقة' : 'نعم، تفعيل الشقة')
                    ->action(function (Apartment $record) {
                        $newBillable = ! $record->is_billable;
                        $record->update(['is_billable' => $newBillable]);

                        AuditLog::record(
                            actorId: auth()->id(),
                            action: $newBillable ? 'APARTMENT_BILLABLE_ENABLED' : 'APARTMENT_BILLABLE_DISABLED',
                            targetTenantId: auth()->user()->tenant_id,
                            metadata: [
                                'apartment_id' => $record->id,
                                'unit_number'  => $record->unit_number,
                                'building_id'  => $record->building_id,
                                'is_billable'  => $newBillable,
                            ]
                        );

                        Notification::make()
                            ->title($newBillable ? "تم تفعيل الشقة {$record->unit_number} وإلغاء الإعفاء" : "تم إعفاء الشقة {$record->unit_number} بنجاح")
                            ->success()
                            ->send();
                    }),

                // Quick Pay Action
                Action::make('record_payment')
                    ->label('استخلاص سريع')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->modalHeading(fn (Apartment $record) => "تسجيل استخلاص واجب الشقة {$record->unit_number}")
                    ->form([
                        Forms\Components\TextInput::make('amount_paid')
                            ->label('المبلغ المؤدى (درهم)')
                            ->numeric()
                            ->required()
                            ->default(fn (Apartment $record) => $record->monthly_fee)
                            ->prefix('درهم'),

                        Forms\Components\Select::make('payment_method')
                            ->label('طريقة الأداء')
                            ->options([
                                'CASH'          => '💵 نقداً (صندوق الإقامة)',
                                'BANK_TRANSFER' => '🏦 تحويل بنكي (الحساب البنكي)',
                                'CHEQUE'        => '🧾 شيك بنكي',
                            ])
                            ->default('CASH')
                            ->required(),

                        Forms\Components\DatePicker::make('payment_date')
                            ->label('تاريخ الأداء')
                            ->default(now())
                            ->required(),

                        Forms\Components\TagsInput::make('covered_months')
                            ->label('الأشهر المغطاة')
                            ->default([now()->format('Y-m')])
                            ->required(),
                    ])
                    ->action(function (Apartment $record, array $data) {
                        $paymentService = app(PaymentService::class);
                        $payment = $paymentService->recordPayment([
                            'apartment_id'   => $record->id,
                            'amount_paid'    => (float) $data['amount_paid'],
                            'payment_method' => $data['payment_method'],
                            'payment_date'   => $data['payment_date'],
                            'covered_months' => $data['covered_months'],
                        ], auth()->id(), auth()->user()->tenant_id);

                        $paymentService->confirmPayment($payment, auth()->id());

                        Notification::make()
                            ->title("تم تسجيل وتأكيد وصل أداء الشقة {$record->unit_number} بنجاح")
                            ->body("رقم الوصل: {$payment->receipt_number}")
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

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListApartments::route('/'),
            'create' => Pages\CreateApartment::route('/create'),
            'edit'   => Pages\EditApartment::route('/{record}/edit'),
        ];
    }
}
