<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-teal-800 via-teal-700 to-cyan-800 p-6 sm:p-8 text-white shadow-xl">
    <!-- Background subtle glow patterns -->
    <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-white/10 blur-2xl pointer-events-none"></div>
    <div class="absolute -left-10 -bottom-10 h-40 w-40 rounded-full bg-cyan-400/10 blur-2xl pointer-events-none"></div>

    <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
        <!-- Residence info & greeting -->
        <div class="space-y-3">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold backdrop-blur-md">
                    <x-heroicon-m-building-office class="h-4 w-4 text-teal-200" />
                    {{ $propertyTypeLabel }}
                </span>
                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/20 px-3 py-1 text-xs font-medium text-emerald-200 border border-emerald-400/30">
                    <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    نظام التسيير نشط
                </span>
            </div>

            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                مرحباً بك، {{ $user->full_name }} 👋
            </h1>

            <p class="text-teal-100 text-sm sm:text-base max-w-2xl leading-relaxed">
                لوحة القيادة الموحدة لإدارة <strong class="text-white">{{ $tenant->name }}</strong>. تابع المداخيل، المصاريف، الخزينة، وطلبات السكان في لمحة واحدة.
            </p>

            <!-- Quick Stats Pills -->
            <div class="flex flex-wrap items-center gap-4 pt-2 text-xs sm:text-sm font-medium text-teal-100">
                <div class="flex items-center gap-1.5 bg-black/20 rounded-lg px-3 py-1.5 backdrop-blur-sm">
                    <x-heroicon-m-building-office-2 class="h-4 w-4 text-teal-300" />
                    <span>العمارات: <strong class="text-white font-bold">{{ $totalBuildings }}</strong></span>
                </div>
                <div class="flex items-center gap-1.5 bg-black/20 rounded-lg px-3 py-1.5 backdrop-blur-sm">
                    <x-heroicon-m-home-modern class="h-4 w-4 text-teal-300" />
                    <span>الشقق: <strong class="text-white font-bold">{{ $totalApartments }}</strong></span>
                </div>
                <div class="flex items-center gap-1.5 bg-black/20 rounded-lg px-3 py-1.5 backdrop-blur-sm">
                    <x-heroicon-m-users class="h-4 w-4 text-teal-300" />
                    <span>السكان المفعلين: <strong class="text-white font-bold">{{ $totalResidents }}</strong></span>
                </div>
            </div>
        </div>

        <!-- Right Side: Invitation Code & Quick Actions -->
        <div class="flex flex-col sm:flex-row lg:flex-col items-start lg:items-end gap-4 shrink-0">
            <!-- Invitation Code Card with Copy -->
            <div x-data="{ copied: false }" class="bg-black/25 backdrop-blur-md rounded-xl p-3.5 border border-white/10 w-full sm:w-auto">
                <div class="text-[11px] text-teal-200 font-medium mb-1 flex items-center justify-between gap-4">
                    <span>كود دعوة السكان للانضمام:</span>
                    <span x-show="copied" x-cloak class="text-emerald-300 font-bold text-xs">تم النسخ بنجاح!</span>
                </div>
                <div class="flex items-center gap-2">
                    <code class="bg-white/20 text-white font-mono text-base font-bold tracking-widest px-3 py-1 rounded-lg border border-white/20">
                        {{ $tenant->invitation_code ?? '—' }}
                    </code>
                    <button
                        type="button"
                        @click="navigator.clipboard.writeText('{{ $tenant->invitation_code }}'); copied = true; setTimeout(() => copied = false, 2500)"
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-teal-600 hover:bg-teal-500 text-white text-xs font-semibold transition active:scale-95 shadow"
                        title="نسخ كود الدعوة"
                    >
                        <x-heroicon-m-clipboard-document class="h-4 w-4" />
                        <span>نسخ</span>
                    </button>
                </div>
            </div>

            <!-- Fast Action Buttons -->
            <div class="flex flex-wrap items-center gap-2">
                <a
                    href="{{ url('/syndic/payments/create') }}"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white text-teal-900 hover:bg-teal-50 text-xs font-bold shadow-md transition transform hover:-translate-y-0.5"
                >
                    <x-heroicon-m-plus-circle class="h-4 w-4 text-teal-700" />
                    <span>استخلاص دفعة</span>
                </a>

                <a
                    href="{{ url('/syndic/expenses/create') }}"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-teal-600/60 hover:bg-teal-600 text-white text-xs font-bold border border-white/20 shadow-md transition transform hover:-translate-y-0.5"
                >
                    <x-heroicon-m-receipt-percent class="h-4 w-4" />
                    <span>إضافة مصروف</span>
                </a>

                <a
                    href="{{ url('/syndic/apartments') }}"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-teal-900/60 hover:bg-teal-900 text-teal-100 text-xs font-bold border border-white/10 transition"
                >
                    <x-heroicon-m-home-modern class="h-4 w-4" />
                    <span>الشقق</span>
                </a>
            </div>
        </div>
    </div>
</div>
