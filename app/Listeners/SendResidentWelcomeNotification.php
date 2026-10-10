<?php

namespace App\Listeners;

use App\Events\ResidentApproved;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendResidentWelcomeNotification implements ShouldQueue
{
    public $queue = 'notifications';

    public function __construct(
        private readonly WhatsAppService $whatsApp,
    ) {}

    public function handle(ResidentApproved $event): void
    {
        $resident = $event->resident;
        $tenantName = $resident->tenant->name ?? 'Smart Syndic';

        $message = match ($resident->locale) {
            'ar' => "مرحباً {$resident->full_name}! تم تفعيل حسابك في {$tenantName}. أهلاً وسهلاً!",
            'fr' => "Bienvenue {$resident->full_name}! Votre compte pour {$tenantName} a été activé.",
            default => "Welcome {$resident->full_name}! Your account for {$tenantName} has been activated.",
        };

        $this->whatsApp->sendMessage($resident->phone, $message);

        // TODO: Send FCM push notification
    }
}
