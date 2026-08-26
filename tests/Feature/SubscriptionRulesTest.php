<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Addon;
use App\Models\Restaurant;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionRulesTest extends TestCase
{
    use RefreshDatabase;

    protected User $customerInTrial;
    protected User $customerExpiredTrial;
    protected Restaurant $restaurant;
    protected SubscriptionPlan $weeklyPlan;
    protected SubscriptionPlan $monthlyPlan;
    protected Address $address;

    protected function setUp(): void
    {
        parent::setUp();

        // Customer in 3-day old registration (within 7-day free trial)
        $this->customerInTrial = User::factory()->create([
            'role' => 'CUSTOMER',
            'created_at' => Carbon::now()->subDays(3),
        ]);

        // Customer registered 10 days ago (past 7-day free trial)
        $this->customerExpiredTrial = User::factory()->create([
            'role' => 'CUSTOMER',
            'created_at' => Carbon::now()->subDays(10),
        ]);

        $this->restaurant = Restaurant::create([
            'name' => 'Tiffin Express Test',
            'email' => 'tiffin@example.com',
            'phone' => '1234567890',
            'address' => '123 Test St',
            'city' => 'London',
            'state' => 'London',
            'country' => 'UK',
            'pincode' => 'SW1A 1AA',
            'opening_time' => '08:00:00',
            'closing_time' => '22:00:00',
            'status' => 'ACTIVE',
        ]);

        $this->address = Address::create([
            'customer_id' => $this->customerInTrial->id,
            'name' => 'Home',
            'phone' => '1234567890',
            'address_line_1' => '123 Main St',
            'city' => 'London',
            'state' => 'London',
            'country' => 'UK',
            'pincode' => 'SW1A 1AA',
            'is_default' => true,
        ]);

        Address::create([
            'customer_id' => $this->customerExpiredTrial->id,
            'name' => 'Office',
            'phone' => '9876543210',
            'address_line_1' => '456 Market St',
            'city' => 'London',
            'state' => 'London',
            'country' => 'UK',
            'pincode' => 'SW1A 1AA',
            'is_default' => true,
        ]);

        $this->weeklyPlan = SubscriptionPlan::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Weekly Deluxe Tiffin',
            'description' => '7 meals for 1 week',
            'price' => 35.00,
            'duration_value' => 1,
            'duration_type' => 'WEEK',
            'meal_type' => 'LUNCH',
            'meals_per_day' => 1,
            'total_meals' => 7,
            'delivery_frequency' => 'daily',
            'status' => 'ACTIVE',
        ]);

        $this->monthlyPlan = SubscriptionPlan::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Monthly Grand Tiffin',
            'description' => '30 meals for 1 month',
            'price' => 140.00,
            'duration_value' => 1,
            'duration_type' => 'MONTH',
            'meal_type' => 'LUNCH',
            'meals_per_day' => 1,
            'total_meals' => 30,
            'delivery_frequency' => 'daily',
            'status' => 'ACTIVE',
        ]);
    }

    /** @test */
    public function customer_in_free_trial_can_access_protected_meals()
    {
        $response = $this->actingAs($this->customerInTrial, 'sanctum')
            ->getJson("/api/v1/restaurants/{$this->restaurant->id}/today-meal");

        $response->assertStatus(200);
    }

    /** @test */
    public function customer_past_free_trial_without_subscription_is_blocked_from_protected_meals()
    {
        $response = $this->actingAs($this->customerExpiredTrial, 'sanctum')
            ->getJson("/api/v1/restaurants/{$this->restaurant->id}/today-meal");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error_code' => 'SUBSCRIPTION_REQUIRED',
            ]);
    }

    /** @test */
    public function weekly_subscription_calculates_14_days_max_validity()
    {
        $response = $this->actingAs($this->customerExpiredTrial, 'sanctum')
            ->postJson('/api/v1/subscriptions', [
                'restaurant_id' => $this->restaurant->id,
                'subscription_plan_id' => $this->weeklyPlan->id,
                'start_date' => Carbon::today()->toDateString(),
                'address_id' => Address::where('customer_id', $this->customerExpiredTrial->id)->first()->id,
            ]);

        $response->assertStatus(201);
        $data = $response->json('data');

        $this->assertEquals(7, $data['total_meals']);
        $this->assertEquals(7, $data['remaining_meals']);
        $this->assertEquals(14, $data['max_validity_days']);
        $this->assertEquals(Carbon::today()->addDays(14)->toDateString(), $data['max_validity_date']);
    }

    /** @test */
    public function monthly_subscription_calculates_60_days_max_validity()
    {
        $response = $this->actingAs($this->customerExpiredTrial, 'sanctum')
            ->postJson('/api/v1/subscriptions', [
                'restaurant_id' => $this->restaurant->id,
                'subscription_plan_id' => $this->monthlyPlan->id,
                'start_date' => Carbon::today()->toDateString(),
                'address_id' => Address::where('customer_id', $this->customerExpiredTrial->id)->first()->id,
            ]);

        $response->assertStatus(201);
        $data = $response->json('data');

        $this->assertEquals(30, $data['total_meals']);
        $this->assertEquals(30, $data['remaining_meals']);
        $this->assertEquals(60, $data['max_validity_days']);
        $this->assertEquals(Carbon::today()->addDays(60)->toDateString(), $data['max_validity_date']);
    }

    /** @test */
    public function subscription_past_14_days_validity_auto_expires_pending_meals()
    {
        $sub = Subscription::create([
            'restaurant_id' => $this->restaurant->id,
            'customer_id' => $this->customerExpiredTrial->id,
            'subscription_plan_id' => $this->weeklyPlan->id,
            'total_meals' => 7,
            'used_meals' => 5,
            'remaining_meals' => 2, // 2 meals remaining
            'max_validity_days' => 14,
            'max_validity_date' => Carbon::today()->subDay()->toDateString(), // expired yesterday
            'start_date' => Carbon::today()->subDays(15)->toDateString(),
            'end_date' => Carbon::today()->subDays(8)->toDateString(),
            'price' => 35.00,
            'payment_status' => 'PAID',
            'status' => 'ACTIVE',
        ]);

        $expired = $sub->checkAndAutoExpire();

        $this->assertTrue($expired);
        $this->assertEquals('EXPIRED', $sub->status);
        $this->assertEquals(0, $sub->remaining_meals);
        $this->assertEquals('MAX_VALIDITY_EXCEEDED', $sub->expiration_reason);
    }

    /** @test */
    public function subscription_expiring_in_2_days_returns_reminder_message()
    {
        $sub = Subscription::create([
            'restaurant_id' => $this->restaurant->id,
            'customer_id' => $this->customerExpiredTrial->id,
            'subscription_plan_id' => $this->weeklyPlan->id,
            'total_meals' => 7,
            'used_meals' => 5,
            'remaining_meals' => 2,
            'max_validity_days' => 14,
            'max_validity_date' => Carbon::today()->addDays(2)->toDateString(),
            'start_date' => Carbon::today()->subDays(12)->toDateString(),
            'end_date' => Carbon::today()->subDays(5)->toDateString(),
            'price' => 35.00,
            'payment_status' => 'PAID',
            'status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($this->customerExpiredTrial, 'sanctum')
            ->getJson("/api/v1/subscriptions/{$sub->id}");

        $response->assertStatus(200);
        $this->assertEquals('Your plan will expire in 2 days.', $response->json('data.expiry_reminder_message'));
    }

    /** @test */
    public function order_with_subscription_meal_and_addons_requires_separate_payment_for_addons()
    {
        $sub = Subscription::create([
            'restaurant_id' => $this->restaurant->id,
            'customer_id' => $this->customerExpiredTrial->id,
            'subscription_plan_id' => $this->weeklyPlan->id,
            'total_meals' => 7,
            'used_meals' => 2,
            'remaining_meals' => 5,
            'max_validity_days' => 14,
            'max_validity_date' => Carbon::today()->addDays(10)->toDateString(),
            'start_date' => Carbon::today()->subDays(4)->toDateString(),
            'end_date' => Carbon::today()->addDays(3)->toDateString(),
            'price' => 35.00,
            'payment_status' => 'PAID',
            'status' => 'ACTIVE',
        ]);

        $addon = Addon::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Extra Gulab Jamun',
            'price' => 2.50,
            'availability' => true,
            'status' => 'ACTIVE',
        ]);

        $address = Address::where('customer_id', $this->customerExpiredTrial->id)->first();

        $response = $this->actingAs($this->customerExpiredTrial, 'sanctum')
            ->postJson('/api/v1/orders', [
                'restaurant_id' => $this->restaurant->id,
                'address_id' => $address->id,
                'subscription_id' => $sub->id,
                'include_subscription_meal' => true,
                'items' => [
                    [
                        'addon_id' => $addon->id,
                        'quantity' => 2,
                    ]
                ]
            ]);

        $response->assertStatus(201);
        $orderData = $response->json('data');

        // Subtotal = 2 * 2.50 = 5.00
        $this->assertEquals(5.00, $orderData['subtotal']);
        $this->assertEquals('PENDING_PAYMENT', $orderData['payment_status']);
        $this->assertEquals('PENDING_PAYMENT', $orderData['order_status']);

        // Check subscription remaining meals decremented from 5 to 4
        $sub->refresh();
        $this->assertEquals(4, $sub->remaining_meals);
        $this->assertEquals(3, $sub->used_meals);
    }

    /** @test */
    public function terms_and_conditions_endpoint_returns_accurate_rules()
    {
        $response = $this->getJson('/api/v1/terms-and-conditions');

        $response->assertStatus(200)
            ->assertJsonPath('data.sections.0.id', 'free_trial')
            ->assertJsonPath('data.sections.1.id', 'validity_expiry');
    }
}
