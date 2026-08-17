<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Restaurant;
use Illuminate\Http\Request;

class AdminSubscriptionController extends Controller
{
    /**
     * Display a listing of subscriptions.
     */
    public function index(Request $request)
    {
        $restaurantId = $request->query('restaurant_id');
        $status = $request->query('status');
        $search = $request->query('search');

        $query = Subscription::with(['restaurant', 'customer', 'plan']);

        if ($restaurantId) {
            $query->where('restaurant_id', $restaurantId);
        }

        if ($status) {
            $query->where('status', strtoupper($status));
        }

        if ($search) {
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            });
        }

        $subscriptions = $query->orderBy('created_at', 'desc')->paginate(15);
        $restaurants = Restaurant::all();

        return view('admin.subscriptions.index', compact('subscriptions', 'restaurants'));
    }

    /**
     * Show subscription details and generated daily orders.
     */
    public function show(int $id)
    {
        $subscription = Subscription::with(['restaurant', 'customer', 'plan', 'subscriptionOrders.order'])
            ->findOrFail($id);

        return view('admin.subscriptions.show', compact('subscription'));
    }
}
