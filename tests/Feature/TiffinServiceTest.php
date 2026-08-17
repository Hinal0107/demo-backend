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
            'status' => 'ACTIVE',
        ]);

        $itemA = MenuItem::create([
            'restaurant_id' => $this->restaurantA->id,
            'category_id' => $catA->id,
            'name' => 'Tiffin A',
            'price' => 10.00,
            'veg_type' => 'VEG',
            'status' => 'ACTIVE',
        ]);

        $catB = MenuCategory::create([
            'restaurant_id' => $this->restaurantB->id,
            'name' => 'Lunch B',
            'status' => 'ACTIVE',
        ]);

        $itemB = MenuItem::create([
            'restaurant_id' => $this->restaurantB->id,
            'category_id' => $catB->id,
            'name' => 'Tiffin B',
            'price' => 12.00,
            'veg_type' => 'VEG',
            'status' => 'ACTIVE',
        ]);

        // 2. Add Item A (Restaurant A) to Cart
        $response = $this->withHeader('Authorization', 'Bearer mock-uid-customer')
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
        $responseFail = $this->withHeader('Authorization', 'Bearer mock-uid-customer')
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
            'status' => 'ACTIVE',
        ]);

        $itemA = MenuItem::create([
            'restaurant_id' => $this->restaurantA->id,
            'category_id' => $catA->id,
            'name' => 'Paneer Thali Box',
            'price' => 10.00,
            'veg_type' => 'VEG',
            'status' => 'ACTIVE',
        ]);

        // Place Order
        $response = $this->withHeader('Authorization', 'Bearer mock-uid-customer')
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
        $responseB = $this->withHeader('Authorization', 'Bearer mock-uid-restaurant-b')
            ->getJson("/api/v1/restaurant/orders/{$order->id}");

        $responseB->assertStatus(403);
        $responseB->assertJsonFragment([
            'success' => false,
            'message' => 'Forbidden. You do not have permissions to access this restaurant\'s data.',
        ]);

        // 3. View order as Restaurant Manager A (should be allowed)
        $responseA = $this->withHeader('Authorization', 'Bearer mock-uid-restaurant')
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
        $response = $this->withHeader('Authorization', 'Bearer mock-uid-restaurant')
            ->postJson("/api/v1/restaurant/orders/{$order->id}/confirm");

        $response->assertStatus(422);
    }
}
