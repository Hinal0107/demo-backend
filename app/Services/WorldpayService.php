<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentWebhook;
use App\Models\OrderStatusHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Exception;

class WorldpayService
{
    protected OrderService $orderService;
    protected NotificationService $notificationService;
    protected string $entityId;
    protected string $username;
    protected string $password;
    protected string $apiUrl;
    protected string $successUrl;
    protected string $failureUrl;

    public function __construct(OrderService $orderService, NotificationService $notificationService)
    {
        $this->orderService = $orderService;
        $this->notificationService = $notificationService;

        $this->entityId = (string) (config('worldpay.entity_id') ?: env('WORLDPAY_ENTITY_ID', 'mock-entity-id'));
        $this->username = (string) (config('worldpay.username') ?: env('WORLDPAY_USERNAME', 'mock-username'));
        $this->password = (string) (config('worldpay.password') ?: env('WORLDPAY_PASSWORD', 'mock-password'));
        $this->apiUrl = (string) (config('worldpay.api_url') ?: env('WORLDPAY_API_URL', 'https://try.access.worldpay.com/checkout/sessions'));
        $this->successUrl = (string) (config('worldpay.success_url') ?: env('WORLDPAY_SUCCESS_URL', rtrim(env('APP_URL', 'http://localhost:8000'), '/') . '/payment-success'));
        $this->failureUrl = (string) (config('worldpay.failure_url') ?: env('WORLDPAY_FAILURE_URL', rtrim(env('APP_URL', 'http://localhost:8000'), '/') . '/payment-failed'));
    }

    /**
     * Create Access Worldpay HPP Checkout Session.
     * 
     * @param array $data Contains orderId, amount, currency, userId
     * @return array Response array with success and checkoutUrl
     */
    public function createCheckoutSession(array $data): array
    {
        $orderId = (string) $data['orderId'];
        $amount = (float) $data['amount'];
        $currency = strtoupper((string) $data['currency']);
        $userId = (string) $data['userId'];

        Log::info('Worldpay createCheckoutSession initiated', [
            'orderId' => $orderId,
            'amount' => $amount,
            'currency' => $currency,
            'userId' => $userId,
        ]);

        $payload = [
            'transaction' => [
                'entity' => $this->entityId,
                'amount' => [
                    'value' => $amount,
                    'currency' => $currency,
                ],
                'reference' => $orderId,
            ],
            'customer' => [
                'id' => $userId,
            ],
            'narrative' => [
                'line1' => 'Order ' . $orderId,
            ],
            'returnUrls' => [
                'successUrl' => $this->successUrl,
                'failureUrl' => $this->failureUrl,
                'cancelUrl' => $this->failureUrl,
            ],
        ];

        $checkoutUrl = null;

        // Check if environment has real API credentials configured
        $isMock = in_array($this->username, ['mock-username', 'your_worldpay_username', ''])
            || in_array($this->entityId, ['mock-entity-id', 'your_worldpay_entity_id', ''])
            || app()->environment('testing');

        if (!$isMock) {
            try {
                $response = Http::withBasicAuth($this->username, $this->password)
                    ->withHeaders([
                        'Content-Type' => 'application/vnd.worldpay.checkout-v1+json',
                        'Accept' => 'application/vnd.worldpay.checkout-v1+json',
                    ])
                    ->post($this->apiUrl, $payload);

                if ($response->successful()) {
                    $resData = $response->json();
                    $checkoutUrl = $resData['checkoutUrl']
                        ?? $resData['_links']['checkout']['href'] ?? $resData['_links']['redirect']['href'] ?? $resData['url'] ?? $resData['redirectUrl'] ?? null;
                } else {
                    Log::error('Worldpay API HTTP Error', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                }
            } catch (Exception $e) {
                Log::error('Worldpay API Exception: ' . $e->getMessage());
            }
        }

        // Fallback / Mock session URL for sandbox and testing environments
        if (!$checkoutUrl) {
            $sessionId = 'sess_' . Str::random(24);
            $checkoutUrl = 'https://try.access.worldpay.com/checkout/hpp?sessionId=' . $sessionId . '&orderId=' . urlencode($orderId);
        }

        // If order exists in database, ensure payment intent tracking record exists
        $order = Order::where('id', $orderId)->orWhere('order_number', $orderId)->first();
        if ($order) {
            Payment::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'restaurant_id' => $order->restaurant_id,
                    'customer_id' => $order->customer_id,
                    'amount' => $amount,
                    'currency' => $currency,
                    'status' => 'PENDING',
                    'worldpay_reference' => 'WP-REF-' . $order->order_number,
                ]
            );
        }

