<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Restaurant;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Subscription;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class RestaurantWebController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Show restaurant manager login view.
     */
    public function showLogin()
    {
        if (Auth::check() && Auth::user()->isRestaurant() && Auth::user()->restaurant) {
            return redirect()->route('restaurant.dashboard');
        }
        return view('restaurant.login');
    }

    /**
     * Handle login request.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            if ($user->isRestaurant() && $user->restaurant) {
                $request->session()->regenerate();
                return redirect()->route('restaurant.dashboard');
            }
            Auth::logout();
            return back()->withErrors(['email' => 'Access denied. Your account is not linked to any active restaurant.']);
        }

        return back()->withErrors(['email' => 'The provided credentials do not match our records.']);
    }

    /**
     * Log out manager.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('restaurant.login');
    }

    /**
     * Dashboard home page.
     */
    public function index()
    {
        $restaurant = Auth::user()->restaurant;
        $today = Carbon::today();

        $stats = [
            'today_orders' => $restaurant->orders()->whereDate('created_at', $today)->count(),
            'total_revenue' => $restaurant->orders()->where('payment_status', 'PAID')->sum('total_amount'),
            'active_menu_items' => $restaurant->menuItems()->where('status', 'ACTIVE')->count(),
            'active_subscriptions' => $restaurant->subscriptions()->where('status', 'ACTIVE')->count(),
        ];

        $recentOrders = $restaurant->orders()
            ->with('customer')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('restaurant.dashboard', compact('stats', 'recentOrders', 'restaurant'));
    }

    /**
     * Show restaurant profile.
     */
    public function showProfile()
    {
        $restaurant = Auth::user()->restaurant;
        return view('restaurant.profile', compact('restaurant'));
    }

    /**
     * Update profile details.
     */
    public function updateProfile(Request $request)
    {
        $restaurant = Auth::user()->restaurant;

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'description' => 'nullable|string|max:500',
            'address' => 'required|string|max:200',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'pincode' => 'required|string|max:15',
            'opening_time' => 'required',
            'closing_time' => 'required',
        ]);

        $restaurant->update($data);

        return back()->with('success', 'Restaurant profile updated successfully.');
    }

    /**
     * List categories.
     */
    public function categoriesIndex()
    {
        $restaurant = Auth::user()->restaurant;
        $categories = $restaurant->menuCategories()->orderBy('sort_order')->get();
        return view('restaurant.categories', compact('categories', 'restaurant'));
    }

    /**
     * Store new category.
     */
    public function categoriesStore(Request $request)
    {
        $restaurant = Auth::user()->restaurant;

        $data = $request->validate([
            'name' => 'required|string|max:50',
            'description' => 'nullable|string|max:200',
            'sort_order' => 'required|integer',
        ]);

        $restaurant->menuCategories()->create($data + ['status' => 'ACTIVE']);

        return back()->with('success', 'Menu category created successfully.');
    }

    /**
     * Update category.
     */
    public function categoriesUpdate(Request $request, $id)
    {
        $restaurant = Auth::user()->restaurant;
        $category = $restaurant->menuCategories()->findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:50',
            'description' => 'nullable|string|max:200',
            'sort_order' => 'required|integer',
            'status' => 'required|in:ACTIVE,INACTIVE',
        ]);

        $category->update($data);

        return back()->with('success', 'Menu category updated successfully.');
    }

    /**
     * Delete category.
     */
    public function categoriesDestroy($id)
    {
        $restaurant = Auth::user()->restaurant;
        $category = $restaurant->menuCategories()->findOrFail($id);

        // Delete menu items first or reject if has items
        if ($category->menuItems()->count() > 0) {
            return back()->withErrors(['category' => 'Cannot delete category that contains menu items.']);
        }

        $category->delete();
        return back()->with('success', 'Menu category deleted successfully.');
    }

    /**
     * List menu items.
     */
    public function menuItemsIndex()
    {
        $restaurant = Auth::user()->restaurant;
        $menuItems = $restaurant->menuItems()->with('category')->orderBy('sort_order')->get();
        $categories = $restaurant->menuCategories()->active()->orderBy('sort_order')->get();
        return view('restaurant.menu_items', compact('menuItems', 'categories', 'restaurant'));
    }

    /**
     * Store new menu item.
     */
    public function menuItemsStore(Request $request)
    {
        $restaurant = Auth::user()->restaurant;

        $data = $request->validate([
            'category_id' => 'required|exists:menu_categories,id',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:200',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0|lte:price',
            'veg_type' => 'required|in:VEG,NON_VEG,EGG',
            'availability' => 'required|boolean',
            'sort_order' => 'required|integer',
        ]);

        $restaurant->menuItems()->create($data + ['status' => 'ACTIVE']);

        return back()->with('success', 'Menu item added successfully.');
    }

    /**
     * Update menu item.
     */
    public function menuItemsUpdate(Request $request, $id)
    {
        $restaurant = Auth::user()->restaurant;
        $menuItem = $restaurant->menuItems()->findOrFail($id);

        $data = $request->validate([
            'category_id' => 'required|exists:menu_categories,id',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:200',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0|lte:price',
            'veg_type' => 'required|in:VEG,NON_VEG,EGG',
            'availability' => 'required|boolean',
            'status' => 'required|in:ACTIVE,INACTIVE',
            'sort_order' => 'required|integer',
        ]);

        $menuItem->update($data);

        return back()->with('success', 'Menu item updated successfully.');
    }

    /**
     * Delete menu item.
     */
    public function menuItemsDestroy($id)
    {
        $restaurant = Auth::user()->restaurant;
        $menuItem = $restaurant->menuItems()->findOrFail($id);
        $menuItem->delete();
        return back()->with('success', 'Menu item deleted successfully.');
    }

    /**
     * List orders.
     */
    public function ordersIndex()
    {
        $restaurant = Auth::user()->restaurant;
        $orders = $restaurant->orders()->with('customer')->orderBy('created_at', 'desc')->get();
        return view('restaurant.orders', compact('orders', 'restaurant'));
    }

    /**
     * Show order details.
     */
    public function ordersShow($id)
    {
        $restaurant = Auth::user()->restaurant;
        $order = $restaurant->orders()->with(['customer', 'orderItems', 'address'])->findOrFail($id);
        return view('restaurant.orders_show', compact('order', 'restaurant'));
    }

    /**
     * Update order status.
     */
    public function ordersUpdateStatus(Request $request, $id)
    {
        $restaurant = Auth::user()->restaurant;
        $order = $restaurant->orders()->findOrFail($id);

        $data = $request->validate([
            'order_status' => 'required|in:CONFIRMED,PREPARING,READY,CANCELLED',
            'delivery_status' => 'nullable|in:PENDING,OUT_FOR_DELIVERY,DELIVERED',
        ]);

        $order->update($data);

        // Notify client using custom events configured in NotificationService
        if (isset($data['order_status'])) {
            $event = null;
            switch ($data['order_status']) {
                case 'CONFIRMED':
                    $event = 'order_confirmed';
                    break;
                case 'PREPARING':
                    $event = 'order_preparing';
                    break;
                case 'READY':
                    $event = 'order_ready';
                    break;
                case 'CANCELLED':
                    $event = 'order_cancelled';
                    break;
            }
            if ($event) {
                $this->notificationService->sendOrderStatusNotification($order, $event);
            }
        }

        if (isset($data['delivery_status']) && $data['delivery_status'] !== 'PENDING') {
            $event = null;
            switch ($data['delivery_status']) {
                case 'OUT_FOR_DELIVERY':
                    $event = 'delivery_out_for_delivery';
                    break;
                case 'DELIVERED':
                    $event = 'delivery_delivered';
                    // Automatically mark order_status completed if delivered
                    $order->update(['order_status' => 'COMPLETED']);
                    break;
            }
            if ($event) {
                $this->notificationService->sendOrderStatusNotification($order, $event);
            }
        }

        return back()->with('success', 'Order status updated successfully.');
    }

    /**
     * List active subscriptions.
     */
    public function subscriptionsIndex()
    {
        $restaurant = Auth::user()->restaurant;
        $subscriptions = $restaurant->subscriptions()
            ->with(['customer', 'plan'])
            ->orderBy('created_at', 'desc')
            ->get();
        return view('restaurant.subscriptions', compact('subscriptions', 'restaurant'));
    }
}
