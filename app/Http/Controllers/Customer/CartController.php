<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\MenuItem;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    use ApiResponseTrait;

    /**
     * GET /cart
     */
    public function index(Request $request): JsonResponse
    {
        $cartItems = CartItem::with(['menuItem', 'addon'])
            ->where('customer_id', $request->user()->id)
            ->get();

        $subtotal = 0.00;
        foreach ($cartItems as $item) {
            $subtotal += (float)$item->total_price;
        }

        return $this->successResponse([
            'items' => $cartItems->map(function ($item) {
                $name = $item->menu_item_id ? $item->menuItem?->name : $item->addon?->name;
                $image = $item->menu_item_id ? $item->menuItem?->image : $item->addon?->image;
                return [
                    'id' => $item->id,
                    'restaurant_id' => $item->restaurant_id,
                    'menu_item_id' => $item->menu_item_id,
                    'addon_id' => $item->addon_id,
                    'name' => $name,
                    'image' => $image,
                    'quantity' => $item->quantity,
                    'unit_price' => (float)$item->unit_price,
                    'total_price' => (float)$item->total_price,
                ];
            }),
            'subtotal' => $subtotal,
            'restaurant_id' => $cartItems->first()?->restaurant_id,
        ], 'Cart fetched successfully.');
    }

    /**
     * POST /cart/items
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'restaurant_id' => 'required|integer|exists:restaurants,id',
            'menu_item_id' => 'nullable|integer|exists:menu_items,id',
            'addon_id' => 'nullable|integer|exists:addons,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $customerId = $request->user()->id;
        $restaurantId = $request->input('restaurant_id');
        $menuItemId = $request->input('menu_item_id');
        $addonId = $request->input('addon_id');
        $qty = $request->input('quantity');

        if (!$menuItemId && !$addonId) {
            return $this->errorResponse('Either menu_item_id or addon_id is required.', 422);
        }

        // Verify item belongs to restaurant and fetch price
        if ($addonId) {
            $addon = \App\Models\Addon::active()->findOrFail($addonId);
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
        if ($addonId) {
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
            $cartItem->total_price = $cartItem->quantity * $price;
            $cartItem->save();
        } else {
            $cartItem = CartItem::create([
                'customer_id' => $customerId,
                'restaurant_id' => $restaurantId,
                'menu_item_id' => $menuItemId,
                'addon_id' => $addonId,
                'quantity' => $qty,
                'unit_price' => $price,
                'total_price' => $qty * $price,
            ]);
        }

        return $this->successResponse([
            'id' => $cartItem->id,
            'quantity' => $cartItem->quantity,
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
        
        if ($cartItem->addon_id) {
            $addon = \App\Models\Addon::findOrFail($cartItem->addon_id);
            $price = $addon->price;
        } else {
            $menuItem = MenuItem::findOrFail($cartItem->menu_item_id);
            $price = $menuItem->active_price;
        }

        $cartItem->quantity = $request->input('quantity');
        $cartItem->total_price = $cartItem->quantity * $price;
        $cartItem->save();

        return $this->successResponse([
            'id' => $cartItem->id,
            'quantity' => $cartItem->quantity,
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
