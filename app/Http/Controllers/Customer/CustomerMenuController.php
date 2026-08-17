<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\MenuCategoryResource;
use App\Http\Resources\MenuItemResource;
use App\Models\Restaurant;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CustomerMenuController extends Controller
{
    use ApiResponseTrait;

    /**
     * GET /restaurants/{restaurantId}/categories
     */
    public function categories(int $restaurantId): JsonResponse
    {
        // Check if restaurant is active
        $restaurant = Restaurant::active()->findOrFail($restaurantId);

        $categories = MenuCategory::active()
            ->where('restaurant_id', $restaurantId)
            ->orderBy('sort_order', 'asc')
            ->get();

        return $this->successResponse(
            MenuCategoryResource::collection($categories),
            'Menu categories fetched successfully.'
        );
    }

    /**
     * GET /restaurants/{restaurantId}/menu
     */
    public function menu(Request $request, int $restaurantId): JsonResponse
    {
        $restaurant = Restaurant::active()->findOrFail($restaurantId);

        $categoryId = $request->query('category_id');
        $search = $request->query('search');
        $limit = $request->query('limit', 20);

        $query = MenuItem::active()->where('restaurant_id', $restaurantId);

        if (!empty($categoryId)) {
            $query->where('category_id', $categoryId);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        $items = $query->orderBy('sort_order', 'asc')->paginate($limit);

        return $this->paginatedResponse(
            MenuItemResource::collection($items),
            'Menu items fetched successfully.'
        );
    }

    /**
     * GET /menu-items/{id}
     */
    public function showItem(int $id): JsonResponse
    {
        $item = MenuItem::active()->findOrFail($id);

        return $this->successResponse(
            new MenuItemResource($item),
            'Menu item details fetched successfully.'
        );
    }
}
