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
     * Retrieve the Google OAuth2 access token, signed with the Firebase service account private key.
     * Caches the token for 55 minutes to avoid redundant remote requests.
     */
    protected function getAccessToken(): ?string
    {
        $cacheKey = 'firebase_fcm_access_token';
        
        $token = \Illuminate\Support\Facades\Cache::get($cacheKey);
        if ($token) {
            return $token;
        }

        $filePath = config('services.firebase.credentials_path');
        if (!$filePath || !file_exists($filePath)) {
            Log::error("Firebase credentials file not found at: " . ($filePath ?: 'null'));
            return null;
        }

        try {
            $credentials = json_decode(file_get_contents($filePath), true);
            if (!$credentials || !isset($credentials['private_key']) || !isset($credentials['client_email'])) {
                Log::error("Invalid Firebase credentials file format.");
                return null;
            }

            $now = time();
            $payload = [
                'iss' => $credentials['client_email'],
                'sub' => $credentials['client_email'],
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            ];

            $jwt = \Firebase\JWT\JWT::encode($payload, $credentials['private_key'], 'RS256');

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $accessToken = $data['access_token'];
                \Illuminate\Support\Facades\Cache::put($cacheKey, $accessToken, now()->addMinutes(55));
                return $accessToken;
            }

            Log::error("Google OAuth2 token generation failed: " . $response->body());
        } catch (Exception $e) {
            Log::error("Error generating Google OAuth2 Token: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Dispatch notification payload to Firebase FCM using HTTP v1 API.
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

        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            Log::warning('FCM HTTP v1: No access token available. Skipping FCM transmission.');
            return;
        }

        $projectId = config('services.firebase.project_id') ?: 'kinkitchen-51b17';
        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        foreach ($tokens as $token) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ])->post($url, [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $title,
                            'body' => $message,
                        ],
                        // FCM HTTP v1 requires all values in the 'data' array to be strings
                        'data' => array_map('strval', $data),
                    ],
                ]);

                if (!$response->successful()) {
                    Log::error("FCM HTTP v1 transmission failed for token {$token}: " . $response->body());
                }
            } catch (Exception $e) {
                Log::error("FCM Transmission Failure for token {$token}: " . $e->getMessage());
            }
        }
    }
}
