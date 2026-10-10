<?php

namespace App\Providers;

use App\Models\Apartment;
use App\Models\Building;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Poll;
use App\Models\Suggestion;
use App\Models\Tenant;
use App\Policies\ApartmentPolicy;
use App\Policies\BuildingPolicy;
use App\Policies\ExpensePolicy;
use App\Policies\PaymentPolicy;
use App\Policies\PollPolicy;
use App\Policies\SuggestionPolicy;
use App\Policies\TenantPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Model-to-Policy mapping.
     */
    protected $policies = [
        Tenant::class     => TenantPolicy::class,
        Building::class   => BuildingPolicy::class,
        Apartment::class  => ApartmentPolicy::class,
        Payment::class    => PaymentPolicy::class,
        Expense::class    => ExpensePolicy::class,
        Suggestion::class => SuggestionPolicy::class,
        Poll::class       => PollPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
