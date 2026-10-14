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
        $aptNumber = $apartment ? " (شقة {$apartment->number})" : '';

        app(\App\Services\NotificationService::class)->sendToTenantSyndics(
            tenantId: $resident->tenant_id,
            title: 'طلب انضمام ساكن جديد 👤',
            message: "سجل ساكن جديد باسم {$resident->full_name}{$aptNumber} في انتظار موافقتكم واعتماده.",
            type: 'warning',
            url: route('portal.residents.index'),
            icon: '📋',
            extra: [
                'target_screen' => 'pending_residents',
                'target_id'     => $resident->id,
            ]
        );
    }
}
