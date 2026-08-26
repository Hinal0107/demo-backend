<?php

namespace App\Services;

use App\Models\SubscriptionPlan;
use App\Models\Subscription;
use App\Models\SubscriptionOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Address;
use App\Models\Restaurant;
use App\Models\RestaurantCustomer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class SubscriptionService
{
    /**
     * Create a new subscription plan.
     */
    public function createPlan(int $restaurantId, array $data): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'restaurant_id' => $restaurantId,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'duration_value' => $data['duration_value'],
            'duration_type' => strtoupper($data['duration_type']),
            'meal_type' => $data['meal_type'],
            'meals_per_day' => $data['meals_per_day'] ?? 1,
            'total_meals' => $data['total_meals'],
            'delivery_frequency' => $data['delivery_frequency'] ?? 'daily',
            'start_date' => $data['start_date'] ?? null,
            'status' => $data['status'] ?? 'ACTIVE',
        ]);
    }

    /**
     * Update subscription plan.
     */
    public function updatePlan(SubscriptionPlan $plan, array $data): SubscriptionPlan
    {
        $fields = [
            'name', 'description', 'price', 'duration_value', 'duration_type',
            'meal_type', 'meals_per_day', 'total_meals', 'delivery_frequency',
            'start_date', 'status'
        ];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $plan->{$field} = $field === 'duration_type' ? strtoupper($data[$field]) : $data[$field];
            }
        }

        $plan->save();
        return $plan;
    }

    /**
     * Delete subscription plan.
     */
    public function deletePlan(SubscriptionPlan $plan): void
    {
        $plan->delete();
    }

    /**
     * Subscribe a customer to a plan.
     */
    public function createSubscription(int $customerId, array $data): Subscription
    {
        return DB::transaction(function () use ($customerId, $data) {
            // Validate plan
            $plan = SubscriptionPlan::active()->findOrFail($data['subscription_plan_id']);

            if ((int)$plan->restaurant_id !== (int)$data['restaurant_id']) {
                throw new Exception('Selected plan does not belong to this restaurant.', 422);
            }

            // Validate address
            $address = Address::where('id', $data['address_id'])
                ->where('customer_id', $customerId)
                ->first();

            if (!$address) {
                throw new Exception('Selected address does not exist or does not belong to the customer.', 422);
            }

            $startDate = Carbon::parse($data['start_date']);
            if ($startDate->isBefore(Carbon::today())) {
                throw new Exception('Start date cannot be in the past.', 422);
            }

            // Calculate total meals and max validity days based on plan duration
            $totalMeals = $plan->total_meals ?: ($plan->duration_type === 'WEEK' ? 7 : ($plan->duration_type === 'MONTH' ? 30 : 7));
            $maxValidityDays = 14; // Default weekly 14 days max

            switch (strtoupper($plan->duration_type)) {
                case 'WEEK':
                    $maxValidityDays = 14; // Maximum 14 days for weekly plan
                    break;
                case 'MONTH':
                    $maxValidityDays = 60; // Maximum 60 days for monthly plan
                    break;
                case 'DAY':
                    $maxValidityDays = max(2, $plan->duration_value * 2);
                    break;
                case 'CUSTOM':
                default:
                    $maxValidityDays = max(30, $totalMeals * 2);
                    break;
            }

            $endDate = clone $startDate;
            $endDate->addDays($plan->duration_type === 'WEEK' ? 7 * $plan->duration_value : ($plan->duration_type === 'MONTH' ? 30 * $plan->duration_value : $plan->duration_value));

            $maxValidityDate = clone $startDate;
            $maxValidityDate->addDays($maxValidityDays);

            // Create subscription with full meal tracking and validity bounds
            $subscription = Subscription::create([
                'restaurant_id' => $plan->restaurant_id,
                'customer_id' => $customerId,
                'subscription_plan_id' => $plan->id,
                'total_meals' => $totalMeals,
                'used_meals' => 0,
                'remaining_meals' => $totalMeals,
                'max_validity_days' => $maxValidityDays,
                'max_validity_date' => $maxValidityDate->toDateString(),
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'price' => $plan->price,
                'payment_status' => 'PAID', // In production, this shifts to Worldpay verification
                'status' => 'ACTIVE',
                'auto_renew' => $data['auto_renew'] ?? false,
            ]);

            // Link restaurant customer relationship if not exists
            RestaurantCustomer::firstOrCreate([
                'restaurant_id' => $plan->restaurant_id,
                'customer_id' => $customerId,
            ], [
                'status' => 'ACTIVE'
            ]);

            // Dispatch Events
            event(new \App\Events\SubscriptionPurchasedEvent($subscription));
            event(new \App\Events\SubscriptionActivatedEvent($subscription));

            return $subscription;
        });
    }

    /**
     * Check if a user has access to view/order meals (via initial Free Trial or Active Subscription).
     */
    public function checkUserAccessToMeals(\App\Models\User $user): array
    {
        // 1. Auto-expire any outdated subscriptions first
        $this->expireOutdatedSubscriptions($user->id);

        $freeTrialDays = 7; // Configurable initial free trial period (days)
        $registrationDate = $user->created_at ?? Carbon::now();
        $daysSinceRegistration = (int)Carbon::today()->diffInDays($registrationDate->startOfDay());

        $isInTrial = $daysSinceRegistration < $freeTrialDays;
        $trialDaysLeft = max(0, $freeTrialDays - $daysSinceRegistration);

        // 2. Check for active subscription
        $activeSubscription = Subscription::where('customer_id', $user->id)
            ->where('status', 'ACTIVE')
            ->where('payment_status', 'PAID')
            ->where('remaining_meals', '>', 0)
            ->where('start_date', '<=', Carbon::today()->toDateString())
            ->where('max_validity_date', '>=', Carbon::today()->toDateString())
            ->orderBy('id', 'desc')
            ->first();

        if ($isInTrial) {
            return [
                'can_access' => true,
                'is_trial' => true,
                'trial_days_remaining' => $trialDaysLeft,
                'message' => "You are currently in your initial {$freeTrialDays}-day free trial.",
                'subscription' => $activeSubscription ? new \App\Http\Resources\SubscriptionResource($activeSubscription) : null,
            ];
        }

        if ($activeSubscription) {
            return [
                'can_access' => true,
                'is_trial' => false,
                'trial_days_remaining' => 0,
                'message' => 'Active subscription plan found.',
                'subscription' => new \App\Http\Resources\SubscriptionResource($activeSubscription),
            ];
        }

        return [
            'can_access' => false,
            'is_trial' => false,
            'trial_days_remaining' => 0,
            'message' => 'Your initial free trial period has expired. Please purchase a subscription plan to access protected meal details.',
            'subscription' => null,
        ];
    }

    /**
     * Check and expire active subscriptions that passed max validity date or exhausted meals.
     */
    public function expireOutdatedSubscriptions(?int $customerId = null): int
    {
        $query = Subscription::where('status', 'ACTIVE');

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        $subscriptions = $query->get();
        $expiredCount = 0;

        foreach ($subscriptions as $sub) {
            if ($sub->checkAndAutoExpire()) {
                $expiredCount++;
            }
        }

        return $expiredCount;
    }

    public function pauseSubscription(Subscription $subscription): Subscription
    {
        if ($subscription->status !== 'ACTIVE') {
            throw new Exception('Only active subscriptions can be paused.', 422);
        }
        $subscription->status = 'PAUSED';
        $subscription->save();
        return $subscription;
    }

    public function resumeSubscription(Subscription $subscription): Subscription
    {
        if ($subscription->status !== 'PAUSED') {
            throw new Exception('Only paused subscriptions can be resumed.', 422);
        }
        $subscription->status = 'ACTIVE';
        $subscription->save();
        return $subscription;
    }

    public function cancelSubscription(Subscription $subscription): Subscription
    {
        if (in_array($subscription->status, ['CANCELLED', 'EXPIRED', 'COMPLETED'])) {
            throw new Exception('Subscription is already inactive.', 422);
        }
        $subscription->status = 'CANCELLED';
        $subscription->cancelled_at = now();
        $subscription->save();

        // Dispatch Event
        event(new \App\Events\SubscriptionCancelledEvent($subscription));

        return $subscription;
    }

    /**
     * Generate daily scheduled orders from active subscriptions.
     * Called by Laravel Scheduler Command.
     */
    public function generateScheduledOrdersForDate(Carbon $targetDate): int
    {
        $dateStr = $targetDate->toDateString();
        $dayOfWeek = strtoupper($targetDate->englishDayOfWeek); // MONDAY, TUESDAY...

        // Find active subscriptions for today
        $subscriptions = Subscription::where('status', 'ACTIVE')
            ->where('payment_status', 'PAID')
            ->where('start_date', '<=', $dateStr)
            ->where('end_date', '>=', $dateStr)
            ->get();

        $generatedCount = 0;

        foreach ($subscriptions as $sub) {
            // Check if already generated order for this subscription on this date
            $exists = SubscriptionOrder::where('subscription_id', $sub->id)
                ->where('scheduled_date', $dateStr)
                ->exists();

            if ($exists) {
                continue;
            }

            // Check delivery frequency constraint
            $plan = $sub->plan;
            $shouldDeliver = false;
            $freq = strtolower($plan->delivery_frequency);

            if ($freq === 'daily') {
                $shouldDeliver = true;
            } elseif ($freq === 'weekdays' && !in_array($dayOfWeek, ['SATURDAY', 'SUNDAY'])) {
                $shouldDeliver = true;
            } elseif ($freq === 'weekends' && in_array($dayOfWeek, ['SATURDAY', 'SUNDAY'])) {
                $shouldDeliver = true;
            }

            if (!$shouldDeliver) {
                continue;
            }

            // Skip if remaining_meals <= 0 or past max_validity_date
            if ($sub->remaining_meals <= 0 || ($sub->max_validity_date && $targetDate->isAfter($sub->max_validity_date))) {
                $sub->checkAndAutoExpire();
                continue;
            }

            // Run database transaction to generate order
            DB::transaction(function () use ($sub, $plan, $dateStr, &$generatedCount) {
                // Get customer's default address or first address
                $address = Address::where('customer_id', $sub->customer_id)
                    ->orderBy('is_default', 'desc')
                    ->first();

                if (!$address) {
                    Log::warning("Could not generate subscription order: Customer {$sub->customer_id} has no address.");
                    return;
                }

                // Generate Order
                $orderNumber = 'SUB-' . $sub->id . '-' . date('YmdHis') . rand(10, 99);
                
                $order = Order::create([
                    'order_number' => $orderNumber,
                    'restaurant_id' => $sub->restaurant_id,
                    'customer_id' => $sub->customer_id,
                    'subscription_id' => $sub->id,
                    'address_id' => $address->id,
                    'subtotal' => 0.00, // Prepaid under subscription price
                    'discount' => 0.00,
                    'delivery_fee' => 0.00,
                    'tax' => 0.00,
                    'total_amount' => 0.00,
                    'payment_status' => 'PAID', // Subscription is prepaid
                    'order_status' => 'CONFIRMED', // Automatically confirm subscription orders
                    'delivery_status' => 'PENDING',
                    'scheduled_date' => $dateStr,
                    'notes' => 'Subscription Meal: ' . $plan->name,
                    'confirmed_at' => now(),
                ]);

                // Create Order Item snapshotting the meal plan info
                OrderItem::create([
                    'order_id' => $order->id,
                    'menu_item_id' => null, // Not a single menu item
                    'item_name' => $plan->name . ' - Daily Delivery (' . ucfirst($plan->meal_type) . ')',
                    'quantity' => 1,
                    'unit_price' => 0.00,
                    'total_price' => 0.00,
                ]);

                // Create SubscriptionOrder link
                SubscriptionOrder::create([
                    'subscription_id' => $sub->id,
                    'restaurant_id' => $sub->restaurant_id,
                    'customer_id' => $sub->customer_id,
                    'scheduled_date' => $dateStr,
                    'order_id' => $order->id,
                    'order_status' => 'CONFIRMED',
                    'delivery_status' => 'PENDING',
                ]);

                // Update subscription meal counts
                $sub->used_meals += 1;
                $sub->remaining_meals = max(0, $sub->total_meals - $sub->used_meals);
                if ($sub->remaining_meals <= 0) {
                    $sub->status = 'COMPLETED';
                }
                $sub->save();

                $generatedCount++;
            });
        }

        return $generatedCount;
    }
}
