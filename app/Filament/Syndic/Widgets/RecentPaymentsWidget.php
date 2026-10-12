<?php

namespace App\Filament\Syndic\Widgets;

use App\Models\Payment;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentPaymentsWidget extends TableWidget
{
    protected static ?string $heading = '💳 آخر المقبوضات والتحصيلات المسجلة';

    protected static ?int $sort = 7;

    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 2,
    ];

    public function table(Table $table): Table
    {
        $tenantId = auth()->user()?->tenant_id;

        return $table
            ->query(
                Payment::where('tenant_id', $tenantId)
                    ->where('status', '!=', 'DRAFT')
                    ->with(['apartment.building', 'recorder'])
                    ->latest('payment_date')
                    ->limit(6)
            )
            ->columns([
                Tables\Columns\TextColumn::make('receipt_number')
                    ->label('رقم الوصل')
                    ->copyable()
                    ->weight('bold')
                    ->icon('heroicon-m-document-text'),

                Tables\Columns\TextColumn::make('apartment.unit_number')
                    ->label('الشقة')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state, Payment $record) => ($record->apartment?->building?->name ? "{$record->apartment->building->name} - " : '') . "شقة {$state}"),

                Tables\Columns\TextColumn::make('amount_paid')
                    ->label('المبلغ')
                    ->money('MAD')
                    ->weight('bold')
                    ->color('success'),

                Tables\Columns\BadgeColumn::make('payment_method')
                    ->label('الطريقة')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'CASH'          => 'نقداً',
                        'BANK_TRANSFER' => 'تحويل',
                        'CHEQUE'        => 'شيك',
                        default         => $state,
                    })
                    ->colors([
                        'success' => 'CASH',
                        'info'    => 'BANK_TRANSFER',
                        'warning' => 'CHEQUE',
                    ]),

                Tables\Columns\TextColumn::make('payment_date')
                    ->label('التاريخ')
                    ->date('d/m/Y'),
            ])
            ->actions([
                Action::make('download_receipt')
                    ->label('تحميل')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->url(fn (Payment $record) => $record->receipt_url)
                    ->openUrlInNewTab(),
            ])
            ->paginated(false);
    }
}
