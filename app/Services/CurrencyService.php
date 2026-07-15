<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class CurrencyService
{
    private array $fallbackRates = ["USD" => 16200.0, "EUR" => 17500.0];

    public function toIDR(float $amount, string $fromCurrency): float
    {
        if ($fromCurrency === "IDR") return $amount;
        $rate = $this->getRate($fromCurrency);
        return round($amount * $rate, 2);
    }

    private function getRate(string $currency): float
    {
        return Cache::remember("rate_{$currency}", 3600, function () use ($currency) {
            return $this->fetchRate($currency);
        });
    }

    private function fetchRate(string $currency): float
    {
        try {
            $response = Http::timeout(5)->get("https://api.exchangerate-api.com/v4/latest/{$currency}");
            if ($response->successful()) {
                $rates = $response->json("rates", []);
                return $rates["IDR"] ?? $this->fallbackRates[$currency] ?? 16200.0;
            }
        } catch (\Exception $e) { /* fallback */ }
        return $this->fallbackRates[$currency] ?? 16200.0;
    }
}
