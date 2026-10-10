<div class="p-4" dir="rtl">
    <div class="mb-4">
        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">التاريخ والوقت:</span>
        <span class="text-sm font-semibold">{{ $log->created_at->format('Y-m-d H:i:s') }}</span>
    </div>
    <div class="mb-4">
        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">المستخدم المنفذ:</span>
        <span class="text-sm">{{ $log->actor->full_name ?? 'غير محدد' }} ({{ $log->actor->email ?? '—' }})</span>
    </div>
    <div class="mb-4">
        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">نوع العملية:</span>
        <span class="text-sm font-bold text-primary-600 dark:text-primary-400">{{ $log->action }}</span>
    </div>
    <div class="mb-4">
        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">عنوان IP:</span>
        <span class="text-sm font-mono dir-ltr inline-block">{{ $log->ip_address }}</span>
    </div>
    <div class="mb-4">
        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">المتصفح والنظام:</span>
        <span class="text-xs text-gray-600 dark:text-gray-300 block mt-1 dir-ltr text-left">{{ $log->user_agent }}</span>
    </div>
    <div>
        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">البيانات الإضافية (JSON):</span>
        <pre class="mt-2 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg text-xs overflow-x-auto text-left dir-ltr">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    </div>
</div>
