<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentWebhook;
use App\Models\Restaurant;
use App\Models\User;
use App\Models\Address;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorldpayPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected Restaurant $restaurant;
    protected Address $address;
    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user (customer)
        $this->customer = User::create([
            'name' => 'John Doe',
            'email' => 'john.worldpay@example.com',
            'password' => bcrypt('password123'),
            'role' => 'CUSTOMER',
        ]);

        // Create restaurant
        $this->restaurant = Restaurant::create([
            'name' => 'Tiffin Express',
            'email' => 'restaurant.wp@example.com',
            'phone' => '1234567890',
            'address' => '123 Food Street',
            'city' => 'London',
            'state' => 'Greater London',
            'country' => 'UK',
            'pincode' => 'SW1A 1AA',
            'opening_time' => '08:00:00',
            'closing_time' => '22:00:00',
            'status' => 'ACTIVE',
        ]);

        // Create address
        $this->address = Address::create([
            'customer_id' => $this->customer->id,
            'name' => 'Home',
            'phone' => '1234567890',
            'address_line_1' => '123 Main St',
            'city' => 'London',
            'state' => 'London',
            'country' => 'UK',
            'pincode' => 'SW1A 1AA',
            'is_default' => true,
        ]);

        // Create order
        $this->order = Order::create([
            'order_number' => 'ORD-WP-1001',
            'restaurant_id' => $this->restaurant->id,
            'customer_id' => $this->customer->id,
            'address_id' => $this->address->id,
            'subtotal' => 25.00,
            'tax' => 2.50,
            'delivery_fee' => 3.50,
            'total_amount' => 31.00,
            'payment_status' => 'PENDING_PAYMENT',
            'order_status' => 'PENDING_PAYMENT',
            'delivery_status' => 'PENDING',
        ]);
    }

    public function test_it_validates_required_fields_when_creating_checkout_session()
    {
        $response = $this->postJson('/api/payments/worldpay/create-session', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['orderId', 'amount', 'currency', 'userId']);
    }

    public function test_it_validates_amount_must_be_greater_than_zero()
    {
        $response = $this->postJson('/api/payments/worldpay/create-session', [
            'orderId' => $this->order->id,
            'amount' => 0,
            'currency' => 'GBP',
            'userId' => $this->customer->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_it_successfully_creates_a_worldpay_checkout_session()
    {
        $payload = [
            'orderId' => (string)$this->order->id,
            'amount' => 31.00,
            'currency' => 'GBP',
            'userId' => (string)$this->customer->id,
        ];

        $response = $this->postJson('/api/payments/worldpay/create-session', $payload);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'checkoutUrl',
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertNotEmpty($response->json('checkoutUrl'));
    }

    public function test_it_successfully_creates_a_worldpay_checkout_session_via_v1_route()
    {
        $payload = [
            'orderId' => (string)$this->order->id,
            'amount' => 31.00,
            'currency' => 'GBP',
            'userId' => (string)$this->customer->id,
        ];

        $response = $this->postJson('/api/v1/payments/worldpay/create-session', $payload);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'checkoutUrl',
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertNotEmpty($response->json('checkoutUrl'));
    }

    public function test_it_handles_worldpay_webhook_for_successful_payment()
    {
        $webhookPayload = [
            'event_id' => 'evt_test_12345',
            'transaction_id' => 'tx_worldpay_9999',
            'reference' => 'WP-REF-' . $this->order->order_number,
            'status' => 'PAID',
            'amount' => 31.00,
            'currency' => 'GBP',
            'response_code' => '00',
            'signature' => 'mock-signature',
        ];

        $response = $this->postJson('/api/payments/worldpay/webhook', $webhookPayload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Webhook processed successfully.',
            ]);

        // Verify database state updates
        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'payment_status' => 'PAID',
        ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $this->order->id,
            'status' => 'PAID',
            'worldpay_transaction_id' => 'tx_worldpay_9999',
        ]);

        $this->assertDatabaseHas('payment_webhooks', [
            'event_id' => 'evt_test_12345',
        ]);
    }

    public function test_it_enforces_idempotency_on_duplicate_worldpay_webhooks()
    {
        PaymentWebhook::create([
            'event_id' => 'evt_duplicate_001',
            'payload' => ['sample' => 'data'],
            'processed_at' => now(),
        ]);

        $webhookPayload = [
            'event_id' => 'evt_duplicate_001',
            'transaction_id' => 'tx_dup_999',
            'reference' => 'WP-REF-' . $this->order->order_number,
            'status' => 'PAID',
        ];

        $response = $this->postJson('/api/payments/worldpay/webhook', $webhookPayload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'already_processed' => true,
            ]);
    }

    public function test_it_handles_worldpay_webhook_for_failed_payment()
    {
        $webhookPayload = [
            'event_id' => 'evt_failed_888',
            'transaction_id' => 'tx_failed_888',
            'reference' => 'WP-REF-' . $this->order->order_number,
            'status' => 'FAILED',
            'amount' => 31.00,
            'signature' => 'mock-signature',
        ];

        $response = $this->postJson('/api/payments/worldpay/webhook', $webhookPayload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'payment_status' => 'FAILED',
        ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $this->order->id,
            'status' => 'FAILED',
        ]);
    }
}
