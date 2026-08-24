<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\Notification;
use App\Models\FcmToken;
use App\Models\Restaurant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Exception;

class NotificationService
{
    /**
     * Dispatch general push notification to a specific user.
     * Integrates strict database idempotency and FCM error token cleanup.
     */
    public function sendNotification(User $user, string $type, string $title, string $message, ?array $data = [], ?string $idempotencyKey = null): ?Notification
    {
        // 1. Idempotency protection check
        if ($idempotencyKey) {
            $existing = Notification::where('notification_idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                Log::info('NotificationService: Duplicate notification prevented.', ['idempotency_key' => $idempotencyKey]);
                return $existing;
            }
        }

        // 2. Resolve database fields from metadata if applicable
        $restaurantId = $data['restaurant_id'] ?? null;
        $orderId = $data['order_id'] ?? null;
        $subscriptionId = $data['subscription_id'] ?? null;

        // 3. Create in-app notification record in DB
        $notification = Notification::create([
            'user_id' => $user->id,
            'restaurant_id' => $restaurantId,
            'order_id' => $orderId,
            'subscription_id' => $subscriptionId,
            'notification_idempotency_key' => $idempotencyKey,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'status' => 'SENT',
            'data' => $data,
        ]);

        // 4. Dispatch FCM Push Notifications
        $tokens = FcmToken::where('user_id', $user->id)
            ->where('status', 'ACTIVE')
            ->pluck('token')
            ->toArray();

        if (!empty($tokens)) {
            $this->sendFCM($tokens, $title, $message, array_merge($data ?: [], [
                'type' => $type,
                'title' => $title,
                'message' => $message,
            ]), $notification);
        }

        return $notification;
    }

    /**
     * Send notification alias methods for clean service access.
     */
    public function sendToUser(User $user, string $type, string $title, string $message, ?array $data = [], ?string $idempotencyKey = null): ?Notification
    {
        return $this->sendNotification($user, $type, $title, $message, $data, $idempotencyKey);
    }

    public function sendToCustomer(User $customer, string $type, string $title, string $message, ?array $data = [], ?string $idempotencyKey = null): ?Notification
    {
        return $this->sendNotification($customer, $type, $title, $message, $data, $idempotencyKey);
    }

    /**
     * Notify all active staff members of a restaurant.
     */
    public function sendToRestaurant(Restaurant $restaurant, string $type, string $title, string $message, ?array $data = [], ?string $idempotencyKey = null): void
    {
        $staffUsers = User::whereHas('restaurantUsers', function ($query) use ($restaurant) {
            $query->where('restaurant_id', $restaurant->id)->where('status', 'ACTIVE');
        })->get();

        foreach ($staffUsers as $staff) {
            // Append restaurant_id to data for scoping
            $appendedData = array_merge($data ?: [], ['restaurant_id' => $restaurant->id]);
            // Generate user-specific idempotency key to allow broadcasting to multiple staffs
            $userSpecificKey = $idempotencyKey ? "{$idempotencyKey}_user_{$staff->id}" : null;
            $this->sendNotification($staff, $type, $title, $message, $appendedData, $userSpecificKey);
        }
    }

    /**
     * Send notification to a collection of users.
     */
    public function sendToMultipleUsers($users, string $type, string $title, string $message, ?array $data = [], ?string $idempotencyKey = null): void
    {
        foreach ($users as $user) {
            $userSpecificKey = $idempotencyKey ? "{$idempotencyKey}_user_{$user->id}" : null;
            $this->sendNotification($user, $type, $title, $message, $data, $userSpecificKey);
        }
    }

    /*
     |--------------------------------------------------------------------------
     | Event Specific Helper Notifications
     |--------------------------------------------------------------------------
     */

    public function notifyRestaurantNewOrder(Order $order): void
    {
        $restaurant = $order->restaurant;
        if (!$restaurant) return;

        $customerName = $order->customer->name ?? 'Customer';
        $title = 'New Order Received';
        $message = "You received a new order #{$order->order_number} from {$customerName}.";
        $data = [
            'type' => 'new_order',
            'order_id' => (string)$order->id,
            'order_number' => $order->order_number,
        ];
        $idempotencyKey = "order_{$order->id}_new_order";

        $this->sendToRestaurant($restaurant, 'new_order', $title, $message, $data, $idempotencyKey);
    }

