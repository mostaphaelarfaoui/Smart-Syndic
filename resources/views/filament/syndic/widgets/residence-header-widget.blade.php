<x-filament-widgets::widget class="fi-wi-residence-header">
    <x-filament::section>
        <div class="flex flex-col gap-y-4">
            {{-- Top Row: Greeting & Invitation Code --}}
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 border-b border-gray-200 pb-4 dark:border-gray-800">
                <div class="space-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                            مرحباً بك، {{ $user->full_name }} 👋
                        </h2>
                        <x-filament::badge color="info" size="sm">
                            {{ $propertyTypeLabel }}
                        </x-filament::badge>
                        <x-filament::badge color="success" size="sm">
                            نظام التسيير نشط
                        </x-filament::badge>
                    </div>

                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        لوحة القيادة والمؤشرات العامة لإدارة <strong class="text-gray-900 dark:text-gray-200 font-semibold">{{ $tenant->name }}</strong>
                    </p>
                </div>

                {{-- Invitation Code Box --}}
                <div x-data="{ copied: false }" class="flex items-center gap-2 rounded-xl bg-gray-50 px-3 py-2 ring-1 ring-gray-200 dark:bg-gray-900/60 dark:ring-gray-800 self-start lg:self-center">
                    <div class="text-xs text-gray-600 dark:text-gray-400">
                        <span class="font-medium">كود انضمام السكان:</span>
                        <code class="font-mono font-bold text-primary-600 dark:text-primary-400 px-1 text-sm tracking-wider select-all">
                            {{ $tenant->invitation_code ?? '—' }}
                        </code>
                    </div>
                    <x-filament::button
                        size="xs"
                        color="gray"
                        icon="heroicon-m-clipboard-document"
                        x-on:click="navigator.clipboard.writeText('{{ $tenant->invitation_code }}'); copied = true; setTimeout(() => copied = false, 2500)"
                    >
                        <span x-show="!copied">نسخ</span>
                        <span x-show="copied" x-cloak class="text-success-600 font-bold">تم النسخ!</span>
                    </x-filament::button>
                </div>
            </div>

            {{-- Bottom Row: Summary Badges & Action Buttons --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pt-1">
                {{-- Quick Statistics Badges --}}
                <div class="flex flex-wrap items-center gap-2">
                    <x-filament::badge color="gray" icon="heroicon-m-building-office-2">
                        العمارات: {{ $totalBuildings }}
                    </x-filament::badge>

                    <x-filament::badge color="info" icon="heroicon-m-home-modern">
                        الشقق: {{ $totalApartments }}
                    </x-filament::badge>

                    <x-filament::badge color="success" icon="heroicon-m-users">
                        السكان المفعلين: {{ $totalResidents }}
                    </x-filament::badge>
                </div>

                {{-- Action Buttons --}}
                <div class="flex flex-wrap items-center gap-2">
                    <x-filament::button
                        tag="a"
                        href="{{ $paymentCreateUrl }}"
                        color="success"
                        icon="heroicon-m-plus-circle"
                        size="sm"
                    >
                        استخلاص دفعة
                    </x-filament::button>

                    <x-filament::button
                        tag="a"
                        href="{{ $expenseCreateUrl }}"
                        color="danger"
                        icon="heroicon-m-receipt-percent"
                        size="sm"
                    >
                        إضافة مصروف
                    </x-filament::button>

                    <x-filament::button
                        tag="a"
                        href="{{ $apartmentsIndexUrl }}"
                        color="primary"
                        icon="heroicon-m-home-modern"
                        size="sm"
                    >
                        إدارة الشقق
                    </x-filament::button>

                    <x-filament::button
                        tag="a"
                        href="{{ $residenceSettingsUrl }}"
                        color="gray"
                        icon="heroicon-m-cog-6-tooth"
                        size="sm"
                    >
                        إعدادات الإقامة
                    </x-filament::button>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
