<?php

namespace App\Http\Resources;

use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $imageService = app(ImageUploadService::class);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => strtolower($this->role),
            'profile_image' => $this->profile_image ? $imageService->formatUrl($this->profile_image) : null,
            'status' => $this->status,
            'selected_restaurant_id' => $this->selected_restaurant_id,
            'selected_restaurant' => $this->selectedRestaurant ? [
                'id' => $this->selectedRestaurant->id,
                'name' => $this->selectedRestaurant->name,
                'logo' => $this->selectedRestaurant->logo ? $imageService->formatUrl($this->selectedRestaurant->logo) : null,
            ] : null,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}

