<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Configure Redis-backed rate limiters for all API tiers (Security §1.2).
     */
    private function configureRateLimiting(): void
    {
        // ─── Standard API: 60-120 req/min per token ───────────────────
        RateLimiter::for('api', function (Request $request) {
            $limit = config('smartsyndic.rate_limits.api', 60);
            return Limit::perMinute($limit)
                ->by($request->user()?->id ?: $request->ip());
        });

        // ─── Invite code verification: 10 req/min per IP ─────────────
        RateLimiter::for('invite-code', function (Request $request) {
            $limit = config('smartsyndic.rate_limits.invite_code', 10);
            return Limit::perMinute($limit)->by($request->ip());
        });

        // ─── Financial mutations: 15 req/min ──────────────────────────
        RateLimiter::for('financial', function (Request $request) {
            $limit = config('smartsyndic.rate_limits.financial', 15);
            return Limit::perMinute($limit)
                ->by($request->user()?->id ?: $request->ip());
        });

        // ─── Authentication: 5 req/min per IP ─────────────────────────
        RateLimiter::for('auth', function (Request $request) {
            $limit = config('smartsyndic.rate_limits.auth', 5);
            return Limit::perMinute($limit)->by($request->ip());
        });
    }
}
