<?php

namespace App\Http\Resources;

use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $imageService = app(ImageUploadService::class);

        return [
            'id' => $this->id,
            'restaurant_id' => $this->restaurant_id,
            'name' => $this->name,
            'description' => $this->description,
            'image' => $this->image ? $imageService->formatUrl($this->image) : null,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
        ];
    }
}

