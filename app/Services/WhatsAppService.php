<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $apiUrl;
    private string $token;

    /**
     * WhatsAppService constructor.
     * Initializes the API URL and token from configuration.
     */
    public function __construct()
    {
        $this->apiUrl = config("services.whatsapp.api_url", "https://api.fonnte.com/send");
        $this->token = config("services.whatsapp.token", "");
    }

    /**
     * Sends a text message to a specific phone number via the WhatsApp API.
     *
     * @param string $phone The recipient's phone number.
     * @param string $message The text message to send.
     * @return bool True if the message was sent successfully, false otherwise.
     */
    public function sendText(string $phone, string $message): bool
    {
        try {
            $response = Http::withHeaders(["Authorization" => $this->token])
                ->post($this->apiUrl, ["target" => $phone, "message" => $message]);

            if ($response->successful()) return true;

            Log::warning("WhatsApp send failed", [
                "phone" => $phone, "status" => $response->status(), "body" => $response->body(),
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error("WhatsApp send error", ["phone" => $phone, "error" => $e->getMessage()]);
            return false;
        }
    }
}
