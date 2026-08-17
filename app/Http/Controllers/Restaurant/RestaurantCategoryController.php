<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Http\Requests\MenuCategoryRequest;
use App\Http\Resources\MenuCategoryResource;
use App\Models\MenuCategory;
use App\Services\MenuService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class RestaurantCategoryController extends Controller
{
    use ApiResponseTrait;

    protected MenuService $menuService;

    public function __construct(MenuService $menuService)
    {
        $this->menuService = $menuService;
    }

    /**
     * GET /restaurant/categories
     */
    public function index(Request $request): JsonResponse
    {
        $restaurantId = $request->attributes->get('restaurant_id');

        $categories = MenuCategory::where('restaurant_id', $restaurantId)
            ->orderBy('sort_order', 'asc')
            ->get();

        return $this->successResponse(
            MenuCategoryResource::collection($categories),
            'Restaurant categories fetched successfully.'
        );
    }

    /**
     * POST /restaurant/categories
     */
    public function store(MenuCategoryRequest $request): JsonResponse
    {
        $restaurantId = $request->attributes->get('restaurant_id');

        try {
            $category = $this->menuService->createCategory($restaurantId, $request->validated());
            return $this->successResponse(
                new MenuCategoryResource($category),
                'Category created successfully.',
                201
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * PUT /restaurant/categories/{id}
     */
    public function update(MenuCategoryRequest $request, int $id): JsonResponse
    {
        $restaurantId = $request->attributes->get('restaurant_id');
        $category = MenuCategory::where('restaurant_id', $restaurantId)->findOrFail($id);

        try {
            $updated = $this->menuService->updateCategory($category, $request->validated());
            return $this->successResponse(
                new MenuCategoryResource($updated),
                'Category updated successfully.'
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * DELETE /restaurant/categories/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $restaurantId = $request->attributes->get('restaurant_id');
        $category = MenuCategory::where('restaurant_id', $restaurantId)->findOrFail($id);

        try {
            $this->menuService->deleteCategory($category);
            return $this->successResponse(null, 'Category deleted successfully.');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
