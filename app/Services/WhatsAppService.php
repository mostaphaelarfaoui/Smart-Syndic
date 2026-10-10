<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsAppService — WhatsApp Cloud API integration (Meta).
 *
 * All WhatsApp API calls execute server-side only (Security §1.3).
 * No WhatsApp credentials are ever exposed to mobile clients.
 */
class WhatsAppService
{
    private string $apiUrl;
    private string $phoneNumberId;
    private string $accessToken;

    public function __construct()
    {
        $this->apiUrl        = config('smartsyndic.whatsapp.api_url');
        $this->phoneNumberId = config('smartsyndic.whatsapp.phone_number_id', '');
        $this->accessToken   = config('smartsyndic.whatsapp.access_token', '');
    }

    /**
     * Send a text message via WhatsApp.
     */
    public function sendMessage(string $recipientPhone, string $message): bool
    {
        if (empty($this->phoneNumberId) || empty($this->accessToken)) {
            Log::warning('WhatsApp credentials not configured. Skipping message.');
            return false;
        }

        try {
            $response = Http::withToken($this->accessToken)
                ->post("{$this->apiUrl}/{$this->phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to'                => $this->formatPhone($recipientPhone),
                    'type'              => 'text',
                    'text'              => ['body' => $message],
                ]);

            if ($response->successful()) {
                return true;
            }

            Log::error('WhatsApp API error', [
                'status'   => $response->status(),
                'response' => $response->json(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('WhatsApp send failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Send a document (PDF receipt) via WhatsApp.
     */
    public function sendDocument(string $recipientPhone, string $documentUrl, string $filename, string $caption = ''): bool
    {
        if (empty($this->phoneNumberId) || empty($this->accessToken)) {
            return false;
        }

        try {
            $response = Http::withToken($this->accessToken)
                ->post("{$this->apiUrl}/{$this->phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to'                => $this->formatPhone($recipientPhone),
                    'type'              => 'document',
                    'document'          => [
                        'link'     => $documentUrl,
                        'caption'  => $caption,
                        'filename' => $filename,
                    ],
                ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('WhatsApp document send failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Format Moroccan phone number for WhatsApp API (must include country code).
     */
    private function formatPhone(string $phone): string
    {
        // Remove any non-digit characters
        $digits = preg_replace('/\D/', '', $phone);

        // Convert Moroccan local format to international
        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            $digits = '212' . substr($digits, 1);
        }

        // Remove leading 00
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        return $digits;
    }
}
