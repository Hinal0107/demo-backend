<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\Notification;
use App\Models\FcmToken;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Exception;

class NotificationService
{
    /**
     * Dispatch general push notification to a specific user.
     */
    public function sendNotification(User $user, string $type, string $title, string $message, ?array $data = []): Notification
    {
        // 1. Create in-app notification record in DB
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);

        // 2. Dispatch FCM Push Notifications
        $tokens = FcmToken::where('user_id', $user->id)
            ->where('status', 'ACTIVE')
            ->pluck('token')
            ->toArray();

        if (!empty($tokens)) {
            $this->sendFCM($tokens, $title, $message, array_merge($data, [
                'type' => $type,
                'title' => $title,
                'message' => $message,
            ]));
        }

        return $notification;
    }

    /**
     * Notify restaurant about a new paid order.
     */
    public function sendNewOrderNotificationToRestaurant(Order $order): void
    {
        $restaurant = $order->restaurant;
        if (!$restaurant) return;

        // Resolve staff users of restaurant
        $staffUsers = User::whereHas('restaurantUsers', function ($query) use ($restaurant) {
            $query->where('restaurant_id', $restaurant->id)->where('status', 'ACTIVE');
        })->get();

        $title = "New Order Placed - #" . $order->order_number;
        $message = "You have a new paid order of £" . number_format($order->total_amount, 2) . ". Please prepare it.";
        $data = [
            'type' => 'new_order',
            'order_id' => (string)$order->id,
            'restaurant_id' => (string)$restaurant->id,
        ];

        foreach ($staffUsers as $staff) {
            $this->sendNotification($staff, 'new_order', $title, $message, $data);
        }
    }

    /**
     * Notify customer about order status updates.
     */
    public function sendOrderStatusNotification(Order $order, string $event): void
    {
        $customer = $order->customer;
        if (!$customer) return;

        $title = "";
        $message = "";

        switch ($event) {
            case 'order_paid':
                $title = "Order Paid Successfully";
                $message = "Your order #{$order->order_number} has been paid. Awaiting restaurant confirmation.";
                break;
            case 'order_confirmed':
                $title = "Order Confirmed";
                $message = "Your order #{$order->order_number} has been confirmed by the restaurant and is being prepared.";
                break;
            case 'order_preparing':
                $title = "Preparing Your Meal";
                $message = "The kitchen has started preparing your order #{$order->order_number}.";
                break;
            case 'order_ready':
                $title = "Order Ready";
                $message = "Your order #{$order->order_number} is ready for pick up / delivery.";
                break;
            case 'delivery_out_for_delivery':
                $title = "Order Out for Delivery";
                $message = "Your tiffin is on the way! Handover OTP is: {$order->delivery_otp}.";
                break;
            case 'delivery_delivered':
                $title = "Order Delivered";
                $message = "Your tiffin has been delivered successfully. Enjoy your meal!";
                break;
            case 'order_cancelled':
                $title = "Order Cancelled";
                $message = "Your order #{$order->order_number} has been cancelled.";
                break;
            default:
                return;
        }

        $data = [
            'type' => 'order_status',
            'order_id' => (string)$order->id,
            'restaurant_id' => (string)$order->restaurant_id,
            'status' => $order->order_status,
            'delivery_status' => $order->delivery_status,
        ];

        $this->sendNotification($customer, 'order_status', $title, $message, $data);
    }

    /**
     * Notify customer about refund confirmation.
     */
    public function sendRefundNotificationToCustomer(Order $order, float $amount): void
    {
        $customer = $order->customer;
        if (!$customer) return;

        $title = "Payment Refunded";
        $message = "A refund of £" . number_format($amount, 2) . " has been issued for your order #{$order->order_number}.";
        $data = [
            'type' => 'refund',
            'order_id' => (string)$order->id,
            'amount' => (string)$amount,
        ];

        $this->sendNotification($customer, 'refund', $title, $message, $data);
    }

    /**
     * Notify customer about subscription events.
     */
    public function sendSubscriptionNotification(Subscription $sub, string $event): void
    {
        $customer = $sub->customer;
        if (!$customer) return;

        $planName = $sub->plan->name;
        $title = "";
        $message = "";

        switch ($event) {
            case 'activated':
                $title = "Subscription Active";
                $message = "Your monthly plan '{$planName}' is now active! Deliveries start on {$sub->start_date->toDateString()}.";
                break;
            case 'paused':
                $title = "Subscription Paused";
                $message = "Your plan '{$planName}' is paused. Daily deliveries are temporarily suspended.";
                break;
            case 'resumed':
                $title = "Subscription Resumed";
                $message = "Your plan '{$planName}' has been resumed. Deliveries will resume as scheduled.";
                break;
            case 'cancelled':
                $title = "Subscription Cancelled";
                $message = "Your plan '{$planName}' has been cancelled.";
                break;
        }

        $data = [
            'type' => 'subscription_status',
            'subscription_id' => (string)$sub->id,
            'status' => $sub->status,
        ];

        $this->sendNotification($customer, 'subscription_status', $title, $message, $data);
    }

    /**
     * Dispatch notification payload to Firebase FCM.
     */
    protected function sendFCM(array $tokens, string $title, string $message, array $data): void
    {
        // Log push details for local developer visibility
        Log::info('Dispatched Firebase FCM Push notification', [
            'tokens_count' => count($tokens),
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);

        $fcmKey = env('FCM_SERVER_KEY');
        if (!$fcmKey) {
            return; // Sandbox logging is enough
        }

        try {
            // FCM Legacy or HTTP V1 Dispatch
            Http::withHeaders([
                'Authorization' => 'key=' . $fcmKey,
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'registration_ids' => $tokens,
                'notification' => [
                    'title' => $title,
                    'body' => $message,
                    'sound' => 'default',
                ],
                'data' => $data,
                'priority' => 'high',
            ]);
        } catch (Exception $e) {
            Log::error('FCM Transmission Failure: ' . $e->getMessage());
        }
    }
}
