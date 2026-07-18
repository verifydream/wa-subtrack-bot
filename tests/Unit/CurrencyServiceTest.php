<?php

namespace Tests\Unit;

use App\Services\CurrencyService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase; // use feature TestCase because it boots Laravel

class CurrencyServiceTest extends TestCase
{
    private CurrencyService $currencyService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->currencyService = new CurrencyService();
        Cache::flush();
    }

    public function test_usd_to_idr_conversion()
    {
        Http::fake([
            'api.exchangerate-api.com/*' => Http::response([
                'rates' => [
                    'IDR' => 15000.0,
                ]
            ], 200)
        ]);

        $result = $this->currencyService->toIDR(10.0, 'USD');
        $this->assertEquals(150000.0, $result);
    }

    public function test_eur_to_idr_conversion()
    {
        Http::fake([
            'api.exchangerate-api.com/*' => Http::response([
                'rates' => [
                    'IDR' => 16000.0,
                ]
            ], 200)
        ]);

        $result = $this->currencyService->toIDR(10.0, 'EUR');
        $this->assertEquals(160000.0, $result);
    }

    public function test_idr_passthrough()
    {
        // No HTTP call should be made
        Http::fake();

        $result = $this->currencyService->toIDR(150000.0, 'IDR');
        $this->assertEquals(150000.0, $result);

        Http::assertNothingSent();
    }

    public function test_api_fallback_behavior()
    {
        Http::fake([
            'api.exchangerate-api.com/*' => Http::response([], 500)
        ]);

        // Fallbacks in code: USD => 16200.0, EUR => 17500.0
        $usdResult = $this->currencyService->toIDR(10.0, 'USD');
        $this->assertEquals(162000.0, $usdResult);

        $eurResult = $this->currencyService->toIDR(10.0, 'EUR');
        $this->assertEquals(175000.0, $eurResult);
    }
}
