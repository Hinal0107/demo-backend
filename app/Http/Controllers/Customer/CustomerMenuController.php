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
        $restaurant = Restaurant::find($restaurantId);
        if (!$restaurant) {
            return $this->errorResponse('Restaurant not found.', 404);
        }

        $categoryId = $request->query('category_id');
        $categoryName = $request->query('category');
        $search = $request->query('search');
        $limit = $request->query('limit', 50);

        if (strtolower((string)$categoryName) === 'add-ons' || strtolower((string)$categoryName) === 'addons') {
            $addons = \App\Models\Addon::active()->where('restaurant_id', $restaurantId)->get();
            $items = $addons->map(function ($a) {
                return [
                    'id' => $a->id,
                    'restaurant_id' => $a->restaurant_id,
                    'category_id' => null,
                    'name' => $a->name,
                    'description' => $a->description ?? 'Delicious side / add-on item',
                    'image' => $a->image ? app(\App\Services\ImageUploadService::class)->formatUrl($a->image) : null,
                    'price' => (float)$a->price,
                    'discount_price' => null,
                    'active_price' => (float)$a->price,
                    'veg_type' => $a->veg_type ?? 'VEG',
                    'availability' => (bool)$a->availability,
                    'status' => $a->status,
                    'item_type' => 'Add-on',
                ];
            });
            return $this->successResponse($items, 'Add-ons fetched successfully.');
        }

        $query = MenuItem::active()->where('restaurant_id', $restaurantId);

        if (!empty($categoryId) && strtolower((string)$categoryId) !== 'all') {
            $query->where('category_id', $categoryId);
        } elseif (!empty($categoryName) && strtolower((string)$categoryName) !== 'all') {
            if (is_numeric($categoryName)) {
                $query->where('category_id', (int)$categoryName);
            } else {
                $matchedCat = MenuCategory::where('restaurant_id', $restaurantId)
                    ->where('name', 'like', '%' . $categoryName . '%')
                    ->first();
                if ($matchedCat) {
                    $query->where('category_id', $matchedCat->id);
                } else {
                    $query->where('name', 'like', '%' . $categoryName . '%');
                }
            }
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        $itemsList = $query->orderBy('sort_order', 'asc')->get();

        if ($itemsList->isEmpty() && (empty($categoryName) || strtolower((string)$categoryName) === 'all')) {
            $dailyMeals = \App\Models\DailyMeal::active()->where('restaurant_id', $restaurantId)->get();
            $addons = \App\Models\Addon::active()->where('restaurant_id', $restaurantId)->get();

            $combined = collect();
            foreach ($dailyMeals as $dm) {
                $combined->push([
                    'id' => $dm->id,
                    'restaurant_id' => $dm->restaurant_id,
                    'category_id' => null,
                    'name' => $dm->name,
                    'description' => $dm->description ?? 'Daily Tiffin Special Meal',
                    'image' => $dm->image ? app(\App\Services\ImageUploadService::class)->formatUrl($dm->image) : null,
                    'price' => (float)$dm->price,
                    'discount_price' => $dm->discount_price ? (float)$dm->discount_price : null,
                    'active_price' => (float)($dm->discount_price > 0 ? $dm->discount_price : $dm->price),
                    'veg_type' => $dm->veg_type ?? 'VEG',
                    'availability' => (bool)$dm->availability,
                    'status' => $dm->status,
                    'item_type' => 'Daily Meal',
                ]);
            }
            foreach ($addons as $a) {
                $combined->push([
                    'id' => $a->id,
                    'restaurant_id' => $a->restaurant_id,
                    'category_id' => null,
                    'name' => $a->name,
                    'description' => $a->description ?? 'Add-on Item',
                    'image' => $a->image ? app(\App\Services\ImageUploadService::class)->formatUrl($a->image) : null,
                    'price' => (float)$a->price,
                    'discount_price' => null,
                    'active_price' => (float)$a->price,
                    'veg_type' => $a->veg_type ?? 'VEG',
                    'availability' => (bool)$a->availability,
                    'status' => $a->status,
                    'item_type' => 'Add-on',
                ]);
            }
            return $this->successResponse($combined, 'Menu items fetched successfully.');
        }

        return $this->paginatedResponse(
            MenuItemResource::collection($itemsList),
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
