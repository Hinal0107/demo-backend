<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    /**
     * Display a listing of orders.
     */
    public function index(Request $request)
    {
        $restaurantId = $request->query('restaurant_id');
        $customerId = $request->query('customer_id');
        $orderStatus = $request->query('order_status');
        $deliveryStatus = $request->query('delivery_status');
        $paymentStatus = $request->query('payment_status');
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');
        $search = $request->query('search');

        $query = Order::with(['restaurant', 'customer']);

        if ($restaurantId) {
            $query->where('restaurant_id', $restaurantId);
        }

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        if ($orderStatus) {
            $query->where('order_status', strtoupper($orderStatus));
        }

        if ($deliveryStatus) {
            $query->where('delivery_status', strtoupper($deliveryStatus));
        }

        if ($paymentStatus) {
            $query->where('payment_status', strtoupper($paymentStatus));
        }

        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', '%' . $search . '%')
                  ->orWhereHas('customer', function ($sub) use ($search) {
                      $sub->where('name', 'like', '%' . $search . '%');
                  });
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(15);
        $restaurants = Restaurant::all();

        return view('admin.orders.index', compact('orders', 'restaurants'));
    }

    /**
     * Display order details.
     */
    public function show(int $id)
    {
        $order = Order::with(['items', 'restaurant', 'customer', 'address', 'statusHistories.changer', 'payments'])
            ->findOrFail($id);

        return view('admin.orders.show', compact('order'));
    }
}
