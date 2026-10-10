<?php

namespace App\Listeners;

use App\Events\ResidentRegistered;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifySyndicOfNewResident implements ShouldQueue
{
    public $queue = 'notifications';

    public function handle(ResidentRegistered $event): void
    {
        $resident = $event->resident;
        $apartment = $resident->apartments()->first();

        // Find the Syndic admin for this tenant
        $syndic = \App\Models\User::where('tenant_id', $resident->tenant_id)
            ->where('role', 'ADMIN_SYNDIC')
            ->where('status', 'ACTIVE')
            ->first();

        if (! $syndic) {
            return;
        }

        // TODO: Send push notification to Syndic
        // TODO: Send email notification to Syndic
    }
}
