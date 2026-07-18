<?php

namespace App\Services;

class MessageParser
{
    /**
     * Parses a natural language text into a structured subscription array.
     *
     * @param string $text The incoming message text.
     * @return array|null The parsed data (service_name, amount, currency, billing_day, notes) or null if invalid.
     */
    public function parse(string $text): ?array
    {
        $text = trim($text);
        if (empty($text)) {
            return null;
        }

        $serviceName = $this->extractServiceName($text);
        $amount = $this->extractAmount($text);
        $currency = $this->extractCurrency($text);
        $billingDay = $this->extractBillingDay($text);

        if (!$serviceName || is_null($amount) || !$billingDay) {
            return null;
        }

        return [
            'service_name' => $serviceName,
            'amount' => $amount,
            'currency' => $currency,
            'billing_day' => $billingDay,
            'notes' => $text,
        ];
    }

    /**
     * Extracts the service name from the message by removing known keywords and amounts.
     *
     * @param string $text The message text.
     * @return string|null The extracted service name, or null if it cannot be determined.
     */
    private function extractServiceName(string $text): ?string
    {
        $cleaned = $text;
        $cleaned = preg_replace('/tiap\s*tanggal\s*\d{1,2}/i', '', $cleaned);
        $cleaned = preg_replace('/setiap\s*tanggal\s*\d{1,2}/i', '', $cleaned);
        $cleaned = preg_replace('/tanggal\s*\d{1,2}/i', '', $cleaned);
        $cleaned = preg_replace('/tgl\s*\d{1,2}/i', '', $cleaned);
        $cleaned = preg_replace('/\$\s*\d+[.,]?\d*/i', '', $cleaned);
        $cleaned = preg_replace('/\d+[.,]?\d*\s*(\$|dollar|usd|euro|eur|rb|ribu|k|jt|juta|rp)/i', '', $cleaned);
        $cleaned = preg_replace('/rp\.?\s*\d+[.,]?\d*/i', '', $cleaned);
        $cleaned = preg_replace('/\b\d{4,}\b/', '', $cleaned);
        $cleaned = trim($cleaned);

        if (empty($cleaned) || strlen($cleaned) < 2) {
            return null;
        }

        return ucwords(trim($cleaned));
    }

    /**
     * Extracts the monetary amount from the message text.
     *
     * @param string $text The message text.
     * @return float|null The extracted amount, or null if none found.
     */
    private function extractAmount(string $text): ?float
    {
        // USD: "15 dollar" or "$15"
        if (preg_match('/(\d+[.,]?\d*)\s*(dollar|usd)\b/i', $text, $m)) {
            return (float) str_replace(',', '.', $m[1]);
        }
        if (preg_match('/\$\s*(\d+[.,]?\d*)/', $text, $m)) {
            return (float) str_replace(',', '.', $m[1]);
        }

        // EUR: "15 euro"
        if (preg_match('/(\d+[.,]?\d*)\s*(euro|eur)\b/i', $text, $m)) {
            return (float) str_replace(',', '.', $m[1]);
        }

        // IDR with suffix: "149 rb" or "55 ribu" or "15k"
        if (preg_match('/(\d+)\s*(rb|ribu)\b/i', $text, $m)) {
            $num = (float) $m[1];
            // "149 rb" = 149,000 but "149000 rb" = 149,000 (already large)
            return $num < 1000 ? $num * 1000 : $num;
        }
        if (preg_match('/(\d+)\s*k\b/i', $text, $m)) {
            $num = (float) $m[1];
            return $num < 1000 ? $num * 1000 : $num;
        }

        // IDR with jt/juta: "1 jt" or "1 juta" or "1.5jt"
        if (preg_match('/(\d+[.,]?\d*)\s*(jt|juta)\b/i', $text, $m)) {
            $num = (float) str_replace(',', '.', $m[1]);
            return $num * 1000000;
        }

        // RP prefix: "Rp 149000" or "rp149000"
        if (preg_match('/rp\.?\s*(\d+[.,]?\d*)/i', $text, $m)) {
            return (float) str_replace(',', '', $m[1]);
        }

        // Plain large number (4+ digits, assume IDR): "55000" or "149000"
        if (preg_match('/\b(\d{4,})\b/', $text, $m)) {
            return (float) $m[1];
        }

        return null;
    }

    /**
     * Determines the currency based on the text contents.
     *
     * @param string $text The message text.
     * @return string The currency code (USD, EUR, or IDR as default).
     */
    private function extractCurrency(string $text): string
    {
        if (preg_match('/\$\s*\d/', $text)) {
            return 'USD';
        }
        if (preg_match('/\b(dollar|usd)\b/i', $text)) {
            return 'USD';
        }
        if (preg_match('/\b(euro|eur)\b/i', $text)) {
            return 'EUR';
        }
        return 'IDR';
    }

    /**
     * Extracts the billing day (1-31) from the message text.
     *
     * @param string $text The message text.
     * @return int|null The extracted billing day, or null if not found.
     */
    private function extractBillingDay(string $text): ?int
    {
        $patterns = [
            '/tanggal\s*(\d{1,2})/i',
            '/tgl\s*(\d{1,2})/i',
            '/tiap\s*tanggal\s*(\d{1,2})/i',
            '/setiap\s*tanggal\s*(\d{1,2})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                $day = (int) $m[1];
                if ($day >= 1 && $day <= 31) {
                    return $day;
                }
            }
        }

        return null;
    }
}
