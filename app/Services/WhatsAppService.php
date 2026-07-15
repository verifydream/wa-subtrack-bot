<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $apiUrl;
    private string $token;

    public function __construct()
    {
        $this->apiUrl = config("services.whatsapp.api_url", "https://api.fonnte.com/send");
        $this->token = config("services.whatsapp.token", "");
    }

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
