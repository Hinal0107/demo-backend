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
        $cartItems = CartItem::with('menuItem')
            ->where('customer_id', $request->user()->id)
            ->get();

        $subtotal = 0.00;
        foreach ($cartItems as $item) {
            $subtotal += (float)$item->total_price;
        }

        return $this->successResponse([
            'items' => $cartItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'restaurant_id' => $item->restaurant_id,
                    'menu_item_id' => $item->menu_item_id,
                    'name' => $item->menuItem?->name,
                    'image' => $item->menuItem?->image,
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
            'menu_item_id' => 'required|integer|exists:menu_items,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $customerId = $request->user()->id;
        $restaurantId = $request->input('restaurant_id');
        $menuItemId = $request->input('menu_item_id');
        $qty = $request->input('quantity');

        // Verify menu item belongs to restaurant
        $menuItem = MenuItem::active()->findOrFail($menuItemId);
        if ((int)$menuItem->restaurant_id !== (int)$restaurantId) {
            return $this->errorResponse('Selected item does not belong to this restaurant.', 422);
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
        $cartItem = CartItem::where('customer_id', $customerId)
            ->where('menu_item_id', $menuItemId)
            ->first();

        $price = $menuItem->active_price;

        if ($cartItem) {
            $cartItem->quantity += $qty;
            $cartItem->total_price = $cartItem->quantity * $price;
            $cartItem->save();
        } else {
            $cartItem = CartItem::create([
                'customer_id' => $customerId,
                'restaurant_id' => $restaurantId,
                'menu_item_id' => $menuItemId,
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
        
        $menuItem = MenuItem::findOrFail($cartItem->menu_item_id);
        $price = $menuItem->active_price;

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
