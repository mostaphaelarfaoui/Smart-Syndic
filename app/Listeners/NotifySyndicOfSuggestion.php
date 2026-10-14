<?php

namespace App\Listeners;

use App\Events\SuggestionSubmitted;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifySyndicOfSuggestion implements ShouldQueue
{
    public $queue = 'notifications';

    public function handle(SuggestionSubmitted $event): void
    {
        $suggestion = $event->suggestion;

        app(\App\Services\NotificationService::class)->sendToTenantSyndics(
            tenantId: $suggestion->tenant_id,
            title: 'مقترح أو شكوى جديدة 💡',
            message: "تم تقديم مقترح جديد بعنوان: \"{$suggestion->title}\"",
            type: 'info',
            url: route('portal.community.index', ['tab' => 'suggestions']),
            icon: '💡',
            extra: [
                'target_screen' => 'community',
                'target_id'     => $suggestion->id,
            ]
        );
    }
}
