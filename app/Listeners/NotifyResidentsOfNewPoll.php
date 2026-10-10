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

        $residents = \App\Models\User::where('tenant_id', $tenantId)
            ->where('role', 'RESIDENT')
            ->where('status', 'ACTIVE')
            ->whereNotNull('fcm_device_token')
            ->get();

        foreach ($residents as $resident) {
            // TODO: Send FCM push notification
            // "A new community vote has been opened: {$poll->title}. Cast your ballot."
        }
    }
}
