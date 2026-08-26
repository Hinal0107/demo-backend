<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\MenuItem;
use App\Models\DailyMeal;
use App\Models\Addon;
use App\Models\Tax;
use App\Services\ImageUploadService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    use ApiResponseTrait;

    /**
     * GET /cart
     * Get customer cart items with prices, quantities, taxes/GST, and final calculated total.
     */
    public function index(Request $request): JsonResponse
    {
        $imageService = app(ImageUploadService::class);

        $cartItems = CartItem::with(['menuItem', 'addon', 'dailyMeal'])
            ->where('customer_id', $request->user()->id)
            ->get();

        $subtotal = 0.00;
        foreach ($cartItems as $item) {
            $subtotal += (float)$item->total_price;
        }

        $restaurantId = $cartItems->first()?->restaurant_id;
        $taxes = [];
        $totalTaxAmount = 0.00;

        if ($restaurantId) {
            $activeTaxes = Tax::where('restaurant_id', $restaurantId)
                ->where('status', 'ACTIVE')
                ->get();

            foreach ($activeTaxes as $tax) {
                $taxAmount = round(($subtotal * (float)$tax->rate) / 100, 2);
                $totalTaxAmount += $taxAmount;
                $taxes[] = [
                    'id' => $tax->id,
                    'name' => $tax->name,
                    'rate' => (float)$tax->rate,
                    'tax_amount' => $taxAmount,
                ];
            }
        }

        $finalAmount = round($subtotal + $totalTaxAmount, 2);

        return $this->successResponse([
            'items' => $cartItems->map(function ($item) use ($imageService) {
                $name = null;
                $image = null;
                $itemType = 'UNKNOWN';

                if ($item->daily_meal_id) {
                    $itemType = 'DAILY_MEAL';
                    $name = $item->dailyMeal?->name;
                    $image = $item->dailyMeal?->image;
                } elseif ($item->menu_item_id) {
                    $itemType = 'MENU_ITEM';
                    $name = $item->menuItem?->name;
                    $image = $item->menuItem?->image;
                } elseif ($item->addon_id) {
                    $itemType = 'ADDON';
                    $name = $item->addon?->name;
                    $image = $item->addon?->image;
                }

                return [
                    'id' => $item->id,
                    'restaurant_id' => $item->restaurant_id,
                    'item_type' => $itemType,
                    'menu_item_id' => $item->menu_item_id,
                    'daily_meal_id' => $item->daily_meal_id,
                    'addon_id' => $item->addon_id,
                    'name' => $name,
                    'image' => $image ? $imageService->formatUrl($image) : null,
                    'unit_price' => (float)$item->unit_price,
                    'quantity' => $item->quantity,
                    'total_price' => (float)$item->total_price, // Quantity-wise total price
                ];
            }),
            'restaurant_id' => $restaurantId,
            'subtotal' => round($subtotal, 2),
            'taxes' => $taxes,
            'total_tax_amount' => round($totalTaxAmount, 2),
            'final_amount' => $finalAmount,
        ], 'Cart fetched successfully.');
    }

    /**
     * POST /cart/items
     * Add Menu Item, Daily Meal, or Addon to Cart
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'restaurant_id' => 'required|integer|exists:restaurants,id',
            'menu_item_id' => 'nullable|integer|exists:menu_items,id',
            'daily_meal_id' => 'nullable|integer|exists:daily_meals,id',
            'addon_id' => 'nullable|integer|exists:addons,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $customerId = $request->user()->id;
        $restaurantId = $request->input('restaurant_id');
        $menuItemId = $request->input('menu_item_id');
        $dailyMealId = $request->input('daily_meal_id');
        $addonId = $request->input('addon_id');
        $qty = $request->input('quantity');

        if (!$menuItemId && !$addonId && !$dailyMealId) {
            return $this->errorResponse('One of menu_item_id, daily_meal_id, or addon_id is required.', 422);
        }

        // Verify item belongs to restaurant and fetch price
        if ($dailyMealId) {
            $dailyMeal = DailyMeal::active()->findOrFail($dailyMealId);
            if ((int)$dailyMeal->restaurant_id !== (int)$restaurantId) {
                return $this->errorResponse('Selected daily meal does not belong to this restaurant.', 422);
            }
            $price = $dailyMeal->discount_price && $dailyMeal->discount_price > 0 
                ? $dailyMeal->discount_price 
                : $dailyMeal->price;
        } elseif ($addonId) {
            $addon = Addon::active()->findOrFail($addonId);
            if ((int)$addon->restaurant_id !== (int)$restaurantId) {
                return $this->errorResponse('Selected add-on does not belong to this restaurant.', 422);
            }
            $price = $addon->price;
        } else {
            $menuItem = MenuItem::active()->findOrFail($menuItemId);
            if ((int)$menuItem->restaurant_id !== (int)$restaurantId) {
                return $this->errorResponse('Selected item does not belong to this restaurant.', 422);
            }
            $price = $menuItem->active_price;
        }

        // Check if cart contains items from a different restaurant
        $existingDifferent = CartItem::where('customer_id', $customerId)
            ->where('restaurant_id', '!=', $restaurantId)
            ->exists();

        if ($existingDifferent) {
            return $this->errorResponse(
                'Your cart already contains items from another restaurant. Please clear your cart first.',
                422,
                ['conflict' => 'Cart contains items from another restaurant.']
            );
        }

        // Add or update quantity
        if ($dailyMealId) {
            $cartItem = CartItem::where('customer_id', $customerId)
                ->where('daily_meal_id', $dailyMealId)
                ->first();
        } elseif ($addonId) {
            $cartItem = CartItem::where('customer_id', $customerId)
                ->where('addon_id', $addonId)
                ->first();
        } else {
            $cartItem = CartItem::where('customer_id', $customerId)
                ->where('menu_item_id', $menuItemId)
                ->first();
        }

        if ($cartItem) {
            $cartItem->quantity += $qty;
            $cartItem->total_price = round($cartItem->quantity * $price, 2);
            $cartItem->save();
        } else {
            $cartItem = CartItem::create([
                'customer_id' => $customerId,
                'restaurant_id' => $restaurantId,
                'menu_item_id' => $menuItemId,
                'daily_meal_id' => $dailyMealId,
                'addon_id' => $addonId,
                'quantity' => $qty,
                'unit_price' => $price,
                'total_price' => round($qty * $price, 2),
            ]);
        }

        return $this->successResponse([
            'id' => $cartItem->id,
            'quantity' => $cartItem->quantity,
            'unit_price' => (float)$cartItem->unit_price,
            'total_price' => (float)$cartItem->total_price,
        ], 'Item added to cart successfully.', 201);
    }

    /**
     * PUT /cart/items/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cartItem = CartItem::where('customer_id', $request->user()->id)->findOrFail($id);
        
        if ($cartItem->daily_meal_id) {
            $dailyMeal = DailyMeal::findOrFail($cartItem->daily_meal_id);
            $price = $dailyMeal->discount_price && $dailyMeal->discount_price > 0 
                ? $dailyMeal->discount_price 
                : $dailyMeal->price;
        } elseif ($cartItem->addon_id) {
            $addon = Addon::findOrFail($cartItem->addon_id);
            $price = $addon->price;
        } else {
            $menuItem = MenuItem::findOrFail($cartItem->menu_item_id);
            $price = $menuItem->active_price;
        }

        $cartItem->quantity = $request->input('quantity');
        $cartItem->unit_price = $price;
        $cartItem->total_price = round($cartItem->quantity * $price, 2);
        $cartItem->save();

        return $this->successResponse([
            'id' => $cartItem->id,
            'quantity' => $cartItem->quantity,
            'unit_price' => (float)$cartItem->unit_price,
            'total_price' => (float)$cartItem->total_price,
        ], 'Cart item updated successfully.');
    }

    /**
     * DELETE /cart/items/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $cartItem = CartItem::where('customer_id', $request->user()->id)->findOrFail($id);
        $cartItem->delete();

        return $this->successResponse(null, 'Item removed from cart successfully.');
    }

    /**
     * DELETE /cart
     */
    public function clear(Request $request): JsonResponse
    {
        CartItem::where('customer_id', $request->user()->id)->delete();

        return $this->successResponse(null, 'Cart cleared successfully.');
    }
}
