<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'restaurant_id' => $this->restaurant_id,
            'restaurant' => new RestaurantResource($this->whenLoaded('restaurant') ?: $this->restaurant),
            'customer_id' => $this->customer_id,
            'customer' => new UserResource($this->whenLoaded('customer') ?: $this->customer),
            'subscription_plan_id' => $this->subscription_plan_id,
            'plan' => new SubscriptionPlanResource($this->whenLoaded('plan') ?: $this->plan),
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'price' => (float)$this->price,
            'payment_status' => $this->payment_status,
            'status' => $this->status,
            'auto_renew' => (bool)$this->auto_renew,
            'cancelled_at' => $this->cancelled_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
