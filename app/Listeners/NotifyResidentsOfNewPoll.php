<?php

namespace App\Listeners;

use App\Events\PollCreated;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyResidentsOfNewPoll implements ShouldQueue
{
    public $queue = 'notifications';

    public function handle(PollCreated $event): void
    {
        $poll     = $event->poll;
        $tenantId = $poll->tenant_id;

        app(\App\Services\NotificationService::class)->sendToTenantResidents(
            tenantId: $tenantId,
            title: 'استطلاع رأي وتصويت جديد 📊',
            message: "تم طرح تصويت جديد في الإقامة: \"{$poll->title}\". شارك بصوتك الآن!",
            type: 'info',
            url: route('portal.community.index', ['tab' => 'polls']),
            icon: '🗳️',
            extra: [
                'target_screen' => 'polls',
                'target_id'     => $poll->id,
            ]
        );
    }
}