    public function notifyCustomerPaymentSuccess(Order $order): void
    {
        $customer = $order->customer;
        if (!$customer) return;

        $title = 'Payment Successful';
        $message = "Your payment for order #{$order->order_number} was successful.";
        $data = [
            'type' => 'payment_success',
            'order_id' => (string)$order->id,
            'order_number' => $order->order_number,
        ];
        $idempotencyKey = "order_{$order->id}_payment_success";

        $this->sendToCustomer($customer, 'payment_success', $title, $message, $data, $idempotencyKey);
    }

    public function notifyCustomerPaymentFailed(Order $order): void
    {
        $customer = $order->customer;
        if (!$customer) return;

        $title = 'Payment Failed';
        $message = "Your payment for order #{$order->order_number} failed. Please try again.";
        $data = [
            'type' => 'payment_failed',
            'order_id' => (string)$order->id,
        ];
        $idempotencyKey = "order_{$order->id}_payment_failed";

        $this->sendToCustomer($customer, 'payment_failed', $title, $message, $data, $idempotencyKey);
    }

    public function notifyCustomerPaymentPending(Order $order): void
    {
        $customer = $order->customer;
        if (!$customer) return;

        $title = 'Payment Pending';
        $message = "We are confirming your payment for order #{$order->order_number}.";
        $data = [
            'type' => 'payment_pending',
            'order_id' => (string)$order->id,
        ];
        $idempotencyKey = "order_{$order->id}_payment_pending";

        $this->sendToCustomer($customer, 'payment_pending', $title, $message, $data, $idempotencyKey);
    }

    public function notifyCustomerOrderConfirmed(Order $order): void
    {
        $customer = $order->customer;
        if (!$customer) return;

        $title = 'Order Confirmed';
        $message = "Your order #{$order->order_number} has been confirmed by the restaurant.";
        $data = [
            'type' => 'order_confirmed',
            'order_id' => (string)$order->id,
            'status' => 'confirmed',
        ];
        $idempotencyKey = "order_{$order->id}_confirmed";

        $this->sendToCustomer($customer, 'order_confirmed', $title, $message, $data, $idempotencyKey);
    }

    public function notifyCustomerOrderRejected(Order $order, string $reason): void
    {
        $customer = $order->customer;
        if (!$customer) return;

        $title = 'Order Rejected';
        $message = "Unfortunately, your order #{$order->order_number} was rejected by the restaurant.";
        if ($reason) {
            $message .= " Reason: {$reason}";
        }
        $data = [
            'type' => 'order_rejected',
            'order_id' => (string)$order->id,
            'reason' => $reason,
        ];
        $idempotencyKey = "order_{$order->id}_rejected";

        $this->sendToCustomer($customer, 'order_rejected', $title, $message, $data, $idempotencyKey);
    }

    public function notifyCustomerOrderPreparing(Order $order): void
    {
        $customer = $order->customer;
        if (!$customer) return;

        $title = 'Your Order Is Being Prepared';
        $message = "Your order #{$order->order_number} is now being prepared.";
        $data = [
            'type' => 'order_preparing',
            'order_id' => (string)$order->id,
        ];
        $idempotencyKey = "order_{$order->id}_preparing";

        $this->sendToCustomer($customer, 'order_preparing', $title, $message, $data, $idempotencyKey);
    }

    public function notifyCustomerOrderReady(Order $order): void
    {
        $customer = $order->customer;
        if (!$customer) return;

        $title = 'Your Order Is Ready';
        $message = "Your order #{$order->order_number} is ready.";
        $data = [
            'type' => 'order_ready',
            'order_id' => (string)$order->id,
        ];
        $idempotencyKey = "order_{$order->id}_ready";

        $this->sendToCustomer($customer, 'order_ready', $title, $message, $data, $idempotencyKey);
    }

