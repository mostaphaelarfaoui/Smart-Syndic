<?php

namespace App\Filament\Syndic\Widgets;

use App\Events\ResidentApproved;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class PendingAlertsWidget extends TableWidget
{
    protected static ?string $heading = '⏳ طلبات انضمام السكان المعلقة في انتظار التفعيل';

    protected static ?int $sort = 8;

    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    public function table(Table $table): Table
    {
        $tenantId = auth()->user()?->tenant_id;

        return $table
            ->query(
                User::where('tenant_id', $tenantId)
                    ->where('role', 'RESIDENT')
                    ->where('status', 'PENDING')
                    ->with('apartments')
                    ->latest('created_at')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('الاسم الكامل')
                    ->weight('bold')
                    ->icon('heroicon-m-user'),

                Tables\Columns\TextColumn::make('phone')
                    ->label('رقم الهاتف')
                    ->copyable(),

                Tables\Columns\TextColumn::make('apartments.unit_number')
                    ->label('الشقة')
                    ->badge()
                    ->placeholder('غير محددة'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الطلب')
                    ->since(),
            ])
            ->actions([
                Action::make('approve')
                    ->label('موافقة')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->button()
                    ->requiresConfirmation()
                    ->modalHeading('تفعيل حساب الساكن')
                    ->modalDescription('هل تؤكد رغبتك في تفعيل حساب هذا الساكن؟ سيتمكن من تسجيل الدخول فوراً.')
                    ->action(function (User $record) {
                        $record->update(['status' => 'ACTIVE']);
                        event(new ResidentApproved($record));

                        Notification::make()
                            ->title("تم تفعيل حساب الساكن {$record->full_name} بنجاح")
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('لا توجد طلبات انضمام معلقة')
            ->emptyStateDescription('جميع طلبات السكان تمت معالجتها وتفعيلها.')
            ->emptyStateIcon('heroicon-o-check-badge')
            ->paginated(false);
    }
}
