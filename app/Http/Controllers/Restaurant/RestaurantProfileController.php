<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Http\Requests\RestaurantRequest;
use App\Http\Resources\RestaurantResource;
use App\Models\Restaurant;
use App\Services\RestaurantService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class RestaurantProfileController extends Controller
{
    use ApiResponseTrait;

    protected RestaurantService $restaurantService;

    public function __construct(RestaurantService $restaurantService)
    {
        $this->restaurantService = $restaurantService;
    }

    /**
     * GET /restaurant/profile
     */
    public function show(Request $request): JsonResponse
    {
        $restaurantId = $request->attributes->get('restaurant_id');
        $restaurant = Restaurant::findOrFail($restaurantId);

        return $this->successResponse(
            new RestaurantResource($restaurant),
            'Restaurant profile fetched successfully.'
        );
    }

    /**
     * PUT /restaurant/profile
     */
    public function update(RestaurantRequest $request): JsonResponse
    {
        $restaurantId = $request->attributes->get('restaurant_id');
        $restaurant = Restaurant::findOrFail($restaurantId);

        try {
            $updated = $this->restaurantService->updateProfile($restaurant, $request->validated());
            return $this->successResponse(
                new RestaurantResource($updated),
                'Restaurant profile updated successfully.'
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
