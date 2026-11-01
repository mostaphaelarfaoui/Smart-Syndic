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
        $author = $suggestion->user;
        $apartment = $suggestion->apartment ?: $author?->apartments()->first();

        app(\App\Services\NotificationService::class)->sendToTenantSyndics(
            tenantId: $suggestion->tenant_id,
            title: 'مقترح أو شكوى جديدة 💡',
            message: "تم تقديم مقترح جديد بعنوان: \"{$suggestion->title}\"",
            type: 'info',
            url: route('portal.community.index', ['tab' => 'suggestions']),
            icon: '💡',
            extra: [
                'target_screen'  => 'community',
                'target_id'      => $suggestion->id,
                'actor_name'     => $author?->full_name ?? 'أحد السكان',
                'actor_role'     => 'ساكن مقيم',
                'unit_number'    => $apartment?->clean_unit_number,
                'building_name'  => $apartment?->building?->name,
                'recipient_label'=> 'إدارة السانديك',
            ]
        );
    }
}
