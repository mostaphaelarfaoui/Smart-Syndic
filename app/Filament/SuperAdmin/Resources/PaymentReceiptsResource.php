<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\PaymentReceiptsResource\Pages;
use App\Models\AuditLog;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;

/**
 * Feature 3 — Payment Receipts Archive for SuperAdmin.
 *
 * Every time a syndic generates a payment receipt, a snapshot is saved
 * in audit_logs.payment_snapshot. This resource lets the super admin
 * browse and review any receipt in case of disputes.
 */
class PaymentReceiptsResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'أرشيف وصولات الأداء';

    protected static ?string $navigationGroup = 'الأمان والرقابة';

    protected static ?int $navigationSort = 11;

    protected static bool $isScopedToTenant = false;

    // Custom slug to avoid clash with AuditLogResource
    protected static ?string $slug = 'payment-receipts';

    public static function getModelLabel(): string
    {
        return 'وصل أداء';
    }

    public static function getPluralModelLabel(): string
    {
        return 'أرشيف وصولات الأداء';
    }

    public static function canCreate(): bool
    {
        return false; // Read-only archive
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                // Only show audit log rows that have a payment_snapshot
                fn ($query) => $query->whereNotNull('payment_snapshot')
                    ->orderByDesc('created_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإصدار')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_snapshot->receipt_number')
                    ->label('رقم الوصل')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('targetTenant.name')
                    ->label('الإقامة / السانديك')
                    ->searchable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('payment_snapshot->resident_name')
                    ->label('اسم الساكن')
                    ->searchable(),

                Tables\Columns\TextColumn::make('payment_snapshot->apartment_label')
                    ->label('الشقة'),

                Tables\Columns\TextColumn::make('payment_snapshot->amount')
                    ->label('المبلغ')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2) . ' درهم'),

                Tables\Columns\TextColumn::make('payment_snapshot->months_covered')
                    ->label('الأشهر المدفوعة')
                    ->limit(40),

                Tables\Columns\TextColumn::make('actor.full_name')
                    ->label('المسير المُصدِر')
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('target_tenant_id')
                    ->label('الإقامة')
                    ->relationship('targetTenant', 'name'),
            ])
            ->actions([
                Action::make('view_snapshot')
                    ->label('عرض التفاصيل')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading('تفاصيل وصل الأداء المحفوظ')
                    ->modalContent(fn (AuditLog $record) => view(
                        'filament.super-admin.payment-snapshot-detail',
                        ['snapshot' => $record->payment_snapshot, 'log' => $record]
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('إغلاق'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentReceipts::route('/'),
        ];
    }
}
