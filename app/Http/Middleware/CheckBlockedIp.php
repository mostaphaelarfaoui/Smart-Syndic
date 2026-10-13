<?php

namespace App\Http\Middleware;

use App\Models\BlockedIp;
use App\Models\SecurityIncident;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBlockedIp
{
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        if ($ip && BlockedIp::isBlocked($ip)) {
            // Log security incident if not logged recently
            SecurityIncident::firstOrCreate(
                [
                    'incident_type' => 'BLOCKED_ATTEMPT',
                    'ip_address'    => $ip,
                    'created_at'    => now()->format('Y-m-d H:i:00'),
                ],
                [
                    'severity'     => 'HIGH',
                    'user_agent'   => substr($request->userAgent() ?? '', 0, 500),
                    'details'      => 'محاولة وصول من عنوان IP محظور عبر الجدار الناري للنظام: ' . $request->fullUrl(),
                    'status'       => 'OPEN',
                ]
            );

            return response()->view('errors.blocked', [
                'ip' => $ip,
            ], 403);
        }

        return $next($request);
    }
}
