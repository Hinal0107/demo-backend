<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\RestaurantResource;
use App\Models\Restaurant;
use App\Models\RestaurantCustomer;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CustomerRestaurantController extends Controller
{
    use ApiResponseTrait;

    /**
     * GET /restaurants
     * Active & nearby area filtering
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $latitude = $request->query('latitude');
        $longitude = $request->query('longitude');
        $radius = $request->query('radius'); // in km
        $city = $request->query('city');
        $pincode = $request->query('pincode');
        $limit = $request->query('limit', 20);

        // Fetch active restaurants only
        $query = Restaurant::active();

        // Location-based filtering (Haversine formula via scopeNearby)
        if (!empty($latitude) && !empty($longitude)) {
            $query->nearby((float)$latitude, (float)$longitude, $radius ? (float)$radius : null);
        }

        // Pincode / City filtering
        if (!empty($pincode)) {
            $query->where('pincode', $pincode);
        }

        if (!empty($city)) {
            $query->where('city', 'like', '%' . $city . '%');
        }

        // Search term filtering
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%')
                  ->orWhere('city', 'like', '%' . $search . '%')
                  ->orWhere('pincode', 'like', '%' . $search . '%');
            });
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

    /**
     * POST /customer/select-restaurant
     * Maintain relationship between customer and selected restaurant
     */
    public function selectRestaurant(Request $request): JsonResponse
    {
        $request->validate([
            'restaurant_id' => 'required|integer|exists:restaurants,id',
        ]);

        $restaurantId = $request->input('restaurant_id');
        $restaurant = Restaurant::active()->findOrFail($restaurantId);
        $user = $request->user();

        // Update customer's selected restaurant
        $user->selected_restaurant_id = $restaurant->id;
        $user->save();

        // Update or create restaurant-customer mapping
        RestaurantCustomer::updateOrCreate(
            ['restaurant_id' => $restaurant->id, 'customer_id' => $user->id],
            ['status' => 'ACTIVE']
        );

        return $this->successResponse([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'selected_restaurant_id' => $user->selected_restaurant_id,
            ],
            'selected_restaurant' => new RestaurantResource($restaurant),
        ], 'Restaurant selected successfully.');
    }
}
