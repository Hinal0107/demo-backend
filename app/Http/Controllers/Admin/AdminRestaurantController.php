<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Services\AdminActivityLoggerService;
use App\Services\RestaurantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminRestaurantController extends Controller
{
    protected AdminActivityLoggerService $activityLogger;
    protected RestaurantService $restaurantService;

    public function __construct(AdminActivityLoggerService $activityLogger, RestaurantService $restaurantService)
    {
        $this->activityLogger = $activityLogger;
        $this->restaurantService = $restaurantService;
    }

    /**
     * Display a listing of restaurants.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $status = $request->query('status');

        $query = Restaurant::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('city', 'like', '%' . $search . '%')
                  ->orWhere('pincode', 'like', '%' . $search . '%');
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        $restaurants = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('admin.restaurants.index', compact('restaurants'));
    }

    /**
     * Show details of a specific restaurant.
     */
    public function show(int $id)
    {
        $restaurant = Restaurant::with(['users', 'orders', 'payments'])->findOrFail($id);
        
        // Calculate revenue
        $totalRevenue = $restaurant->payments()->where('status', 'PAID')->sum('amount');
        
        return view('admin.restaurants.show', compact('restaurant', 'totalRevenue'));
    }

    /**
     * Show form to create restaurant.
     */
    public function create()
    {
        return view('admin.restaurants.create');
    }

    /**
     * Store new restaurant.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:restaurants,email',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'pincode' => 'required|string|max:20',
            'opening_time' => 'required|date_format:H:i',
            'closing_time' => 'required|date_format:H:i',
            'status' => 'required|in:ACTIVE,INACTIVE,PENDING_APPROVAL',
        ]);

        // Fix date formats for standard MySQL Time columns
        $validated['opening_time'] .= ':00';
        $validated['closing_time'] .= ':00';

        $restaurant = Restaurant::create($validated);

        $this->activityLogger->log(
            Auth::id(),
            'CREATE_RESTAURANT',
            'restaurants',
            $restaurant->id,
            null,
            $restaurant->toArray()
        );

        return redirect()->route('admin.restaurants.show', $restaurant->id)
            ->with('success', 'Restaurant created successfully.');
    }

    /**
     * Show form to edit restaurant.
     */
    public function edit(int $id)
    {
        $restaurant = Restaurant::findOrFail($id);
        return view('admin.restaurants.edit', compact('restaurant'));
    }

    /**
     * Update restaurant details.
     */
    public function update(Request $request, int $id)
    {
        $restaurant = Restaurant::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:restaurants,email,' . $restaurant->id,
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'pincode' => 'required|string|max:20',
            'opening_time' => 'required|date_format:H:i',
            'closing_time' => 'required|date_format:H:i',
            'status' => 'required|in:ACTIVE,INACTIVE,BLOCKED,PENDING_APPROVAL',
        ]);

        $oldData = $restaurant->toArray();

        // Convert opening/closing times
        $validated['opening_time'] .= ':00';
        $validated['closing_time'] .= ':00';

        $restaurant->update($validated);

        $this->activityLogger->log(
            Auth::id(),
            'UPDATE_RESTAURANT',
            'restaurants',
            $restaurant->id,
            $oldData,
            $restaurant->toArray()
        );

        return redirect()->route('admin.restaurants.show', $restaurant->id)
            ->with('success', 'Restaurant details updated successfully.');
    }

    /**
     * Change restaurant status directly.
     */
    public function updateStatus(Request $request, int $id)
    {
        $request->validate(['status' => 'required|in:ACTIVE,INACTIVE,BLOCKED,PENDING_APPROVAL']);

        $restaurant = Restaurant::findOrFail($id);
        $oldData = $restaurant->toArray();
        
        $restaurant->status = $request->input('status');
        $restaurant->save();

        $this->activityLogger->log(
            Auth::id(),
            'UPDATE_RESTAURANT_STATUS',
            'restaurants',
            $restaurant->id,
            ['status' => $oldData['status']],
            ['status' => $restaurant->status]
        );

        return back()->with('success', "Restaurant status updated to {$restaurant->status}.");
    }

    /**
     * Delete restaurant.
     */
    public function destroy(int $id)
    {
        $restaurant = Restaurant::findOrFail($id);
        $oldData = $restaurant->toArray();

        $restaurant->delete();

        $this->activityLogger->log(
            Auth::id(),
            'DELETE_RESTAURANT',
            'restaurants',
            $id,
            $oldData,
            ['deleted' => true]
        );

        return redirect()->route('admin.restaurants.index')
            ->with('success', 'Restaurant soft-deleted successfully.');
    }
}
