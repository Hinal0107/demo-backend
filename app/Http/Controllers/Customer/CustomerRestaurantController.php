<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\RestaurantResource;
use App\Models\Restaurant;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CustomerRestaurantController extends Controller
{
    use ApiResponseTrait;

    /**
     * GET /restaurants
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $limit = $request->query('limit', 20);

        // Fetch active restaurants only
        $query = Restaurant::active();

        if (!empty($search)) {
            $query->where('name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%')
                  ->orWhere('city', 'like', '%' . $search . '%')
                  ->orWhere('pincode', 'like', '%' . $search . '%');
        }

        $restaurants = $query->paginate($limit);

        return $this->paginatedResponse(
            RestaurantResource::collection($restaurants),
            'Restaurants fetched successfully.'
        );
    }

    /**
     * GET /restaurants/{restaurantId}
     */
    public function show(int $restaurantId): JsonResponse
    {
        $restaurant = Restaurant::active()->findOrFail($restaurantId);

        return $this->successResponse(
            new RestaurantResource($restaurant),
            'Restaurant details fetched successfully.'
        );
    }
}