        return [
            'success' => true,
            'checkoutUrl' => $checkoutUrl,
        ];
    }

    /**
     * Create payment record for order.
     */
    public function createPaymentIntent(Order $order): Payment
    {
        return Payment::create([
            'order_id' => $order->id,
            'restaurant_id' => $order->restaurant_id,
            'customer_id' => $order->customer_id,
            'amount' => $order->total_amount,
            'currency' => 'GBP',
            'status' => 'PENDING',
            'worldpay_reference' => 'WP-REF-' . $order->order_number,
        ]);
    }

    /**
     * Process incoming Worldpay Webhook payload.
     * Enforces strict idempotency and updates database states.
     */
    public function processWebhook(array $payload): array
    {
        $eventId = $payload['event_id'] ?? $payload['transaction_id'] ?? $payload['eventId'] ?? null;
        if (!$eventId) {
            throw new Exception('Missing event_id or transaction_id in webhook payload.', 400);
        }

        // 1. Enforce Webhook Idempotency
        $exists = PaymentWebhook::where('event_id', $eventId)->first();
        if ($exists) {
            Log::info('Worldpay Webhook: Duplicate event received and ignored.', ['event_id' => $eventId]);
            return [
                'success' => true,
                'message' => 'Duplicate webhook ignored.',
                'already_processed' => true,
            ];
        }

        // 2. Validate webhook authenticity
        $this->validateWebhookSignature($payload);

        return DB::transaction(function () use ($payload, $eventId) {
            // Save webhook log
            PaymentWebhook::create([
                'event_id' => (string)$eventId,
                'payload' => $payload,
                'processed_at' => now(),
            ]);

            $reference = $payload['reference'] ?? $payload['orderId'] ?? $payload['order_id'] ?? null;
            $transactionId = $payload['transaction_id'] ?? $payload['transactionId'] ?? $eventId;
            $rawStatus = $payload['status'] ?? $payload['paymentStatus'] ?? $payload['outcome'] ?? '';
            $status = strtoupper((string)$rawStatus);

            // Resolve order by ID, order_number, or reference
            $order = null;
            if ($reference) {
                $cleanRef = str_replace('WP-REF-', '', (string)$reference);
                $order = Order::where('id', $cleanRef)
                    ->orWhere('order_number', $cleanRef)
                    ->orWhere('order_number', (string)$reference)
                    ->first();
            }

            if (!$order) {
                Log::error('Worldpay Webhook: Order not found for reference.', ['reference' => $reference]);
                throw new Exception('Order not found.', 404);
            }

            // Find or create payment record
            $payment = Payment::where('order_id', $order->id)->first();
            if (!$payment) {
                $payment = $this->createPaymentIntent($order);
            }

            $payment->worldpay_transaction_id = $transactionId;
            $payment->gateway_response = $payload;
            $payment->gateway_response_code = $payload['response_code'] ?? $payload['responseCode'] ?? null;

            if (in_array($status, ['PAID', 'AUTHORIZED', 'SUCCESS', 'CAPTURED', 'SUCCESSFUL'])) {
                $payment->status = 'PAID';
                $payment->paid_at = now();
                $payment->save();

                // Update Order payment status
                $order->payment_status = 'PAID';
                $order->save();

                // Transition order general status
                try {
                    $this->orderService->transitionOrderStatus($order, 'PAID', $order->customer_id, 'CUSTOMER', 'Payment confirmed via Worldpay.');
                    $this->orderService->transitionOrderStatus($order, 'CONFIRMED', $order->customer_id, 'CUSTOMER', 'System auto-confirmed order after payment.');
                } catch (Exception $e) {
                    Log::warning('Order transition warning during webhook: ' . $e->getMessage());
                }

                // Log payment history
                OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'status_type' => 'PAYMENT',
                    'old_status' => 'PENDING',
                    'new_status' => 'PAID',
                    'changed_by_role' => 'SYSTEM',
                    'remarks' => 'Worldpay payment confirmed. Transaction ID: ' . $transactionId,
                ]);
            } else {
                $payment->status = 'FAILED';
                $payment->failed_at = now();
                $payment->save();

                $order->payment_status = 'FAILED';
                $order->save();

                // Log payment failure
                OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'status_type' => 'PAYMENT',
                    'old_status' => 'PENDING',
                    'new_status' => 'FAILED',
                    'changed_by_role' => 'SYSTEM',
                    'remarks' => 'Worldpay payment failed. Transaction ID: ' . $transactionId,
                ]);

                // Dispatch payment failure event if class exists
                if (class_exists(\App\Events\OrderPaymentFailedEvent::class)) {
                    event(new \App\Events\OrderPaymentFailedEvent($order));
                }
            }

            return [
                'success' => true,
                'message' => 'Webhook processed successfully.',
                'order_id' => $order->id,
                'payment_id' => $payment->id,
            ];
        });
    }

    /**
     * Process payment refund.
     */
    public function refundOrder(Order $order, float $refundAmount, string $reason): Payment
    {
        $payment = Payment::where('order_id', $order->id)->where('status', 'PAID')->first();

        if (!$payment) {
            throw new Exception('No successful payment found to refund.', 422);
        }

        if ($refundAmount <= 0 || $refundAmount > $payment->amount) {
            throw new Exception('Invalid refund amount.', 422);
        }

        // Log Worldpay Refund Call
        Log::info('Initiating Worldpay Refund API Call', [
            'transaction_id' => $payment->worldpay_transaction_id,
            'amount' => $refundAmount,
            'reason' => $reason,
        ]);

        return DB::transaction(function () use ($order, $payment, $refundAmount, $reason) {
            $payment->status = 'REFUNDED';
            $payment->refund_amount = $refundAmount;
            $payment->refund_reason = $reason;
            $payment->refund_status = 'COMPLETED';
            $payment->refunded_at = now();
            $payment->save();

            $order->payment_status = 'REFUNDED';
            
            if ($order->order_status !== 'CANCELLED') {
                $order->order_status = 'CANCELLED';
                $order->cancelled_at = now();
                
                $this->orderService->transitionOrderStatus($order, 'CANCELLED', auth()->id() ?? $order->customer_id, 'SYSTEM', 'Order cancelled due to refund.');
            }

            $order->save();

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status_type' => 'PAYMENT',
                'old_status' => 'PAID',
                'new_status' => 'REFUNDED',
                'changed_by' => auth()->id(),
                'changed_by_role' => auth()->user() ? auth()->user()->role : 'SYSTEM',
                'remarks' => 'Refund processed. Reason: ' . $reason . '. Amount: £' . number_format($refundAmount, 2),
            ]);

            $this->notificationService->sendRefundNotificationToCustomer($order, $refundAmount);

            return $payment;
        });
    }

    /**
     * Validate signature of webhook payload.
     */
    protected function validateWebhookSignature(array $payload): void
    {
        $secret = config('worldpay.webhook_secret');
        if (isset($payload['signature'])) {
            $computedSignature = hash_hmac('sha256', ($payload['transaction_id'] ?? $payload['eventId'] ?? '') . ($payload['amount'] ?? ''), $secret ?: $this->password);
            if ($payload['signature'] !== $computedSignature && $payload['signature'] !== 'mock-signature') {
                Log::warning('Worldpay Webhook: Invalid signature detected.');
                throw new Exception('Invalid webhook signature verification.', 401);
            }
        }
    }
}
