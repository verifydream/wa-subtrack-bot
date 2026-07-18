<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Subscription;
use App\Services\WhatsAppService;
use App\Services\CurrencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery\MockInterface;

class WhatsAppControllerTest extends TestCase
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

    public function test_webhook_creates_user_and_sends_response()
    {
        $response = $this->postJson('/api/webhook', [
            'phone' => '6281234567890',
            'message' => 'Netflix 149 rb tanggal 15'
        ]);

        $response->assertStatus(200)->assertJson(['status' => 'ok']);

        $this->assertDatabaseHas('users', [
            'phone' => '6281234567890'
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'service_name' => 'Netflix',
            'amount' => 149000,
            'currency' => 'IDR',
            'billing_day' => 15
        ]);
    }

    public function test_webhook_ignores_empty_phone()
    {
        $response = $this->postJson('/api/webhook', [
            'phone' => '',
            'message' => 'Netflix 149 rb tanggal 15'
        ]);

        $response->assertStatus(200)->assertJson(['status' => 'ignored']);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_command_list_shows_subscriptions()
    {
        $user = User::factory()->create(['phone' => '6281234567890']);
        Subscription::factory()->create([
            'user_id' => $user->id,
            'service_name' => 'Netflix',
            'amount' => 149000,
            'currency' => 'IDR',
            'active' => true
        ]);

        $mock = $this->mock(WhatsAppService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendText')
                 ->with('6281234567890', \Mockery::on(function ($msg) {
                     return str_contains($msg, 'Netflix') && str_contains($msg, '149.000');
                 }))->once()->andReturn(true);
        });

        $response = $this->postJson('/api/webhook', [
            'phone' => '6281234567890',
            'message' => 'list'
        ]);

        $response->assertStatus(200);
    }

    public function test_command_total_shows_sum()
    {
        $user = User::factory()->create(['phone' => '6281234567890']);
        Subscription::factory()->create([
            'user_id' => $user->id,
            'amount_idr' => 100000,
            'active' => true
        ]);
        Subscription::factory()->create([
            'user_id' => $user->id,
            'amount_idr' => 50000,
            'active' => true
        ]);

        $mock = $this->mock(WhatsAppService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendText')
                 ->with('6281234567890', \Mockery::on(function ($msg) {
                     return str_contains($msg, '150.000');
                 }))->once()->andReturn(true);
        });

        $response = $this->postJson('/api/webhook', [
            'phone' => '6281234567890',
            'message' => 'total'
        ]);

        $response->assertStatus(200);
    }

    public function test_command_help_shows_instructions()
    {
        $mock = $this->mock(WhatsAppService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendText')
                 ->with('6281234567890', \Mockery::on(function ($msg) {
                     return str_contains($msg, 'Bantuan');
                 }))->once()->andReturn(true);
        });

        $response = $this->postJson('/api/webhook', [
            'phone' => '6281234567890',
            'message' => 'help'
        ]);

        $response->assertStatus(200);
    }

    public function test_command_delete_deactivates_subscription()
    {
        $user = User::factory()->create(['phone' => '6281234567890']);
        $sub = Subscription::factory()->create([
            'user_id' => $user->id,
            'service_name' => 'Netflix',
            'active' => true
        ]);

        $response = $this->postJson('/api/webhook', [
            'phone' => '6281234567890',
            'message' => 'hapus Netflix'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('subscriptions', [
            'id' => $sub->id,
            'active' => false
        ]);
    }

    public function test_unknown_message_sends_help()
    {
        $mock = $this->mock(WhatsAppService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendText')
                 ->with('6281234567890', \Mockery::on(function ($msg) {
                     return str_contains($msg, 'Maaf, saya tidak mengerti');
                 }))->once()->andReturn(true);
        });

        $response = $this->postJson('/api/webhook', [
            'phone' => '6281234567890',
            'message' => 'random gibberish'
        ]);

        $response->assertStatus(200);
    }
}
