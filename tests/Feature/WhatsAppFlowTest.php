<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Subscription;
use App\Services\WhatsAppService;
use App\Services\CurrencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery\MockInterface;

class WhatsAppFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(WhatsAppService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendText')->andReturn(true);
        });

        $this->mock(CurrencyService::class, function (MockInterface $mock) {
            $mock->shouldReceive('toIDR')->andReturnUsing(function ($amount, $currency) {
                return $currency === 'IDR' ? $amount : $amount * 15000;
            });
        });
    }

    public function test_end_to_end_subscription_flow()
    {
        $phone = '628999999999';

        // 1. Create sub
        $this->postJson('/api/webhook', [
            'phone' => $phone,
            'message' => 'Netflix 149 rb tanggal 15'
        ])->assertStatus(200);

        $this->assertDatabaseHas('subscriptions', [
            'service_name' => 'Netflix',
            'active' => true
        ]);

        // 2. List sub - should be found by checking WhatsApp mock manually in another test, here we'll just check it doesn't crash
        $this->postJson('/api/webhook', [
            'phone' => $phone,
            'message' => 'list'
        ])->assertStatus(200);

        // 3. Delete sub
        $this->postJson('/api/webhook', [
            'phone' => $phone,
            'message' => 'hapus Netflix'
        ])->assertStatus(200);

        $this->assertDatabaseHas('subscriptions', [
            'service_name' => 'Netflix',
            'active' => false
        ]);

        // 4. List sub again
        $this->postJson('/api/webhook', [
            'phone' => $phone,
            'message' => 'list'
        ])->assertStatus(200);
    }
}
