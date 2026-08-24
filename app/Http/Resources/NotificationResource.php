<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'restaurant_id' => $this->restaurant_id,
            'order_id' => $this->order_id,
            'subscription_id' => $this->subscription_id,
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'is_read' => $this->read_at !== null,
            'read_at' => $this->read_at?->toDateTimeString(),
            'data' => $this->data,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
