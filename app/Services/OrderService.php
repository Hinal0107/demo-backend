<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\MenuItem;
use App\Models\Address;
use App\Models\Restaurant;
use App\Models\RestaurantCustomer;
use App\Models\OrderStatusHistory;
use App\Models\CartItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class OrderService
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Create a new order from cart or check-out details.
     */
    public function createOrder(int $customerId, array $data): Order
    {
        return DB::transaction(function () use ($customerId, $data) {
            $restaurantId = $data['restaurant_id'];

            // 1. Validate restaurant
            $restaurant = Restaurant::active()->findOrFail($restaurantId);

            // 2. Validate address
            $address = Address::where('id', $data['address_id'])
                ->where('customer_id', $customerId)
                ->first();

            if (!$address) {
                throw new Exception('Selected address does not exist or does not belong to the customer.', 422);
            }

            // 3. Calculate Totals
            $subtotal = 0.00;
            $itemsToCreate = [];

            foreach ($data['items'] as $itemData) {
                $menuItem = MenuItem::active()->findOrFail($itemData['menu_item_id']);

                if ((int)$menuItem->restaurant_id !== (int)$restaurantId) {
                    throw new Exception("Menu item '{$menuItem->name}' does not belong to the selected restaurant.", 422);
                }

                $qty = $itemData['quantity'];
                if ($qty <= 0) {
                    throw new Exception('Quantity must be greater than 0.', 422);
                }

                $price = $menuItem->active_price;
                $totalPrice = $price * $qty;
                $subtotal += $totalPrice;

                $itemsToCreate[] = [
                    'menu_item_id' => $menuItem->id,
                    'item_name' => $menuItem->name,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'total_price' => $totalPrice,
                ];
            }

            if (empty($itemsToCreate)) {
                throw new Exception('Order must contain at least one item.', 422);
            }

            // Simple Tax/Fees calculation
            $tax = round($subtotal * 0.10, 2); // 10% flat tax
            $deliveryFee = 3.50; // flat delivery fee
            $discount = 0.00; // placeholder for promo codes
            $totalAmount = $subtotal + $tax + $deliveryFee - $discount;

            $orderNumber = 'ORD-' . date('Ymd') . '-' . rand(100000, 999999);

            // 4. Create Order
            $order = Order::create([
                'order_number' => $orderNumber,
                'restaurant_id' => $restaurantId,
                'customer_id' => $customerId,
                'address_id' => $address->id,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'delivery_fee' => $deliveryFee,
                'tax' => $tax,
                'total_amount' => $totalAmount,
                'payment_status' => 'PENDING_PAYMENT',
                'order_status' => 'PENDING_PAYMENT',
                'delivery_status' => 'PENDING',
                'scheduled_date' => $data['scheduled_date'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // 5. Create Order Items (immutable snapshot data)
            foreach ($itemsToCreate as $item) {
                OrderItem::create(array_merge($item, ['order_id' => $order->id]));
            }

            // 6. Log initial status history
            $this->logStatusChange($order->id, 'ORDER', null, 'PENDING_PAYMENT', $customerId, 'CUSTOMER', 'Order placed successfully.');
            $this->logStatusChange($order->id, 'DELIVERY', null, 'PENDING', $customerId, 'CUSTOMER', 'Delivery scheduled.');
            $this->logStatusChange($order->id, 'PAYMENT', null, 'PENDING', $customerId, 'CUSTOMER', 'Awaiting payment confirmation.');

            // 7. Establish restaurant customer link
            RestaurantCustomer::firstOrCreate([
                'restaurant_id' => $restaurantId,
                'customer_id' => $customerId,
            ], [
                'status' => 'ACTIVE'
            ]);

            // 8. Clear customer cart for this restaurant
            CartItem::where('customer_id', $customerId)
                ->where('restaurant_id', $restaurantId)
                ->delete();

            return $order;
        });
    }

    /**
     * Transition general order status.
     */
    public function transitionOrderStatus(Order $order, string $newStatus, int $changedByUserId, string $changerRole, ?string $remarks = null): Order
    {
        $oldStatus = $order->order_status;
        $newStatus = strtoupper($newStatus);

        if ($oldStatus === $newStatus) {
            return $order;
        }

        // Validate transitions
        $valid = false;
        switch ($oldStatus) {
            case 'PENDING_PAYMENT':
                $valid = in_array($newStatus, ['PAID', 'CANCELLED']);
                break;
            case 'PAID':
                $valid = in_array($newStatus, ['CONFIRMED', 'CANCELLED']);
                break;
            case 'CONFIRMED':
                $valid = in_array($newStatus, ['PREPARING', 'CANCELLED']);
                break;
            case 'PREPARING':
                $valid = in_array($newStatus, ['READY']);
                break;
            case 'READY':
                $valid = in_array($newStatus, ['COMPLETED', 'CANCELLED']);
                break;
            case 'COMPLETED':
            case 'CANCELLED':
                $valid = false; // Terminal states
                break;
        }

        if (!$valid) {
            throw new Exception("Invalid order status transition from '{$oldStatus}' to '{$newStatus}'.", 422);
        }

        return DB::transaction(function () use ($order, $oldStatus, $newStatus, $changedByUserId, $changerRole, $remarks) {
            $order->order_status = $newStatus;

            // Set timestamps based on status
            if ($newStatus === 'CONFIRMED') {
                $order->confirmed_at = now();
            } elseif ($newStatus === 'PREPARING') {
                $order->preparing_at = now();
            } elseif ($newStatus === 'READY') {
                $order->ready_at = now();
            } elseif ($newStatus === 'CANCELLED') {
                $order->cancelled_at = now();
            }

            $order->save();

            // Log history
            $this->logStatusChange($order->id, 'ORDER', $oldStatus, $newStatus, $changedByUserId, $changerRole, $remarks);

            // Dispatch Events
            if ($newStatus === 'PAID') {
                event(new \App\Events\OrderPaymentSuccessfulEvent($order));
            } elseif ($newStatus === 'CONFIRMED') {
                event(new \App\Events\OrderConfirmedEvent($order));
                event(new \App\Events\OrderCreatedEvent($order)); // Alert Restaurant
            } elseif ($newStatus === 'PREPARING') {
                event(new \App\Events\OrderPreparingEvent($order));
            } elseif ($newStatus === 'READY') {
                event(new \App\Events\OrderReadyEvent($order));
            } elseif ($newStatus === 'CANCELLED') {
                event(new \App\Events\OrderCancelledEvent($order, $remarks ?: ''));
            }

            return $order;
        });
    }

    /**
     * Transition delivery status.
     */
    public function transitionDeliveryStatus(Order $order, string $newDeliveryStatus, int $changedByUserId, string $changerRole, ?array $additionalData = null): Order
    {
        $oldDeliveryStatus = $order->delivery_status;
        $newDeliveryStatus = strtoupper($newDeliveryStatus);
        $remarks = $additionalData['reason'] ?? $additionalData['remarks'] ?? null;

        if ($oldDeliveryStatus === $newDeliveryStatus) {
            return $order;
        }

        // Validate delivery transitions
        $valid = false;
        switch ($oldDeliveryStatus) {
            case 'PENDING':
                $valid = in_array($newDeliveryStatus, ['OUT_FOR_DELIVERY', 'FAILED']);
                break;
            case 'OUT_FOR_DELIVERY':
                $valid = in_array($newDeliveryStatus, ['DELIVERED', 'FAILED']);
                break;
            case 'DELIVERED':
            case 'FAILED':
                $valid = false; // Terminal states
                break;
        }

        if (!$valid) {
            throw new Exception("Invalid delivery status transition from '{$oldDeliveryStatus}' to '{$newDeliveryStatus}'.", 422);
        }

        // Handle OTP verification for marking delivered
        if ($newDeliveryStatus === 'DELIVERED') {
            if ($order->delivery_otp && (!isset($additionalData['delivery_otp']) || $additionalData['delivery_otp'] !== $order->delivery_otp)) {
                throw new Exception('Invalid or missing Delivery OTP.', 422);
            }
        }

        return DB::transaction(function () use ($order, $oldDeliveryStatus, $newDeliveryStatus, $changedByUserId, $changerRole, $remarks) {
            $order->delivery_status = $newDeliveryStatus;

            if ($newDeliveryStatus === 'OUT_FOR_DELIVERY') {
                $order->out_for_delivery_at = now();
                // Generate a random 4-digit OTP if not already generated
                if (!$order->delivery_otp) {
                    $order->delivery_otp = (string)rand(1000, 9999);
                }
            } elseif ($newDeliveryStatus === 'DELIVERED') {
                $order->delivered_at = now();
                $order->order_status = 'COMPLETED'; // Marking delivered automatically completes order
                
                $this->logStatusChange($order->id, 'ORDER', $order->order_status, 'COMPLETED', $changedByUserId, $changerRole, 'Delivery completed.');
            }

            $order->save();

            // Log history
            $this->logStatusChange($order->id, 'DELIVERY', $oldDeliveryStatus, $newDeliveryStatus, $changedByUserId, $changerRole, $remarks);

            // Dispatch Events
            if ($newDeliveryStatus === 'OUT_FOR_DELIVERY') {
                event(new \App\Events\OrderOutForDeliveryEvent($order));
            } elseif ($newDeliveryStatus === 'DELIVERED') {
                event(new \App\Events\OrderDeliveredEvent($order));
            }

            return $order;
        });
    }

    /**
     * Log status transition.
     */
    protected function logStatusChange(int $orderId, string $type, ?string $oldStatus, string $newStatus, ?int $userId, string $role, ?string $remarks): void
    {
        OrderStatusHistory::create([
            'order_id' => $orderId,
            'status_type' => $type,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => $userId,
            'changed_by_role' => $role,
            'remarks' => $remarks,
        ]);
    }
}
