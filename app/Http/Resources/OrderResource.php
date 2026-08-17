<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'restaurant_id' => $this->restaurant_id,
            'restaurant' => new RestaurantResource($this->whenLoaded('restaurant') ?: $this->restaurant),
            'customer_id' => $this->customer_id,
            'customer' => new UserResource($this->whenLoaded('customer') ?: $this->customer),
            'subscription_id' => $this->subscription_id,
            'address_id' => $this->address_id,
            'address' => $this->address, // Address is simple enough to return directly
            'subtotal' => (float)$this->subtotal,
            'discount' => (float)$this->discount,
            'delivery_fee' => (float)$this->delivery_fee,
            'tax' => (float)$this->tax,
            'total_amount' => (float)$this->total_amount,
            'payment_status' => $this->payment_status,
            'order_status' => $this->order_status,
            'delivery_status' => $this->delivery_status,
            'scheduled_date' => $this->scheduled_date?->toDateString(),
            'notes' => $this->notes,
            'delivery_otp' => $this->delivery_otp,
            'confirmed_at' => $this->confirmed_at?->toDateTimeString(),
            'preparing_at' => $this->preparing_at?->toDateTimeString(),
            'ready_at' => $this->ready_at?->toDateTimeString(),
            'out_for_delivery_at' => $this->out_for_delivery_at?->toDateTimeString(),
            'delivered_at' => $this->delivered_at?->toDateTimeString(),
            'cancelled_at' => $this->cancelled_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
            // Nesting items
            'items' => OrderItemResource::collection($this->whenLoaded('items') ?: $this->items),
            // Nesting payments
            'payments' => PaymentResource::collection($this->whenLoaded('payments') ?: $this->payments),
            // Nesting history
            'status_history' => $this->statusHistories->map(function ($history) {
                return [
                    'id' => $history->id,
                    'status_type' => $history->status_type,
                    'old_status' => $history->old_status,
                    'new_status' => $history->new_status,
                    'changed_by_role' => $history->changed_by_role,
                    'remarks' => $history->remarks,
                    'created_at' => $history->created_at?->toDateTimeString(),
                ];
            }),
        ];
    }
}
