<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SetLocale — Reads Accept-Language or user preference and sets app locale.
 *
 * Priority order:
 * 1. Explicit Accept-Language header (ar, fr, en)
 * 2. Authenticated user's saved locale preference
 * 3. Application default (ar)
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supportedLocales = config('smartsyndic.locales', ['ar', 'fr', 'en']);
        $default = config('smartsyndic.default_locale', 'ar');

        // 1. Check Accept-Language header
        $headerLocale = $request->header('Accept-Language');
        if ($headerLocale && in_array($headerLocale, $supportedLocales, true)) {
            app()->setLocale($headerLocale);
            return $next($request);
        }

        // 2. Check authenticated user's preference
        $user = $request->user();
        if ($user && in_array($user->locale, $supportedLocales, true)) {
            app()->setLocale($user->locale);
            return $next($request);
        }

        // 3. Fallback to default
        app()->setLocale($default);

        if (app()->getLocale() === 'ar') {
            \Illuminate\Support\Number::useLocale('ar@numbers=latn');
            \Filament\Tables\Table::$defaultNumberLocale = 'ar@numbers=latn';
        }

        return $next($request);
    }
}
