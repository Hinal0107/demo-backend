<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Http\Requests\MenuItemRequest;
use App\Http\Resources\MenuItemResource;
use App\Models\MenuItem;
use App\Services\MenuService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class RestaurantMenuItemController extends Controller
{
    use ApiResponseTrait;

    protected MenuService $menuService;

    public function __construct(MenuService $menuService)
    {
        $this->menuService = $menuService;
    }

    /**
     * GET /restaurant/menu-items
     */
    public function index(Request $request): JsonResponse
    {
        $restaurantId = $request->attributes->get('restaurant_id');
        $categoryId = $request->query('category_id');
        $search = $request->query('search');
        $limit = $request->query('limit', 20);

        $query = MenuItem::where('restaurant_id', $restaurantId);

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
     * POST /restaurant/menu-items
     */
    public function store(MenuItemRequest $request): JsonResponse
    {
        $restaurantId = $request->attributes->get('restaurant_id');

        try {
            $item = $this->menuService->createMenuItem($restaurantId, $request->validated());
            return $this->successResponse(
                new MenuItemResource($item),
                'Menu item created successfully.',
                201
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    /**
     * PUT /restaurant/menu-items/{id}
     */
    public function update(MenuItemRequest $request, int $id): JsonResponse
    {
        $restaurantId = $request->attributes->get('restaurant_id');
        $item = MenuItem::where('restaurant_id', $restaurantId)->findOrFail($id);

        try {
            $updated = $this->menuService->updateMenuItem($item, $request->validated());
            return $this->successResponse(
                new MenuItemResource($updated),
                'Menu item updated successfully.'
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    /**
     * DELETE /restaurant/menu-items/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $restaurantId = $request->attributes->get('restaurant_id');
        $item = MenuItem::where('restaurant_id', $restaurantId)->findOrFail($id);

        try {
            $this->menuService->deleteMenuItem($item);
            return $this->successResponse(null, 'Menu item deleted successfully.');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
