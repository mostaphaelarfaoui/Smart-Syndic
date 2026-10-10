<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Event-to-listener mapping.
     */
    protected $listen = [
        \App\Events\PaymentRecorded::class => [
            \App\Listeners\GeneratePaymentReceipt::class,
            \App\Listeners\SendPaymentNotification::class,
        ],
        \App\Events\ResidentRegistered::class => [
            \App\Listeners\NotifySyndicOfNewResident::class,
        ],
        \App\Events\ResidentApproved::class => [
            \App\Listeners\SendResidentWelcomeNotification::class,
        ],
        \App\Events\PollCreated::class => [
            \App\Listeners\NotifyResidentsOfNewPoll::class,
        ],
        \App\Events\SuggestionSubmitted::class => [
            \App\Listeners\NotifySyndicOfSuggestion::class,
        ],
    ];

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
