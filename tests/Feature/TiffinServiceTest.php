<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Restaurant;
use App\Models\RestaurantUser;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Address;
use App\Models\Order;
use App\Models\CartItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TiffinServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected User $restaurantManagerA;
    protected User $restaurantManagerB;
    protected Restaurant $restaurantA;
    protected Restaurant $restaurantB;
    protected Address $address;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create Customers & Managers
        $this->customer = User::create([
            'name' => 'Test Customer',
            'email' => 'customer_test@tiffin.com',
            'phone' => '+447999999901',
            'firebase_uid' => 'mock-uid-customer',
            'role' => 'CUSTOMER',
        ]);

        $this->restaurantManagerA = User::create([
            'name' => 'Manager A',
            'email' => 'manager_a@tiffin.com',
            'phone' => '+447999999902',
            'firebase_uid' => 'mock-uid-restaurant',
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
            'is_active' => true,
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
            'is_active' => true,
        ]);

        // Link Managers
        RestaurantUser::create([
            'restaurant_id' => $this->restaurantA->id,
            'user_id' => $this->restaurantManagerA->id,
            'role' => 'manager',
            'is_active' => true,
        ]);

        RestaurantUser::create([
            'restaurant_id' => $this->restaurantB->id,
            'user_id' => $this->restaurantManagerB->id,
            'role' => 'manager',
            'is_active' => true,
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

        \Illuminate\Support\Facades\DB::table('restaurants')->where('id', $this->restaurantA->id)->update(['status' => 'ACTIVE']);
        \Illuminate\Support\Facades\DB::table('restaurants')->where('id', $this->restaurantB->id)->update(['status' => 'ACTIVE']);
    }

    /**
     * Test single-restaurant cart validation rules.
     */
    public function test_cart_enforces_single_restaurant_scoping(): void
    {
        // 1. Create categories and items
        $catA = MenuCategory::create([
            'restaurant_id' => $this->restaurantA->id,
            'name' => 'Lunch A',
            'is_active' => true,
        ]);

        $itemA = MenuItem::create([
            'restaurant_id' => $this->restaurantA->id,
            'category_id' => $catA->id,
            'name' => 'Tiffin A',
            'price' => 10.00,
            'veg_type' => 'VEG',
            'is_active' => true,
        ]);

        $catB = MenuCategory::create([
            'restaurant_id' => $this->restaurantB->id,
            'name' => 'Lunch B',
            'is_active' => true,
        ]);

        $itemB = MenuItem::create([
            'restaurant_id' => $this->restaurantB->id,
            'category_id' => $catB->id,
            'name' => 'Tiffin B',
            'price' => 12.00,
            'veg_type' => 'VEG',
            'is_active' => true,
        ]);

        // 2. Add Item A (Restaurant A) to Cart
        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/cart/items', [
                'restaurant_id' => $this->restaurantA->id,
                'menu_item_id' => $itemA->id,
                'quantity' => 1,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('cart_items', [
            'customer_id' => $this->customer->id,
            'menu_item_id' => $itemA->id,
        ]);

        // 3. Try to add Item B (Restaurant B) to Cart (should fail)
        $responseFail = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/cart/items', [
                'restaurant_id' => $this->restaurantB->id,
                'menu_item_id' => $itemB->id,
                'quantity' => 1,
            ]);

        $responseFail->assertStatus(422);
        $responseFail->assertJsonFragment([
            'success' => false,
        ]);
    }

    /**
     * Test order checkout calculations and snapshotted values.
     */
    public function test_order_checkout_pricing_and_snapshots(): void
    {
        $catA = MenuCategory::create([
            'restaurant_id' => $this->restaurantA->id,
            'name' => 'Lunch',
            'is_active' => true,
        ]);

        $itemA = MenuItem::create([
            'restaurant_id' => $this->restaurantA->id,
            'category_id' => $catA->id,
            'name' => 'Paneer Thali Box',
            'price' => 10.00,
            'veg_type' => 'VEG',
            'is_active' => true,
        ]);

        // Place Order
        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'restaurant_id' => $this->restaurantA->id,
                'address_id' => $this->address->id,
                'items' => [
                    [
                        'menu_item_id' => $itemA->id,
                        'quantity' => 2,
                    ]
                ],
                'notes' => 'Ring bell please',
            ]);

        $response->assertStatus(201);
        
        // Calculations verification:
        // Subtotal = 10 * 2 = 20.00
        // Tax = 20 * 10% = 2.00
        // Delivery fee = 3.50
        // Total = 20 + 2 + 3.50 = 25.50
        $response->assertJsonFragment([
            'subtotal' => 20.00,
            'tax' => 2.00,
            'delivery_fee' => 3.50,
            'total_amount' => 25.50,
        ]);

        // Snapshot checking (order_items table should store snapshots)
        $this->assertDatabaseHas('order_items', [
            'item_name' => 'Paneer Thali Box',
            'unit_price' => 10.00,
            'quantity' => 2,
        ]);
    }

    /**
     * Test multi-restaurant security and scoping isolation policies.
     */
    public function test_multi_restaurant_data_isolation(): void
    {
        // 1. Create order for Restaurant A
        $order = Order::create([
            'order_number' => 'ORD-TEST-12345',
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

        // 2. Try to view order as Restaurant Manager B (should be rejected)
        $responseB = $this->actingAs($this->restaurantManagerB, 'sanctum')
            ->getJson("/api/v1/restaurant/orders/{$order->id}");

        $responseB->assertStatus(403);
        $responseB->assertJsonFragment([
            'success' => false,
            'message' => 'Forbidden. You do not have permissions to access this restaurant\'s data.',
        ]);

        // 3. View order as Restaurant Manager A (should be allowed)
        $responseA = $this->actingAs($this->restaurantManagerA, 'sanctum')
            ->getJson("/api/v1/restaurant/orders/{$order->id}");

        $responseA->assertStatus(200);
        $responseA->assertJsonFragment([
            'success' => true,
            'order_number' => 'ORD-TEST-12345',
        ]);
    }

    /**
     * Test order status transition rules.
     */
    public function test_invalid_order_status_transitions_are_blocked(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-TRANS-1',
            'restaurant_id' => $this->restaurantA->id,
            'customer_id' => $this->customer->id,
            'address_id' => $this->address->id,
            'subtotal' => 10.00,
            'total_amount' => 14.50,
            'payment_status' => 'PENDING_PAYMENT',
            'order_status' => 'PENDING_PAYMENT',
            'delivery_status' => 'PENDING',
        ]);

        // Try to confirm a PENDING_PAYMENT order directly (should fail because it's unpaid)
        $response = $this->actingAs($this->restaurantManagerA, 'sanctum')
            ->postJson("/api/v1/restaurant/orders/{$order->id}/confirm");

        $response->assertStatus(422);
    }

    /**
     * Test dynamic tax calculations based on restaurant's active taxes.
     */
    public function test_dynamic_tax_calculations(): void
    {
        // 1. Create category and item
        $catA = MenuCategory::create([
            'restaurant_id' => $this->restaurantA->id,
            'name' => 'Lunch',
            'is_active' => true,
        ]);

        $itemA = MenuItem::create([
            'restaurant_id' => $this->restaurantA->id,
            'category_id' => $catA->id,
            'name' => 'Tiffin Special',
            'price' => 100.00,
            'veg_type' => 'VEG',
            'is_active' => true,
        ]);

        // 2. Set up taxes (GST 5% and VAT 12.5% = 17.5% total)
        \App\Models\Tax::create([
            'restaurant_id' => $this->restaurantA->id,
            'name' => 'GST',
            'rate' => 5.00,
            'is_active' => true,
        ]);

        \App\Models\Tax::create([
            'restaurant_id' => $this->restaurantA->id,
            'name' => 'VAT',
            'rate' => 12.50,
            'is_active' => true,
        ]);

        // Place order
        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'restaurant_id' => $this->restaurantA->id,
                'address_id' => $this->address->id,
                'items' => [
                    [
                        'menu_item_id' => $itemA->id,
                        'quantity' => 1,
                    ]
                ],
            ]);

        $response->assertStatus(201);
        // Subtotal = 100
        // Tax = 100 * 17.5% = 17.50
        // Delivery fee = 3.50
        // Total = 100 + 17.50 + 3.50 = 121.00
        $response->assertJsonFragment([
            'subtotal' => 100.00,
            'tax' => 17.50,
            'total_amount' => 121.00,
        ]);
    }

    /**
     * Test checkout and order creation with add-ons.
     */
    public function test_addon_checkout_and_snapshots(): void
    {
        // 1. Create addon
        $addon = \App\Models\Addon::create([
            'restaurant_id' => $this->restaurantA->id,
            'name' => 'Extra Butter Roti',
            'price' => 1.50,
            'availability' => true,
            'is_active' => true,
        ]);

        // 2. Place Order with addon
        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'restaurant_id' => $this->restaurantA->id,
                'address_id' => $this->address->id,
                'items' => [
                    [
                        'addon_id' => $addon->id,
                        'quantity' => 2,
                    ]
                ],
            ]);

        $response->assertStatus(201);
        // Subtotal = 1.5 * 2 = 3.00
        // Tax = 3 * 10% = 0.30
        // Delivery = 3.50
        // Total = 3 + 0.3 + 3.5 = 6.80
        $response->assertJsonFragment([
            'subtotal' => 3.00,
            'tax' => 0.30,
            'total_amount' => 6.80,
        ]);

        $this->assertDatabaseHas('order_items', [
            'addon_id' => $addon->id,
            'item_name' => 'Extra Butter Roti',
            'unit_price' => 1.50,
            'quantity' => 2,
        ]);
    }

    /**
     * Test fetching scheduled daily meals from the customer API.
     */
    public function test_customer_daily_meals_retrieval(): void
    {
        // Create scheduled meal for today
        \App\Models\DailyMeal::create([
            'restaurant_id' => $this->restaurantA->id,
            'date' => now()->toDateString(),
            'name' => 'Today Special Thali',
            'price' => 10.00,
            'veg_type' => 'VEG',
            'is_active' => true,
        ]);

        // Query today's meal
        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson("/api/v1/restaurants/{$this->restaurantA->id}/today-meal");

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'name' => 'Today Special Thali',
        ]);
    }

    /**
     * Test email/password registration, login, token update, and logout authentication sync.
     */
    public function test_email_password_auth_fcm_sync_flow(): void
    {
        // 1. Register a new customer via email/password
        $registerData = [
            'name' => 'John Doe Auth Test',
            'email' => 'johndoe_authtest@example.com',
            'phone' => '+447999888777',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => 'customer',
        ];

        $registerResponse = $this->postJson('/api/v1/auth/register', $registerData);
        $registerResponse->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'email' => 'johndoe_authtest@example.com',
            'role' => 'CUSTOMER',
        ]);

        // 2. Login via email/password
        $loginData = [
            'email' => 'johndoe_authtest@example.com',
            'password' => 'secret123',
            'device_type' => 'android',
            'fcm_token' => 'fcm-token-initial',
            'device_id' => 'device-android-123',
        ];

        $loginResponse = $this->postJson('/api/v1/auth/login', $loginData);
        $loginResponse->assertStatus(200);
        
        $token = $loginResponse->json('data.token');
        $this->assertNotEmpty($token);

        // Verify FCM token is registered in database
        $this->assertDatabaseHas('user_devices', [
            'fcm_token' => 'fcm-token-initial',
            'device_id' => 'device-android-123',
            'device_type' => 'android',
            'is_active' => true,
        ]);

        // 3. Update/Refresh FCM Token via dedicated sync API
        $syncResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/device/fcm-token', [
                'device_type' => 'android',
                'device_id' => 'device-android-123',
                'fcm_token' => 'fcm-token-refreshed',
            ]);

        $syncResponse->assertStatus(200);
        
        $this->assertDatabaseHas('user_devices', [
            'fcm_token' => 'fcm-token-refreshed',
            'device_id' => 'device-android-123',
            'is_active' => true,
        ]);

        // 4. Logout and verify token is invalidated/deleted
        $logoutResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout', [
                'fcm_token' => 'fcm-token-refreshed',
            ]);

        $logoutResponse->assertStatus(200);

        // Verify FCM token is deactivated (set to is_active = false)
        $this->assertDatabaseHas('user_devices', [
            'fcm_token' => 'fcm-token-refreshed',
            'is_active' => false,
        ]);
    }
}
