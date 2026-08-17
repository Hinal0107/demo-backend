<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'restaurant_id' => $this->restaurant_id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => (float)$this->price,
            'duration_value' => $this->duration_value,
            'duration_type' => $this->duration_type,
            'meal_type' => $this->meal_type,
            'meals_per_day' => $this->meals_per_day,
            'total_meals' => $this->total_meals,
            'delivery_frequency' => $this->delivery_frequency,
            'start_date' => $this->start_date?->toDateString(),
            'status' => $this->status,
        ];
    }
}