    public function notifyCustomerOrderOutForDelivery(Order $order): void
    {
        $customer = $order->customer;
        if (!$customer) return;

        $title = 'Order Out for Delivery';
        $message = "Your order #{$order->order_number} is out for delivery.";
        $data = [
            'type' => 'order_out_for_delivery',
            'order_id' => (string)$order->id,
            'status' => 'out_for_delivery',
        ];
        $idempotencyKey = "order_{$order->id}_out_for_delivery";

        $this->sendToCustomer($customer, 'order_out_for_delivery', $title, $message, $data, $idempotencyKey);
    }

    public function notifyCustomerOrderDelivered(Order $order): void
    {
        $customer = $order->customer;
        if (!$customer) return;

        $title = 'Order Delivered';
        $message = "Your order #{$order->order_number} has been delivered successfully.";
        $data = [
            'type' => 'order_delivered',
            'order_id' => (string)$order->id,
            'status' => 'delivered',
        ];
        $idempotencyKey = "order_{$order->id}_delivered";

        $this->sendToCustomer($customer, 'order_delivered', $title, $message, $data, $idempotencyKey);
    }

    public function notifyOrderCancelled(Order $order, string $reason): void
    {
        $customer = $order->customer;
        if ($customer) {
            $title = 'Order Cancelled';
            $message = "Your order #{$order->order_number} has been cancelled.";
            $data = [
                'type' => 'order_cancelled',
                'order_id' => (string)$order->id,
                'reason' => $reason,
            ];
            $idempotencyKey = "order_{$order->id}_cancelled_cust";
            $this->sendToCustomer($customer, 'order_cancelled', $title, $message, $data, $idempotencyKey);
        }

        // Also notify restaurant
        $restaurant = $order->restaurant;
        if ($restaurant) {
            $title = 'Order Cancelled';
            $message = "Customer cancelled order #{$order->order_number}.";
            $data = [
                'type' => 'order_cancelled',
                'order_id' => (string)$order->id,
                'reason' => $reason,
            ];
            $idempotencyKey = "order_{$order->id}_cancelled_rest";
            $this->sendToRestaurant($restaurant, 'order_cancelled', $title, $message, $data, $idempotencyKey);
        }
    }

    public function notifyCustomerSubscriptionPurchased(Subscription $sub): void
    {
        $customer = $sub->customer;
        if (!$customer) return;

        $planName = $sub->plan->name ?? 'Lunch Plan';
        $title = 'Subscription Purchased';
        $message = "Your subscription to plan '{$planName}' has been purchased successfully.";
        $data = [
            'type' => 'subscription_purchased',
            'subscription_id' => (string)$sub->id,
        ];
        $idempotencyKey = "subscription_{$sub->id}_purchased";

        $this->sendToCustomer($customer, 'subscription_purchased', $title, $message, $data, $idempotencyKey);
    }

    public function notifyCustomerSubscriptionActivated(Subscription $sub): void
    {
        $customer = $sub->customer;
        if (!$customer) return;

        $planName = $sub->plan->name ?? 'Lunch Plan';
        $title = 'Subscription Activated';
        $message = "Your {$planName} subscription has been activated successfully.";
        $data = [
            'type' => 'subscription_activated',
            'subscription_id' => (string)$sub->id,
        ];
        $idempotencyKey = "subscription_{$sub->id}_activated";

        $this->sendToCustomer($customer, 'subscription_activated', $title, $message, $data, $idempotencyKey);
    }

    public function notifyRestaurantNewSubscription(Subscription $sub): void
    {
        $restaurant = $sub->restaurant;
        if (!$restaurant) return;

        $planName = $sub->plan->name ?? 'Lunch Plan';
        $title = 'New Subscription';
        $message = "A customer has subscribed to your {$planName}.";
        $data = [
            'type' => 'new_subscription',
            'subscription_id' => (string)$sub->id,
        ];
        $idempotencyKey = "subscription_{$sub->id}_new_sub";

        $this->sendToRestaurant($restaurant, 'new_subscription', $title, $message, $data, $idempotencyKey);
    }

    public function notifyCustomerSubscriptionCancelled(Subscription $sub): void
    {
        $customer = $sub->customer;
        if (!$customer) return;

        $title = 'Subscription Cancelled';
        $message = "Your subscription has been cancelled successfully.";
        $data = [
            'type' => 'subscription_cancelled',
            'subscription_id' => (string)$sub->id,
        ];
        $idempotencyKey = "subscription_{$sub->id}_cancelled_cust";

        $this->sendToCustomer($customer, 'subscription_cancelled', $title, $message, $data, $idempotencyKey);
    }

