<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Addon;
use App\Models\Restaurant;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class CustomerAddonController extends Controller
{
    use ApiResponseTrait;

    public function index(int $restaurantId): JsonResponse
    {
        $restaurant = Restaurant::active()->findOrFail($restaurantId);
        $addons = Addon::active()
            ->where('restaurant_id', $restaurantId)
            ->get();

        return $this->successResponse($addons, 'Add-ons fetched successfully.');
    }
}
