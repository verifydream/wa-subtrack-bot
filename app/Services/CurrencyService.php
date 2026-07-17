<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class CurrencyService
{
    private array $fallbackRates = ["USD" => 16200.0, "EUR" => 17500.0];

    /**
     * Converts a given amount from a specified currency to IDR.
     *
     * @param float $amount The amount to convert.
     * @param string $fromCurrency The currency code to convert from (e.g., USD, EUR).
     * @return float The converted amount in IDR.
     */
    public function toIDR(float $amount, string $fromCurrency): float
    {
        if ($fromCurrency === "IDR") return $amount;
        $rate = $this->getRate($fromCurrency);
        return round($amount * $rate, 2);
    }

    /**
     * Retrieves the conversion rate for a given currency, using a cached value if available.
     *
     * @param string $currency The currency code (e.g., USD, EUR).
     * @return float The conversion rate to IDR.
     */
    private function getRate(string $currency): float
    {
        return Cache::remember("rate_{$currency}", 3600, function () use ($currency) {
            return $this->fetchRate($currency);
        });
    }

    /**
     * Fetches the latest conversion rate for a given currency from the external API.
     * Falls back to a predefined rate if the request fails.
     *
     * @param string $currency The currency code to fetch the rate for.
     * @return float The conversion rate to IDR.
     */
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
