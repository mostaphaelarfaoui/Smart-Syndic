<?php

namespace App\Filament\Syndic\Pages;

use App\Models\AuditLog;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ResidenceSettings extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'إعدادات الإقامة والسانديك';

    protected static ?string $navigationGroup = 'الإعدادات والرقابة';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'إعدادات الإقامة وملف السانديك';

    protected static string $view = 'filament.syndic.pages.residence-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $tenant = auth()->user()?->tenant;

        if ($tenant) {
            $this->form->fill([
                'name'                     => $tenant->name,
                'property_type'            => $tenant->property_type,
                'bank_rib'                 => $tenant->bank_rib,
                'is_transparency_enabled'  => (bool) $tenant->is_transparency_enabled,
                'stamp_image_path'         => $tenant->stamp_image_path,
                'initial_treasury_balance' => (float) $tenant->initial_treasury_balance,
                'initial_bank_balance'     => (float) $tenant->initial_bank_balance,
                'initial_balance_locked'   => (bool) $tenant->initial_balance_locked,
                'invitation_code'          => $tenant->invitation_code,
            ]);
        }
    }

    public function form(Form $form): Form
    {
        $tenant = auth()->user()?->tenant;
        $isLocked = $tenant?->initial_balance_locked ?? false;

        return $form
            ->schema([
                Forms\Components\Section::make('المعلومات الأساسية للإقامة')
                    ->description('اسم الإقامة ونوع الملكية المشتركة وكود الانضمام')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم الإقامة أو المجمع السكني')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('property_type')
                            ->label('نوع الملكية العقارية')
                            ->options([
                                'SINGLE_BUILDING'        => '🏢 عمارة منفردة',
                                'RESIDENTIAL_COMPLEX'    => '🏘️ مجمع سكني / إقامة مغلقة',
                                'HOUSING_ESTATE_VILLAS' => '🏡 تجزئة فيلات',
                                'COMMERCIAL_RESIDENTIAL' => '🏬 مجمع تجاري وسكني مختلط',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('invitation_code')
                            ->label('كود دعوة السكان (Invitation Code)')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('يستخدمه السكان الجدد عند التسجيل في تطبيق الهاتف للانضمام لهذه الإقامة تلقائياً.')
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('regenerate_code')
                                    ->label('تجديد الكود')
                                    ->icon('heroicon-o-arrow-path')
                                    ->color('warning')
                                    ->requiresConfirmation()
                                    ->modalHeading('تجديد كود دعوة السكان')
                                    ->modalDescription('هل أنت متأكد؟ سيتوقف الكود القديم عن العمل ولن يتمكن السكان من استخدامه.')
                                    ->action(function () {
                                        $tenant = auth()->user()?->tenant;
                                        if ($tenant) {
                                            $tenant->regenerateInvitationCode();
                                            $this->form->fill(['invitation_code' => $tenant->fresh()->invitation_code]);
                                            Notification::make()
                                                ->title('تم تجديد كود الدعوة بنجاح')
                                                ->success()
                                                ->send();
                                        }
                                    })
                            ),
                    ])->columns(2),

                Forms\Components\Section::make('الحساب البنكي والشفافية المالية')
                    ->description('بيانات الحساب البنكي وإعدادات خصوصية التقارير المالية')
                    ->schema([
                        Forms\Components\TextInput::make('bank_rib')
                            ->label('رقم الحساب البنكي (RIB - 24 رقماً)')
                            ->placeholder('مثال: 011780000012345678901234')
                            ->maxLength(34)
                            ->helperText('يظهر للسكان في التطبيق للقيام بالتحويلات البنكية المباشرة.'),

                        Forms\Components\Toggle::make('is_transparency_enabled')
                            ->label('تفعيل وضع الشفافية المالية للسكان')
                            ->helperText('عند التفعيل، يمكن لجميع السكان الاطلاع على التقارير المالية والوصولات المعتمدة من خلال تطبيق الهاتف.')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('خاتم وطابع السانديك الرسمي (Cachet)')
                    ->description('يرفق الخاتم تلقائياً أسفل وصولات الأداء PDF كإثبات رسمي')
                    ->schema([
                        Forms\Components\FileUpload::make('stamp_image_path')
                            ->label('صورة الخاتم أو التوقيع')
                            ->directory('stamps')
                            ->image()
                            ->maxSize(2048)
                            ->helperText('يُفضل رفع صورة واضحة بخلفية شفافة (PNG أو JPG).'),
                    ]),

                Forms\Components\Section::make('الأرصدة الافتتاحية للخزينة (عند بدء إدارة السانديك)')
                    ->description($isLocked
                        ? '🔒 تم قفل الأرصدة الافتتاحية نهائياً لحماية سلامة المحاسبة المالية.'
                        : 'حدد الرصيد الابتدائي المتوفر في صندوق الإقامة والحساب البنكي عند استلام التسيير.')
                    ->schema([
                        Forms\Components\TextInput::make('initial_treasury_balance')
                            ->label('رصيد الصندوق الافتتاحي (السيولة النقدية)')
                            ->numeric()
                            ->prefix('درهم')
                            ->default(0)
                            ->disabled($isLocked)
                            ->helperText('المبلغ النقدي المتوفر فعلياً في صندوق الإقامة.'),

                        Forms\Components\TextInput::make('initial_bank_balance')
                            ->label('رصيد الحساب البنكي الافتتاحي')
                            ->numeric()
                            ->prefix('درهم')
                            ->default(0)
                            ->disabled($isLocked)
                            ->helperText('الرصيد المتوفر في الحساب البنكي للإقامة.'),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $formData = $this->form->getState();
        $tenant   = auth()->user()?->tenant;

        if (! $tenant) {
            return;
        }

        $updateData = [
            'name'                    => $formData['name'],
            'property_type'           => $formData['property_type'],
            'bank_rib'                => $formData['bank_rib'] ?? null,
            'is_transparency_enabled' => (bool) ($formData['is_transparency_enabled'] ?? true),
            'stamp_image_path'        => $formData['stamp_image_path'] ?? $tenant->stamp_image_path,
        ];

        // Handle initial balances if not locked
        if (! $tenant->initial_balance_locked) {
            if (isset($formData['initial_treasury_balance'])) {
                $updateData['initial_treasury_balance'] = (float) $formData['initial_treasury_balance'];
            }
            if (isset($formData['initial_bank_balance'])) {
                $updateData['initial_bank_balance'] = (float) $formData['initial_bank_balance'];
            }

            if (! empty($formData['initial_treasury_balance']) || ! empty($formData['initial_bank_balance'])) {
                $updateData['initial_balance_locked'] = true;
            }
        }

        $tenant->update($updateData);

        AuditLog::record(
            actorId: auth()->id(),
            action: 'TENANT_SETTINGS_UPDATED',
            targetTenantId: $tenant->id,
            metadata: ['updated_fields' => array_keys($updateData)]
        );

        Notification::make()
            ->title('تم حفظ إعدادات الإقامة بنجاح')
            ->success()
            ->send();
    }
}
