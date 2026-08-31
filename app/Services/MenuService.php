<?php

namespace App\Services;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use Exception;

class MenuService
{
    protected ImageUploadService $imageService;

    public function __construct(ImageUploadService $imageService)
    {
        $this->imageService = $imageService;
    }

    // --- Category Scoping Operations ---

    public function createCategory(int $restaurantId, array $data): MenuCategory
    {
        $imageUrl = null;
        if (isset($data['image'])) {
            $imageUrl = $this->imageService->upload($data['image'], 'menu-categories');
        }

        return MenuCategory::create([
            'restaurant_id' => $restaurantId,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'image' => $imageUrl,
            'sort_order' => $data['sort_order'] ?? 0,
            'status' => $data['status'] ?? 'ACTIVE',
        ]);
    }

    public function updateCategory(MenuCategory $category, array $data): MenuCategory
    {
        if (isset($data['image'])) {
            if ($category->image) {
                $this->imageService->delete($category->image);
            }
            $category->image = $this->imageService->upload($data['image'], 'menu-categories');
        }

        $fields = ['name', 'description', 'sort_order', 'status'];
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $category->{$field} = $data[$field];
            }
        }

        $category->save();
        return $category;
    }

    public function deleteCategory(MenuCategory $category): void
    {
        if ($category->image) {
            $this->imageService->delete($category->image);
        }
        $category->delete();
    }

    // --- Menu Item Scoping Operations ---

    public function createMenuItem(int $restaurantId, array $data): MenuItem
    {
        // Verify category belongs to this restaurant
        $category = MenuCategory::where('id', $data['category_id'])
            ->where('restaurant_id', $restaurantId)
            ->first();

        if (!$category) {
            throw new Exception('Selected category does not exist for this restaurant.', 422);
        }

        $imageUrl = null;
        if (isset($data['image'])) {
            $imageUrl = $this->imageService->upload($data['image'], 'menu-items');
        }

        $item = MenuItem::create([
            'restaurant_id' => $restaurantId,
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'image' => $imageUrl,
            'price' => $data['price'],
            'discount_price' => $data['discount_price'] ?? null,
            'veg_type' => strtoupper($data['veg_type']),
            'availability' => $data['availability'] ?? true,
            'status' => $data['status'] ?? 'ACTIVE',
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        $cName = strtolower($category->name);
        $isAddon = !empty($data['is_addon']) || str_contains($cName, 'add-on') || str_contains($cName, 'addon') || str_contains($cName, 'extra') || str_contains($cName, 'side') || str_contains($cName, 'farsan') || str_contains($cName, 'sweet') || str_contains($cName, 'dessert');
        if ($isAddon) {
            \App\Models\Addon::updateOrCreate([
                'restaurant_id' => $restaurantId,
                'name' => $item->name,
            ], [
                'description' => $item->description,
                'price' => $item->price,
                'image' => $item->image,
                'availability' => $item->availability,
                'status' => $item->status,
            ]);
        }

        return $item;
    }

    public function updateMenuItem(MenuItem $item, array $data): MenuItem
    {
        if (isset($data['category_id'])) {
            $category = MenuCategory::where('id', $data['category_id'])
                ->where('restaurant_id', $item->restaurant_id)
                ->first();

            if (!$category) {
                throw new Exception('Selected category does not exist for this restaurant.', 422);
            }
            $item->category_id = $data['category_id'];
        }

        if (isset($data['image'])) {
            if ($item->image) {
                $this->imageService->delete($item->image);
            }
            $item->image = $this->imageService->upload($data['image'], 'menu-items');
        }

        $fields = ['name', 'description', 'price', 'discount_price', 'veg_type', 'availability', 'status', 'sort_order'];
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $item->{$field} = $field === 'veg_type' ? strtoupper($data[$field]) : $data[$field];
            }
        }

        $item->save();

        $category = MenuCategory::find($item->category_id);
        $cName = $category ? strtolower($category->name) : '';
        $isAddon = !empty($data['is_addon']) || str_contains($cName, 'add-on') || str_contains($cName, 'addon') || str_contains($cName, 'extra') || str_contains($cName, 'side') || str_contains($cName, 'farsan') || str_contains($cName, 'sweet') || str_contains($cName, 'dessert');
        if ($isAddon) {
            \App\Models\Addon::updateOrCreate([
                'restaurant_id' => $item->restaurant_id,
                'name' => $item->name,
            ], [
                'description' => $item->description,
                'price' => $item->price,
                'image' => $item->image,
                'availability' => $item->availability,
                'status' => $item->status,
            ]);
        }

        return $item;
    }

    public function deleteMenuItem(MenuItem $item): void
    {
        if ($item->image) {
            $this->imageService->delete($item->image);
        }
        $item->delete();
    }
}
