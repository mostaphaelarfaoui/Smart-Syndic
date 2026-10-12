<?php

namespace App\Filament\Syndic\Resources;

use App\Filament\Syndic\Resources\AuditLogResource\Pages;
use App\Models\AuditLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'سجل العمليات والرقابة';

    protected static ?string $navigationGroup = 'الإعدادات والرقابة';

    protected static ?int $navigationSort = 10;

    protected static bool $isScopedToTenant = false;

    public static function getModelLabel(): string
    {
        return 'سجل عملية';
    }

    public static function getPluralModelLabel(): string
    {
        return 'سجل العمليات والرقابة';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $tenantId = auth()->user()?->tenant_id;

        return parent::getEloquentQuery()
            ->where('target_tenant_id', $tenantId)
            ->latest('created_at');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('التاريخ والوقت')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),

                Tables\Columns\TextColumn::make('actor.full_name')
                    ->label('المستخدم المنفذ')
                    ->searchable(['first_name', 'last_name']),

                Tables\Columns\BadgeColumn::make('action')
                    ->label('نوع العملية')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'PAYMENT_RECORDED'            => 'تسجيل دفعة / استخلاص',
                        'PAYMENT_CONFIRMED'           => 'تأكيد وصل أداء',
                        'RESIDENT_APPROVED'           => 'تفعيل وقبول ساكن',
                        'RESIDENT_SUSPENDED'          => 'تعليق حساب ساكن',
                        'APARTMENT_BILLABLE_ENABLED'  => 'تفعيل شقة (إلغاء الإعفاء)',
                        'APARTMENT_BILLABLE_DISABLED' => 'إعفاء شقة من الواجبات',
                        'EXPENSE_CREATED'             => 'تسجيل مصروف جديد',
                        'INITIAL_BALANCE_SET'         => 'تحديد الرصيد الافتتاحي',
                        default                       => $state,
                    })
                    ->colors([
                        'success' => fn ($state) => str_contains($state, 'APPROVED') || str_contains($state, 'CONFIRMED') || str_contains($state, 'RECORDED'),
                        'warning' => fn ($state) => str_contains($state, 'DISABLED') || str_contains($state, 'SUSPENDED'),
                        'info'    => fn ($state) => str_contains($state, 'ENABLED') || str_contains($state, 'INITIAL'),
                        'danger'  => fn ($state) => str_contains($state, 'DELETE') || str_contains($state, 'REJECT'),
                    ]),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('عنوان IP')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->label('نوع العملية')
                    ->options([
                        'PAYMENT_RECORDED'            => 'تسجيل دفعة / استخلاص',
                        'RESIDENT_APPROVED'           => 'تفعيل وقبول ساكن',
                        'RESIDENT_SUSPENDED'          => 'تعليق حساب ساكن',
                        'APARTMENT_BILLABLE_ENABLED'  => 'تفعيل شقة',
                        'APARTMENT_BILLABLE_DISABLED' => 'إعفاء شقة من الواجبات',
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
        ];
    }
}
