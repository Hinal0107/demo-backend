<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Addon;
use App\Models\Restaurant;
use App\Services\ImageUploadService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class CustomerAddonController extends Controller
{
    use ApiResponseTrait;

    public function index(int $restaurantId): JsonResponse
    {
        $restaurant = Restaurant::active()->findOrFail($restaurantId);
        $imageService = app(ImageUploadService::class);

        $addons = Addon::active()
            ->where('restaurant_id', $restaurantId)
            ->get()
            ->map(function ($addon) use ($imageService) {
                $addonData = $addon->toArray();
                $addonData['image'] = $addon->image ? $imageService->formatUrl($addon->image) : null;
                $addonData['image_url'] = $addonData['image'];
                return $addonData;
            });

        return $this->successResponse($addons, 'Add-ons fetched successfully.');
    }
}
