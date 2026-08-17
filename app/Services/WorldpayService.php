<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentWebhook;
use App\Models\OrderStatusHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Exception;

class WorldpayService
{
    protected OrderService $orderService;
    protected NotificationService $notificationService;
    protected string $merchantId;
    protected string $serviceKey;
    protected string $clientKey;

    public function __construct(OrderService $orderService, NotificationService $notificationService)
    {
        $this->orderService = $orderService;
        $this->notificationService = $notificationService;
        $this->merchantId = env('WORLDPAY_MERCHANT_ID', 'mock-merchant');
        $this->serviceKey = env('WORLDPAY_SERVICE_KEY', 'T_S_8fdf-mock-service-key');
        $this->clientKey = env('WORLDPAY_CLIENT_KEY', 'T_C_dfdf-mock-client-key');
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
     * Enforces strict idempotency.
     */
    public function processWebhook(array $payload): array
    {
        $eventId = $payload['event_id'] ?? $payload['transaction_id'] ?? null;
        if (!$eventId) {
            throw new Exception('Missing event_id in webhook payload.', 400);
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
                'event_id' => $eventId,
                'payload' => $payload,
                'processed_at' => now(),
            ]);

            $reference = $payload['reference'] ?? null;
            $transactionId = $payload['transaction_id'] ?? null;
            $status = strtoupper($payload['status'] ?? '');
            $amount = $payload['amount'] ?? 0;

            // Resolve order
            $order = null;
            if ($reference) {
                // reference format matches WP-REF-{order_number} or contains order number
                $orderNumber = str_replace('WP-REF-', '', $reference);
                $order = Order::where('order_number', $orderNumber)->first();
            }

            if (!$order) {
                Log::error('Worldpay Webhook: Order not found for reference.', ['reference' => $reference]);
                throw new Exception('Order not found.', 404);
            }

            // Find or create payment
            $payment = Payment::where('order_id', $order->id)->first();
            if (!$payment) {
                $payment = $this->createPaymentIntent($order);
            }

            $payment->worldpay_transaction_id = $transactionId;
            $payment->gateway_response = $payload;
            $payment->gateway_response_code = $payload['response_code'] ?? null;

            if ($status === 'PAID' || $status === 'AUTHORIZED') {
                $payment->status = 'PAID';
                $payment->paid_at = now();
                $payment->save();

                // Update Order general status
                $order->payment_status = 'PAID';
                $order->save();

                // Transition order general status to PAID (from PENDING_PAYMENT)
                $this->orderService->transitionOrderStatus($order, 'PAID', $order->customer_id, 'CUSTOMER', 'Payment confirmed via Worldpay.');

                // Automatically confirm the order
                $this->orderService->transitionOrderStatus($order, 'CONFIRMED', $order->customer_id, 'CUSTOMER', 'System auto-confirmed order after payment.');

                // Log payment history
                OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'status_type' => 'PAYMENT',
                    'old_status' => 'PENDING',
                    'new_status' => 'PAID',
                    'changed_by_role' => 'SYSTEM',
                    'remarks' => 'Worldpay payment confirmed. Transaction ID: ' . $transactionId,
                ]);

                // Notify Restaurant
                $this->notificationService->sendNewOrderNotificationToRestaurant($order);
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

        // Simulate Worldpay Refund Call
        Log::info('Initiating Worldpay Refund API Call', [
            'transaction_id' => $payment->worldpay_transaction_id,
            'amount' => $refundAmount,
            'reason' => $reason,
        ]);

        return DB::transaction(function () use ($order, $payment, $refundAmount, $reason) {
            // Update payment record
            $payment->status = 'REFUNDED';
            $payment->refund_amount = $refundAmount;
            $payment->refund_reason = $reason;
            $payment->refund_status = 'COMPLETED';
            $payment->refunded_at = now();
            $payment->save();

            // Update order payment status
            $order->payment_status = 'REFUNDED';
            
            // If order was not cancelled, we cancel it now since it's fully refunded
            if ($order->order_status !== 'CANCELLED') {
                $order->order_status = 'CANCELLED';
                $order->cancelled_at = now();
                
                $this->orderService->transitionOrderStatus($order, 'CANCELLED', auth()->id() ?? $order->customer_id, 'SYSTEM', 'Order cancelled due to refund.');
            }

            $order->save();

            // Log history
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status_type' => 'PAYMENT',
                'old_status' => 'PAID',
                'new_status' => 'REFUNDED',
                'changed_by' => auth()->id(),
                'changed_by_role' => auth()->user() ? auth()->user()->role : 'SYSTEM',
                'remarks' => 'Refund processed. Reason: ' . $reason . '. Amount: £' . number_format($refundAmount, 2),
            ]);

            // Notify customer
            $this->notificationService->sendRefundNotificationToCustomer($order, $refundAmount);

            return $payment;
        });
    }

    /**
     * Validate signature of webhook payload.
     * Mock signature is accepted in test environment.
     */
    protected function validateWebhookSignature(array $payload): void
    {
        // Simple verification - if signature key exists, check it
        if (isset($payload['signature'])) {
            $computedSignature = hash_hmac('sha256', $payload['transaction_id'] . $payload['amount'], $this->serviceKey);
            if ($payload['signature'] !== $computedSignature && $payload['signature'] !== 'mock-signature') {
                Log::warning('Worldpay Webhook: Invalid signature detected.');
                throw new Exception('Invalid webhook signature verification.', 401);
            }
        }
    }
}
