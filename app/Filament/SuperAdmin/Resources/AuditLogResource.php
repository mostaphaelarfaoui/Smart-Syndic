<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\AuditLogResource\Pages;
use App\Models\AuditLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'سجلات الرقابة والعمليات';

    protected static ?string $navigationGroup = 'الأمان والرقابة';

    protected static ?int $navigationSort = 10;

    protected static bool $isScopedToTenant = false;

    public static function getModelLabel(): string
    {
        return 'سجل رقابة';
    }

    public static function getPluralModelLabel(): string
    {
        return 'سجلات الرقابة والعمليات';
    }

    public static function canCreate(): bool
    {
        return false; // Audit logs are immutable — no manual creation
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
                    ->searchable()
                    ->colors([
                        'success' => fn ($state) => str_contains($state, 'APPROVED'),
                        'danger'  => fn ($state) => str_contains($state, 'REJECTED') || str_contains($state, 'SUSPENDED'),
                        'warning' => fn ($state) => str_contains($state, 'IMPERSONATE'),
                        'info'    => fn ($state) => str_contains($state, 'RECORDED'),
                    ]),
                Tables\Columns\TextColumn::make('targetTenant.name')
                    ->label('الإقامة المعنية')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('عنوان IP'),
                Tables\Columns\TextColumn::make('user_agent')
                    ->label('المتصفح والنظام')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->label('نوع العملية')
                    ->options(fn () => AuditLog::distinct()->pluck('action', 'action')->toArray()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض السجل')
                    ->modalHeading('تفاصيل سجل الرقابة')
                    ->modalContent(fn (AuditLog $record) => view('filament.super-admin.audit-detail', ['log' => $record])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
        ];
    }
}
