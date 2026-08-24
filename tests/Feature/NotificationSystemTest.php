<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Restaurant;
use App\Models\RestaurantUser;
use App\Models\Address;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\FcmToken;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected User $restaurantManagerA;
    protected User $restaurantManagerB;
    protected Restaurant $restaurantA;
    protected Restaurant $restaurantB;
    protected Address $address;
    protected SubscriptionPlan $planA;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create Customers & Managers
        $this->customer = User::create([
            'name' => 'Hinal',
            'email' => 'customer_test@tiffin.com',
            'phone' => '+447999999901',
            'firebase_uid' => 'mock-uid-customer',
            'role' => 'CUSTOMER',
        ]);

        $this->restaurantManagerA = User::create([
            'name' => 'Manager A',
            'email' => 'manager_a@tiffin.com',
            'phone' => '+447999999902',
            'firebase_uid' => 'mock-uid-restaurant-a',
            'role' => 'RESTAURANT',
        ]);

        $this->restaurantManagerB = User::create([
            'name' => 'Manager B',
            'email' => 'manager_b@tiffin.com',
            'phone' => '+447999999903',
            'firebase_uid' => 'mock-uid-restaurant-b',
            'role' => 'RESTAURANT',
        ]);

        // 2. Create Restaurants
        $this->restaurantA = Restaurant::create([
            'name' => 'Restaurant Alpha',
            'email' => 'alpha@tiffin.com',
            'phone' => '+447888888801',
            'address' => '1 Alpha Lane',
            'city' => 'London',
            'state' => 'London',
            'country' => 'UK',
            'pincode' => 'NW1 1AA',
            'opening_time' => '08:00:00',
            'closing_time' => '22:00:00',
            'status' => 'ACTIVE',
        ]);

        $this->restaurantB = Restaurant::create([
            'name' => 'Restaurant Beta',
            'email' => 'beta@tiffin.com',
            'phone' => '+447888888802',
            'address' => '2 Beta Lane',
            'city' => 'London',
            'state' => 'London',
            'country' => 'UK',
            'pincode' => 'NW1 2BB',
            'opening_time' => '08:00:00',
            'closing_time' => '22:00:00',
            'status' => 'ACTIVE',
        ]);

        // Link Managers
        RestaurantUser::create([
            'restaurant_id' => $this->restaurantA->id,
            'user_id' => $this->restaurantManagerA->id,
            'role' => 'manager',
            'status' => 'ACTIVE',
        ]);

        RestaurantUser::create([
            'restaurant_id' => $this->restaurantB->id,
            'user_id' => $this->restaurantManagerB->id,
            'role' => 'manager',
            'status' => 'ACTIVE',
        ]);

        // 3. Create Address
        $this->address = Address::create([
            'customer_id' => $this->customer->id,
            'name' => 'Home',
            'phone' => '+447999999901',
            'address_line_1' => 'Flat 5, Rose Gardens',
            'city' => 'London',
            'state' => 'London',
            'country' => 'UK',
            'pincode' => 'NW1 1AA',
            'is_default' => true,
        ]);

        // 4. Create Plan
        $this->planA = SubscriptionPlan::create([
            'restaurant_id' => $this->restaurantA->id,
            'name' => 'Monthly Lunch Plan',
            'price' => 150.00,
            'duration_value' => 1,
            'duration_type' => 'MONTH',
            'meal_type' => 'VEG',
            'meals_per_day' => 1,
            'total_meals' => 30,
            'delivery_frequency' => 'daily',
            'status' => 'ACTIVE',
        ]);

        // Fake HTTP for FCM requests globally in setup
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'mock-access-token'], 200),
            'fcm.googleapis.com/*' => Http::response(['message_id' => 'mock-msg-123'], 200),
        ]);
    }

    /**
     * Test token registration and unregistration.
     */
    public function test_device_token_registration_and_unregistration(): void
    {
        // 1. Register Token
        $response = $this->withHeader('Authorization', 'Bearer mock-uid-customer')
            ->postJson('/api/v1/devices/register', [
                'fcm_token' => 'FCM_TEST_TOKEN_123',
                'device_type' => 'android',
                'device_id' => 'device_id_abc',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('fcm_tokens', [
            'user_id' => $this->customer->id,
            'token' => 'FCM_TEST_TOKEN_123',
            'device_type' => 'android',
            'device_id' => 'device_id_abc',
            'status' => 'ACTIVE',
        ]);

        // 2. Unregister Token
        $responseUnreg = $this->withHeader('Authorization', 'Bearer mock-uid-customer')
            ->postJson('/api/v1/devices/unregister', [
                'fcm_token' => 'FCM_TEST_TOKEN_123',
            ]);

        $responseUnreg->assertStatus(200);
        $this->assertDatabaseHas('fcm_tokens', [
            'user_id' => $this->customer->id,
            'token' => 'FCM_TEST_TOKEN_123',
            'status' => 'INACTIVE',
        ]);
    }

    /**
     * Test notification endpoints.
     */
    public function test_notifications_apis(): void
    {
        // Create sample notifications
        $n1 = Notification::create([
            'user_id' => $this->customer->id,
            'type' => 'payment_success',
            'title' => 'Paid',
            'message' => 'Order paid',
            'status' => 'SENT',
        ]);

        $n2 = Notification::create([
            'user_id' => $this->customer->id,
            'type' => 'order_confirmed',
            'title' => 'Confirmed',
            'message' => 'Order confirmed',
            'status' => 'SENT',
        ]);

        // Get notifications
        $response = $this->withHeader('Authorization', 'Bearer mock-uid-customer')
            ->getJson('/api/v1/notifications');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');

        // Check unread count
        $responseCount = $this->withHeader('Authorization', 'Bearer mock-uid-customer')
            ->getJson('/api/v1/notifications/unread-count');

        $responseCount->assertStatus(200);
        $responseCount->assertJsonFragment(['unread_count' => 2]);

        // Mark single notification as read
        $responseRead = $this->withHeader('Authorization', 'Bearer mock-uid-customer')
            ->postJson('/api/v1/notifications/read', [
                'notification_id' => $n1->id,
            ]);

        $responseRead->assertStatus(200);
        $this->assertNotNull($n1->fresh()->read_at);

        // Check count again
        $responseCount2 = $this->withHeader('Authorization', 'Bearer mock-uid-customer')
            ->getJson('/api/v1/notifications/unread-count');

        $responseCount2->assertJsonFragment(['unread_count' => 1]);

        // Mark all as read
        $responseReadAll = $this->withHeader('Authorization', 'Bearer mock-uid-customer')
            ->postJson('/api/v1/notifications/read-all');

        $responseReadAll->assertStatus(200);
        $this->assertNotNull($n2->fresh()->read_at);

        $responseCount3 = $this->withHeader('Authorization', 'Bearer mock-uid-customer')
            ->getJson('/api/v1/notifications/unread-count');
        $responseCount3->assertJsonFragment(['unread_count' => 0]);
    }

    /**
     * Test order events dispatch notifications.
     */
    public function test_order_events_dispatch_notifications(): void
    {
        // 1. Create order
        $order = Order::create([
            'order_number' => 'ORD-TEST-999',
            'restaurant_id' => $this->restaurantA->id,
            'customer_id' => $this->customer->id,
            'address_id' => $this->address->id,
            'subtotal' => 10.00,
            'discount' => 0.00,
            'delivery_fee' => 3.50,
            'tax' => 1.00,
            'total_amount' => 14.50,
            'payment_status' => 'PENDING_PAYMENT',
            'order_status' => 'PENDING_PAYMENT',
            'delivery_status' => 'PENDING',
        ]);

        // Dispatch Payment successful event
        event(new \App\Events\OrderPaymentSuccessfulEvent($order));

        // Check if database notification was created for Customer
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->customer->id,
            'type' => 'payment_success',
            'order_id' => $order->id,
        ]);
    }

    /**
     * Test notification duplicate prevention (idempotency key).
     */
    public function test_notification_idempotency_prevention(): void
    {
        // Register token
        FcmToken::create([
            'user_id' => $this->customer->id,
            'token' => 'FCM_CUSTOMER_TOKEN',
            'device_type' => 'android',
            'status' => 'ACTIVE',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-TEST-888',
            'restaurant_id' => $this->restaurantA->id,
            'customer_id' => $this->customer->id,
            'address_id' => $this->address->id,
            'subtotal' => 10.00,
            'discount' => 0.00,
            'delivery_fee' => 3.50,
            'tax' => 1.00,
            'total_amount' => 14.50,
            'payment_status' => 'PAID',
            'order_status' => 'CONFIRMED',
            'delivery_status' => 'PENDING',
        ]);

        // Dispatch OrderConfirmedEvent twice
        event(new \App\Events\OrderConfirmedEvent($order));
        event(new \App\Events\OrderConfirmedEvent($order));

        // There should be only ONE notification in the database with the unique idempotency key
        $count = Notification::where('user_id', $this->customer->id)
            ->where('type', 'order_confirmed')
            ->where('order_id', $order->id)
            ->count();

        $this->assertEquals(1, $count);
    }

    /**
     * Test multi-restaurant security and isolation.
     */
    public function test_multi_restaurant_routing_isolation(): void
    {
        // Register Managers' FCM Tokens
        FcmToken::create([
            'user_id' => $this->restaurantManagerA->id,
            'token' => 'FCM_MANAGER_A_TOKEN',
            'device_type' => 'android',
            'status' => 'ACTIVE',
        ]);

        FcmToken::create([
            'user_id' => $this->restaurantManagerB->id,
            'token' => 'FCM_MANAGER_B_TOKEN',
            'device_type' => 'android',
            'status' => 'ACTIVE',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-ALPHA-123',
            'restaurant_id' => $this->restaurantA->id,
            'customer_id' => $this->customer->id,
            'address_id' => $this->address->id,
            'subtotal' => 20.00,
            'discount' => 0.00,
            'delivery_fee' => 3.50,
            'tax' => 2.00,
            'total_amount' => 25.50,
            'payment_status' => 'PAID',
            'order_status' => 'CONFIRMED',
            'delivery_status' => 'PENDING',
        ]);

        // Dispatch OrderCreatedEvent (triggers restaurant notification)
        event(new \App\Events\OrderCreatedEvent($order));

        // Manager A must have order notification
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->restaurantManagerA->id,
            'type' => 'new_order',
            'order_id' => $order->id,
        ]);

        // Manager B MUST NOT have order notification
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->restaurantManagerB->id,
            'type' => 'new_order',
            'order_id' => $order->id,
        ]);
    }

    /**
     * Test multi-device sending and unregistering handling.
     */
    public function test_multi_device_delivery(): void
    {
        // User has two active devices
        $token1 = FcmToken::create([
            'user_id' => $this->customer->id,
            'token' => 'DEVICE_PHONE_TOKEN',
            'device_type' => 'ios',
            'status' => 'ACTIVE',
        ]);

        $token2 = FcmToken::create([
            'user_id' => $this->customer->id,
            'token' => 'DEVICE_TABLET_TOKEN',
            'device_type' => 'android',
            'status' => 'ACTIVE',
        ]);

        // Fire event
        $order = Order::create([
            'order_number' => 'ORD-ALPHA-777',
            'restaurant_id' => $this->restaurantA->id,
            'customer_id' => $this->customer->id,
            'address_id' => $this->address->id,
            'subtotal' => 20.00,
            'discount' => 0.00,
            'delivery_fee' => 3.50,
            'tax' => 2.00,
            'total_amount' => 25.50,
            'payment_status' => 'PAID',
            'order_status' => 'CONFIRMED',
            'delivery_status' => 'PENDING',
        ]);

        event(new \App\Events\OrderConfirmedEvent($order));

        // Check database notification
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->customer->id,
            'type' => 'order_confirmed',
        ]);

        // Unregister token1 (Phone)
        $this->withHeader('Authorization', 'Bearer mock-uid-customer')
            ->postJson('/api/v1/devices/unregister', [
                'fcm_token' => 'DEVICE_PHONE_TOKEN',
            ]);

        $this->assertEquals('INACTIVE', $token1->fresh()->status);
        $this->assertEquals('ACTIVE', $token2->fresh()->status);
    }
}
