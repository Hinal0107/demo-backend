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

            // Calculate end date based on plan duration
            $endDate = clone $startDate;
            $durationVal = $plan->duration_value;
            switch ($plan->duration_type) {
                case 'DAY':
                    $endDate->addDays($durationVal);
                    break;
                case 'WEEK':
                    $endDate->addWeeks($durationVal);
                    break;
                case 'MONTH':
                    $endDate->addMonths($durationVal);
                    break;
                case 'CUSTOM':
                default:
                    // default to 30 days if custom is undefined
                    $endDate->addDays($plan->total_meals ?: 30);
                    break;
            }

            // Create subscription
            $subscription = Subscription::create([
                'restaurant_id' => $plan->restaurant_id,
                'customer_id' => $customerId,
                'subscription_plan_id' => $plan->id,
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

                $generatedCount++;
            });
        }

        return $generatedCount;
    }
}