    public function notifyRestaurantSubscriptionCancelled(Subscription $sub): void
    {
        $restaurant = $sub->restaurant;
        if (!$restaurant) return;

        $title = 'Subscription Cancelled';
        $message = "A customer has cancelled their subscription.";
        $data = [
            'type' => 'subscription_cancelled',
            'subscription_id' => (string)$sub->id,
        ];
        $idempotencyKey = "subscription_{$sub->id}_cancelled_rest";

        $this->sendToRestaurant($restaurant, 'subscription_cancelled', $title, $message, $data, $idempotencyKey);
    }

    public function notifyRestaurantApproved(Restaurant $rest): void
    {
        $title = 'Restaurant Approved';
        $message = 'Your restaurant account has been approved.';
        $data = [
            'type' => 'restaurant_approved',
            'restaurant_id' => (string)$rest->id,
        ];
        $idempotencyKey = "restaurant_{$rest->id}_approved";

        $this->sendToRestaurant($rest, 'restaurant_approved', $title, $message, $data, $idempotencyKey);
    }

    public function notifyRestaurantBlocked(Restaurant $rest): void
    {
        $title = 'Restaurant Blocked';
        $message = 'Your restaurant account has been blocked by the administrator.';
        $data = [
            'type' => 'restaurant_blocked',
            'restaurant_id' => (string)$rest->id,
        ];
        $idempotencyKey = "restaurant_{$rest->id}_blocked";

        $this->sendToRestaurant($rest, 'restaurant_blocked', $title, $message, $data, $idempotencyKey);
    }

    public function notifySubscriptionExpiring(Subscription $sub, int $daysLeft): void
    {
        $customer = $sub->customer;
        if (!$customer) return;

        $planName = $sub->plan->name ?? 'Lunch Plan';
        $title = 'Subscription Expiring Soon';
        $message = "Your {$planName} subscription expires in {$daysLeft} days.";
        $data = [
            'type' => 'subscription_expiring',
            'subscription_id' => (string)$sub->id,
            'days_left' => (string)$daysLeft,
        ];
        $idempotencyKey = "subscription_{$sub->id}_expiry_{$daysLeft}_days";

        $this->sendToCustomer($customer, 'subscription_expiring', $title, $message, $data, $idempotencyKey);
    }

    /*
     |--------------------------------------------------------------------------
     | FCM Google V1 API Logic & Access Token Handling
     |--------------------------------------------------------------------------
     */

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
    protected function sendFCM(array $tokens, string $title, string $message, array $data, ?Notification $notification = null): void
    {
        Log::info('Dispatched Firebase FCM Push notification', [
            'tokens_count' => count($tokens),
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);

        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            Log::warning('FCM HTTP v1: No access token available. Skipping FCM transmission.');
            if ($notification) {
                $notification->update(['status' => 'FAILED']);
            }
            return;
        }

        $projectId = config('services.firebase.project_id') ?: 'kinkitchen-51b17';
        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $anySuccess = false;

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

                if ($response->successful()) {
                    $anySuccess = true;
                } else {
                    Log::error("FCM HTTP v1 transmission failed for token {$token}: " . $response->body());
                    $responseBody = $response->body();
                    $statusCode = $response->status();

                    // If status is 404/410, or response states UNREGISTERED/invalid token, mark inactive
                    if ($statusCode === 404 || $statusCode === 410 || 
                        str_contains($responseBody, 'UNREGISTERED') || 
                        str_contains($responseBody, 'NOT_FOUND') ||
                        str_contains($responseBody, 'Requested entity was not found')) {
                        FcmToken::where('token', $token)->update(['status' => 'INACTIVE']);
                        Log::warning("FCM token marked INACTIVE due to invalid target response: {$token}");
                    }
                }
            } catch (Exception $e) {
                Log::error("FCM Transmission Failure for token {$token}: " . $e->getMessage());
            }
        }

        if ($notification) {
            $notification->update(['status' => $anySuccess ? 'SENT' : 'FAILED']);
        }
    }
}
