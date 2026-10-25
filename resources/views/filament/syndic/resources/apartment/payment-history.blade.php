<div class="space-y-6 text-sm" dir="rtl">
    <!-- Apartment Overview -->
    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div>
            <div class="text-xs text-gray-500 dark:text-gray-400">العمارة / البلوك</div>
            <div class="font-bold text-gray-800 dark:text-gray-100">
                {{ $apartment->building?->name ?? '—' }}
                @if($apartment->building?->block)
                    <span class="text-xs text-primary-600 dark:text-primary-400">({{ $apartment->building->block }})</span>
                @endif
            </div>
        </div>
        <div>
            <div class="text-xs text-gray-500 dark:text-gray-400">الساكن الحالي</div>
            <div class="font-bold text-gray-800 dark:text-gray-100">
                @if($apartment->residents->isNotEmpty())
                    {{ $apartment->residents->first()->full_name }}
                    <span class="text-xs block text-gray-500">{{ $apartment->residents->first()->phone }}</span>
                @else
                    <span class="text-amber-500">شاغرة</span>
                @endif
            </div>
        </div>
        <div>
            <div class="text-xs text-gray-500 dark:text-gray-400">الواجب الشهري</div>
            <div class="font-bold text-emerald-600 dark:text-emerald-400">
                {{ number_format((float)$apartment->monthly_fee, 2) }} درهم
            </div>
        </div>
        <div>
            <div class="text-xs text-gray-500 dark:text-gray-400">إجمالي الوصولات</div>
            <div class="font-bold text-primary-600 dark:text-primary-400">
                {{ $apartment->payments->count() }} وصل
            </div>
        </div>
    </div>

    <!-- Payments Grouped By Year -->
    @if($paymentsByYear->isEmpty())
        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <p>لا توجد أي وصولات أداء مسجلة لهذه الشقة حتى الآن.</p>
        </div>
    @else
        <div class="space-y-6">
            @foreach($paymentsByYear as $year => $payments)
                @php
                    $yearTotal = $payments->sum('amount_paid');
                @endphp
                <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm">
                    <!-- Year Header -->
                    <div class="bg-primary-50 dark:bg-gray-800/80 px-4 py-3 flex items-center justify-between border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-primary-600 text-white font-bold text-sm">
                                📅
                            </span>
                            <span class="font-bold text-base text-gray-800 dark:text-gray-100">سنة {{ $year }}</span>
                            <span class="text-xs bg-primary-100 dark:bg-primary-950/50 text-primary-700 dark:text-primary-300 px-2 py-0.5 rounded-full font-medium">
                                {{ $payments->count() }} {{ $payments->count() == 1 ? 'وصل' : 'وصولات' }}
                            </span>
                        </div>
                        <div class="font-bold text-emerald-600 dark:text-emerald-400 text-sm">
                            المجموع: {{ number_format($yearTotal, 2) }} د.م.
                        </div>
                    </div>

                    <!-- Year Payments Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-right text-xs">
                            <thead class="bg-gray-50/70 dark:bg-gray-900/50 text-gray-500 dark:text-gray-400 uppercase border-b border-gray-200 dark:border-gray-800">
                                <tr>
                                    <th class="py-2.5 px-3">رقم الوصل</th>
                                    <th class="py-2.5 px-3">تاريخ الأداء</th>
                                    <th class="py-2.5 px-3">الأشهر المغطاة</th>
                                    <th class="py-2.5 px-3">المبلغ</th>
                                    <th class="py-2.5 px-3">طريقة الأداء</th>
                                    <th class="py-2.5 px-3 text-center">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach($payments as $payment)
                                    @php
                                        $months = is_array($payment->covered_months) 
                                            ? implode(', ', $payment->covered_months) 
                                            : ($payment->covered_months ?? '—');
                                        $methodLabel = match($payment->payment_method) {
                                            'CASH'          => 'نقداً',
                                            'BANK_TRANSFER' => 'تحويل بنكي',
                                            'CHEQUE'        => 'شيك',
                                            default         => $payment->payment_method,
                                        };
                                        $previewUrl = route('receipts.download', ['payment' => $payment->id]);
                                        $downloadUrl = route('receipts.download', ['payment' => $payment->id, 'download' => 1]);
                                    @endphp
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                                        <td class="py-2.5 px-3 font-semibold text-primary-600 dark:text-primary-400 font-mono">
                                            {{ $payment->receipt_number }}
                                        </td>
                                        <td class="py-2.5 px-3 text-gray-600 dark:text-gray-300">
                                            {{ \Carbon\Carbon::parse($payment->payment_date)->format('Y/m/d') }}
                                        </td>
                                        <td class="py-2.5 px-3 text-gray-700 dark:text-gray-200 font-medium">
                                            {{ $months }}
                                        </td>
                                        <td class="py-2.5 px-3 font-bold text-emerald-600 dark:text-emerald-400">
                                            {{ number_format((float)$payment->amount_paid, 2) }} د.م.
                                        </td>
                                        <td class="py-2.5 px-3">
                                            <span class="inline-block px-2 py-0.5 rounded text-[11px] font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                                {{ $methodLabel }}
                                            </span>
                                        </td>
                                        <td class="py-2.5 px-3 text-center">
                                            <div class="inline-flex items-center gap-1.5">
                                                <a href="{{ $previewUrl }}" target="_blank"
                                                   class="inline-flex items-center gap-1 px-2 py-1 rounded bg-blue-50 dark:bg-blue-900/40 text-blue-600 dark:text-blue-300 hover:bg-blue-100 text-[11px] font-semibold transition"
                                                   title="معاينة الوصل">
                                                    👁️ معاينة
                                                </a>
                                                <a href="{{ $downloadUrl }}"
                                                   class="inline-flex items-center gap-1 px-2 py-1 rounded bg-emerald-50 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-300 hover:bg-emerald-100 text-[11px] font-semibold transition"
                                                   title="تحميل PDF">
                                                    ⬇️ تحميل
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
