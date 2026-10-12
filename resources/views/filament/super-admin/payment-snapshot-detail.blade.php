<div class="space-y-4 p-4 text-right" dir="rtl">
    <div class="grid grid-cols-2 gap-4">
        <div class="bg-gray-50 rounded-lg p-3">
            <p class="text-xs text-gray-500 mb-1">رقم الوصل</p>
            <p class="font-bold text-lg text-primary-600">{{ $snapshot['receipt_number'] ?? '—' }}</p>
        </div>
        <div class="bg-gray-50 rounded-lg p-3">
            <p class="text-xs text-gray-500 mb-1">تاريخ الإصدار</p>
            <p class="font-medium">{{ $log->created_at->format('Y-m-d H:i') }}</p>
        </div>
        <div class="bg-gray-50 rounded-lg p-3">
            <p class="text-xs text-gray-500 mb-1">اسم الساكن</p>
            <p class="font-medium">{{ $snapshot['resident_name'] ?? '—' }}</p>
        </div>
        <div class="bg-gray-50 rounded-lg p-3">
            <p class="text-xs text-gray-500 mb-1">الشقة</p>
            <p class="font-medium">{{ $snapshot['apartment_label'] ?? '—' }}</p>
        </div>
        <div class="bg-gray-50 rounded-lg p-3">
            <p class="text-xs text-gray-500 mb-1">المبلغ المدفوع</p>
            <p class="font-bold text-green-600">{{ number_format((float)($snapshot['amount'] ?? 0), 2) }} درهم</p>
        </div>
        <div class="bg-gray-50 rounded-lg p-3">
            <p class="text-xs text-gray-500 mb-1">طريقة الدفع</p>
            <p class="font-medium">{{ $snapshot['payment_method'] ?? '—' }}</p>
        </div>
    </div>

    @if(!empty($snapshot['months_covered']))
    <div class="bg-blue-50 rounded-lg p-3">
        <p class="text-xs text-gray-500 mb-2">الأشهر المشمولة</p>
        <div class="flex flex-wrap gap-2">
            @foreach((array)$snapshot['months_covered'] as $month)
            <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-sm">{{ $month }}</span>
            @endforeach
        </div>
    </div>
    @endif

    @if(!empty($snapshot['notes']))
    <div class="bg-yellow-50 rounded-lg p-3">
        <p class="text-xs text-gray-500 mb-1">ملاحظات</p>
        <p class="text-sm">{{ $snapshot['notes'] }}</p>
    </div>
    @endif

    <div class="bg-gray-100 rounded-lg p-3 text-xs text-gray-500">
        <p>المسير المُصدِر: <strong>{{ $log->actor->full_name ?? '—' }}</strong></p>
        <p>الإقامة: <strong>{{ $log->targetTenant->name ?? '—' }}</strong></p>
        <p>عنوان IP: <strong>{{ $log->ip_address }}</strong></p>
    </div>
</div>
