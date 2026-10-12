<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\TenantResource\Pages;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Poll;
use App\Models\Suggestion;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AuditService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\DB;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'إدارة الإقامات والمنظمات';

    protected static ?string $navigationGroup = 'إدارة المنصة';

    protected static ?int $navigationSort = 1;

    protected static bool $isScopedToTenant = false;

    public static function getModelLabel(): string
    {
        return 'إقامة / تنظيم';
    }

    public static function getPluralModelLabel(): string
    {
        return 'الإقامات والمنظمات';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('بيانات المنظمة / الإقامة')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم الإقامة أو المجمع')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('property_type')
                            ->label('نوع الملكية العقارية')
                            ->options([
                                'SINGLE_BUILDING'        => 'عمارة منفردة',
                                'MULTI_BUILDING_COMPLEX' => 'مجمع سكني متعدد العمارات',
                            ])
                            ->required(),
                        Forms\Components\Select::make('plan_type')
                            ->label('نوع الباقة / الاشتراك')
                            ->options([
                                'TRIAL'      => 'تجريبية (Trial)',
                                'PRO'        => 'احترافية (Pro)',
                                'ENTERPRISE' => 'مؤسساتية (Enterprise)',
                            ])
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('حالة الحساب')
                            ->options([
                                'PENDING_APPROVAL' => 'في انتظار الموافقة',
                                'ACTIVE'           => 'مفعل',
                                'SUSPENDED'        => 'موقوف',
                                'EXPIRED'          => 'منتهي الصلاحية',
                            ])
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('الاشتراك والتواريخ')
                    ->schema([
                        Forms\Components\DateTimePicker::make('trial_ends_at')
                            ->label('تاريخ انتهاء الفترة التجريبية')
                            ->nullable(),
                        Forms\Components\DateTimePicker::make('subscription_expires_at')
                            ->label('تاريخ انتهاء الاشتراك')
                            ->nullable(),
                    ])->columns(2),

                Forms\Components\Section::make('الإعدادات والبيانات البنكية')
                    ->schema([
                        Forms\Components\TextInput::make('invitation_code')
                            ->label('رمز دعوة السكان')
                            ->readOnly()
                            ->maxLength(32),
                        Forms\Components\TextInput::make('bank_rib')
                            ->label('الرقم التعريفي البنكي RIB (24 رقماً)')
                            ->maxLength(34),
                        Forms\Components\Toggle::make('is_transparency_enabled')
                            ->label('تفعيل وضع الشفافية المالية للسكان'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الإقامة')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('users_count')
                    ->counts('users')
                    ->label('المسؤولين')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('plan_type')
                    ->label('الباقة')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'TRIAL'      => 'تجريبية',
                        'PRO'        => 'احترافية',
                        'ENTERPRISE' => 'مؤسساتية',
                        default      => $state,
                    })
                    ->colors([
                        'warning' => 'TRIAL',
                        'success' => 'PRO',
                        'primary' => 'ENTERPRISE',
                    ]),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'PENDING_APPROVAL' => 'في انتظار الموافقة',
                        'ACTIVE'           => 'مفعل',
                        'SUSPENDED'        => 'موقوف',
                        'EXPIRED'          => 'منتهي',
                        default            => $state,
                    })
                    ->colors([
                        'warning' => 'PENDING_APPROVAL',
                        'success' => 'ACTIVE',
                        'danger'  => 'SUSPENDED',
                        'gray'    => 'EXPIRED',
                    ]),
                Tables\Columns\TextColumn::make('trial_ends_at')
                    ->label('نهاية التجربة')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('حالة الحساب')
                    ->options([
                        'PENDING_APPROVAL' => 'في انتظار الموافقة',
                        'ACTIVE'           => 'مفعل',
                        'SUSPENDED'        => 'موقوف',
                        'EXPIRED'          => 'منتهي الصلاحية',
                    ]),
                Tables\Filters\SelectFilter::make('plan_type')
                    ->label('نوع الباقة')
                    ->options([
                        'TRIAL'      => 'تجريبية',
                        'PRO'        => 'احترافية',
                        'ENTERPRISE' => 'مؤسساتية',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                // Approve action for pending tenants
                Action::make('approve')
                    ->label('موافقة وتفعيل')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Tenant $record) => $record->status === 'PENDING_APPROVAL')
                    ->requiresConfirmation()
                    ->modalHeading('الموافقة على الإقامة وتفعيلها')
                    ->modalDescription('هل أنت متأكد من رغبتك في الموافقة على هذه الإقامة وتفعيل حساب المسير؟')
                    ->action(function (Tenant $record) {
                        $record->update(['status' => 'ACTIVE']);

                        // Also activate the admin user
                        User::where('tenant_id', $record->id)
                            ->where('role', 'ADMIN_SYNDIC')
                            ->update(['status' => 'ACTIVE']);

                        app(AuditService::class)->logTenantAction(
                            auth()->id(),
                            'TENANT_APPROVED',
                            $record->id,
                        );
                    }),

                // Suspend action
                Action::make('suspend')
                    ->label('إيقاف الإقامة')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn (Tenant $record) => $record->status === 'ACTIVE')
                    ->requiresConfirmation()
                    ->modalHeading('تعليق حساب الإقامة')
                    ->modalDescription('هل أنت متأكد من رغبتك في تعليق حساب هذه الإقامة؟')
                    ->action(function (Tenant $record) {
                        $record->update(['status' => 'SUSPENDED']);

                        app(AuditService::class)->logTenantAction(
                            auth()->id(),
                            'TENANT_SUSPENDED',
                            $record->id,
                        );
                    }),

                // Impersonate action
                Action::make('impersonate')
                    ->label('تسجيل الدخول كمسير')
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color('warning')
                    ->visible(fn (Tenant $record) => $record->status === 'ACTIVE')
                    ->requiresConfirmation()
                    ->modalHeading('الدخول كمسير الإقامة')
                    ->modalDescription('سيتم تسجيل دخولك كمسير لهذه الإقامة. سيتم تدوين جميع الأنشطة في سجل الرقابة والأمان.')
                    ->action(function (Tenant $record) {
                        $admin = User::where('tenant_id', $record->id)
                            ->where('role', 'ADMIN_SYNDIC')
                            ->first();

                        if ($admin) {
                            app(AuditService::class)->logImpersonation(
                                auth()->id(),
                                $admin->id,
                                $record->id,
                            );

                            auth()->login($admin);
                            redirect('/syndic');
                        }
                    }),

                // Feature 15 — Reject & cascade delete for PENDING_APPROVAL tenants
                Action::make('reject')
                    ->label('رفض وحذف')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Tenant $record) => $record->status === 'PENDING_APPROVAL')
                    ->requiresConfirmation()
                    ->modalHeading('رفض وحذف الإقامة نهائياً')
                    ->modalDescription('هذا الإجراء سيحذف الإقامة وجميع بياناتها (المستخدمون، الشقق، المدفوعات) بشكل نهائي ولا يمكن التراجع عنه.')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('سبب الرفض (اختياري)')
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->action(function (Tenant $record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            // Log before deletion
                            app(AuditService::class)->logTenantAction(
                                auth()->id(),
                                'TENANT_REJECTED',
                                $record->id,
                                [
                                    'tenant_name'      => $record->name,
                                    'rejection_reason' => $data['rejection_reason'] ?? null,
                                ]
                            );

                            // Cascade delete: payments, expenses, suggestions, polls, apartments, buildings, users, then tenant
                            $tenantId = $record->id;

                            Payment::where('tenant_id', $tenantId)->delete();
                            Expense::where('tenant_id', $tenantId)->delete();
                            Suggestion::where('tenant_id', $tenantId)->delete();
                            Poll::where('tenant_id', $tenantId)->delete();

                            $buildingIds = Building::where('tenant_id', $tenantId)->pluck('id');
                            Apartment::whereIn('building_id', $buildingIds)->delete();
                            Building::where('tenant_id', $tenantId)->delete();

                            User::where('tenant_id', $tenantId)->delete();

                            $record->delete();
                        });
                    }),
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
            'index'  => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit'   => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}
