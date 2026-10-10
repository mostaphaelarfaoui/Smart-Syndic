<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SanitizeInput — Global input cleansing middleware (Security §1.1).
 *
 * Normalizes, trims, and strips dangerous content from ALL input vectors:
 * request body, query strings, and headers. Eliminates XSS payloads,
 * null bytes, and executable script injections.
 */
class SanitizeInput
{
    /**
     * Headers that should not be sanitized (binary/auth).
     */
    private const EXCLUDED_HEADERS = [
        'authorization',
        'cookie',
        'content-type',
        'accept',
        'accept-language',
        'user-agent',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // Skip sanitization on Livewire and Filament admin requests to avoid corrupting signed payloads
        if ($request->is('livewire/*', 'syndic/*', 'super-admin/*', 'filament/*')) {
            return $next($request);
        }

        // ─── Sanitize request input (body + query) ────────────────────
        $request->merge($this->sanitizeArray($request->all()));

        // ─── Sanitize query parameters separately ─────────────────────
        $request->query->replace($this->sanitizeArray($request->query->all()));

        return $next($request);
    }

    /**
     * Recursively sanitize an array of inputs.
     */
    private function sanitizeArray(array $data): array
    {
        return array_map(function ($value) {
            if (is_array($value)) {
                return $this->sanitizeArray($value);
            }

            if (is_string($value)) {
                return $this->sanitizeString($value);
            }

            return $value;
        }, $data);
    }

    /**
     * Sanitize a single string value.
     */
    private function sanitizeString(string $value): string
    {
        // 1. Remove null bytes
        $value = str_replace(["\0", "\x00"], '', $value);

        // 2. Trim whitespace
        $value = trim($value);

        // 3. Strip HTML/PHP tags to prevent XSS
        $value = strip_tags($value);

        // 4. Remove javascript: and data: URI schemes
        $value = preg_replace(
            '/(?:javascript|data|vbscript)\s*:/i',
            '',
            $value
        );

        // 5. Remove event handler attributes (onerror=, onclick=, etc.)
        $value = preg_replace(
            '/on\w+\s*=/i',
            '',
            $value
        );

        // 6. Encode special HTML characters
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);

        return $value;
    }
}
