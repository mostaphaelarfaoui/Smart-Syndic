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

        $syndic = \App\Models\User::where('tenant_id', $suggestion->tenant_id)
            ->where('role', 'ADMIN_SYNDIC')
            ->where('status', 'ACTIVE')
            ->first();

        if (! $syndic) {
            return;
        }

        // TODO: Send FCM push notification to Syndic
        // "New private suggestion received: {$suggestion->title}"
    }
}
