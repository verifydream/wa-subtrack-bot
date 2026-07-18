<?php

namespace Tests\Unit;

use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculates_next_billing_date_this_month()
    {
        Carbon::setTestNow('2023-10-10 12:00:00');

        $subscription = new Subscription([
            'billing_day' => 15,
            'billing_cycle' => 'monthly',
        ]);

        $nextDate = $subscription->calculateNextBillingDate();

        $this->assertEquals('2023-10-15', $nextDate->format('Y-m-d'));
    }

    public function test_calculates_next_billing_date_next_month()
    {
        Carbon::setTestNow('2023-10-20 12:00:00');

        $subscription = new Subscription([
            'billing_day' => 15,
            'billing_cycle' => 'monthly',
        ]);

        $nextDate = $subscription->calculateNextBillingDate();

        $this->assertEquals('2023-11-15', $nextDate->format('Y-m-d'));
    }

    public function test_calculates_next_billing_date_yearly()
    {
        Carbon::setTestNow('2023-10-20 12:00:00');

        $subscription = new Subscription([
            'billing_day' => 15,
            'billing_cycle' => 'yearly',
        ]);

        $nextDate = $subscription->calculateNextBillingDate();

        $this->assertEquals('2024-11-15', $nextDate->format('Y-m-d'));
    }

    public function test_has_user_relation()
    {
        $user = User::factory()->create();
        $subscription = Subscription::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $subscription->user);
        $this->assertEquals($user->id, $subscription->user->id);
    }
}
