<?php

namespace App\Filament\Syndic\Resources;

use App\Filament\Syndic\Resources\ResidentResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;

class ResidentResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'السكان والمقيمين';

    protected static ?string $navigationGroup = 'إدارة الإقامة';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'ساكن';

    protected static ?string $pluralModelLabel = 'السكان والمقيمين';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('role', 'RESIDENT')
            ->where('tenant_id', auth()->user()->tenant_id);
    }

    public static function canCreate(): bool
    {
        return false; // Residents self-register via mobile app
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('المعلومات الشخصية')
                    ->schema([
                        Forms\Components\TextInput::make('first_name')
                            ->label('الاسم الشخصي')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('last_name')
                            ->label('الاسم العائلي')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('email')
                            ->label('البريد الإلكتروني')
                            ->email()
                            ->required(),
                        Forms\Components\TextInput::make('phone')
                            ->label('رقم الهاتف')
                            ->required(),
                    ])->columns(2),
                Forms\Components\Section::make('بيانات الحساب')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('حالة الحساب')
                            ->options([
                                'PENDING'   => 'في انتظار الموافقة',
                                'ACTIVE'    => 'مفعل',
                                'SUSPENDED' => 'موقوف',
                            ])
                            ->required(),
                        Forms\Components\Select::make('locale')
                            ->label('اللغة المفضلة')
                            ->options([
                                'ar' => 'العربية',
                                'fr' => 'الفرنسية',
                                'en' => 'الإنجليزية',
                            ]),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('الاسم الكامل')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['first_name']),
                Tables\Columns\TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('رقم الهاتف')
                    ->searchable(),
                Tables\Columns\TextColumn::make('apartments.unit_number')
                    ->label('رقم الشقة')
                    ->badge(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'PENDING'   => 'في انتظار الموافقة',
                        'ACTIVE'    => 'مفعل',
                        'SUSPENDED' => 'موقوف',
                        default     => $state,
                    })
                    ->colors([
                        'warning' => 'PENDING',
                        'success' => 'ACTIVE',
                        'danger'  => 'SUSPENDED',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('حالة الحساب')
                    ->options([
                        'PENDING'   => 'في انتظار الموافقة',
                        'ACTIVE'    => 'مفعل',
                        'SUSPENDED' => 'موقوف',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('approve')
                    ->label('موافقة وتفعيل')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (User $r) => $r->status === 'PENDING')
                    ->requiresConfirmation()
                    ->modalHeading('الموافقة على تفعيل حساب الساكن')
                    ->modalDescription('هل أنت متأكد من رغبتك في تفعيل حساب هذا الساكن؟ سيتمكن من الولوج إلى التطبيق.')
                    ->action(function (User $record) {
                        $record->update(['status' => 'ACTIVE']);
                        event(new \App\Events\ResidentApproved($record));
                    }),
                Action::make('suspend')
                    ->label('تعليق الحساب')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn (User $r) => $r->status === 'ACTIVE')
                    ->requiresConfirmation()
                    ->modalHeading('تعليق حساب الساكن')
                    ->modalDescription('هل أنت متأكد من رغبتك في تعليق حساب هذا الساكن؟')
                    ->action(fn (User $record) => $record->update(['status' => 'SUSPENDED'])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListResidents::route('/'),
            'edit'  => Pages\EditResident::route('/{record}/edit'),
        ];
    }
}
