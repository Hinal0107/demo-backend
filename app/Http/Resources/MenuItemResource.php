<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'restaurant_id' => $this->restaurant_id,
            'category_id' => $this->category_id,
            'name' => $this->name,
            'description' => $this->description,
            'image' => $this->image,
            'price' => (float)$this->price,
            'discount_price' => $this->discount_price ? (float)$this->discount_price : null,
            'active_price' => (float)$this->active_price,
            'veg_type' => $this->veg_type,
            'availability' => (bool)$this->availability,
            'status' => $this->status,
            'sort_order' => $this->sort_order,
        ];
    }
}
