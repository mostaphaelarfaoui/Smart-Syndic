<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ThrottleAuthentication — Strict rate limiting for auth endpoints (§1.2).
 *
 * 5 attempts per minute per IP to neutralize brute-force and
 * credential stuffing attacks. Uses Redis-backed rate limiter.
 */
class ThrottleAuthentication
{
    public function __construct(
        private readonly RateLimiter $limiter,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $maxAttempts = config('smartsyndic.rate_limits.auth', 5);
        $key = 'auth_throttle:' . $request->ip();

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = $this->limiter->availableIn($key);

            return response()->json([
                'message'     => __('auth.throttle', ['seconds' => $retryAfter]),
                'retry_after' => $retryAfter,
            ], 429);
        }

        $this->limiter->hit($key, 60); // Decay in 60 seconds

        $response = $next($request);

        // Clear on successful login
        if ($response->getStatusCode() === 200 && $request->routeIs('auth.login')) {
            $this->limiter->clear($key);
        }

        return $response;
    }
}
