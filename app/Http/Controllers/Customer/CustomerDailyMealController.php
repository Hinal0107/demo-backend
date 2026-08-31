<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\DailyMeal;
use App\Models\Restaurant;
use App\Models\Addon;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerDailyMealController extends Controller
{
    use ApiResponseTrait;

    public function todayMeal(int $restaurantId): JsonResponse
    {
        $restaurant = Restaurant::active()->findOrFail($restaurantId);

        // 1. Try meal_type 'TODAY' on today's date
        $meal = DailyMeal::active()
            ->where('restaurant_id', $restaurantId)
            ->where('meal_type', 'TODAY')
            ->whereDate('date', Carbon::today())
            ->orderBy('id', 'desc')
            ->first();

        // 2. Fallback to any active meal_type 'TODAY' for this restaurant
        if (!$meal) {
            $meal = DailyMeal::active()
                ->where('restaurant_id', $restaurantId)
                ->where('meal_type', 'TODAY')
                ->orderBy('id', 'desc')
                ->first();
        }

        // 3. Fallback to any active meal scheduled for today's date
        if (!$meal) {
            $meal = DailyMeal::active()
                ->where('restaurant_id', $restaurantId)
                ->whereDate('date', Carbon::today())
                ->whereNotIn('meal_type', ['WEEKLY', 'TOMORROW', 'ADDON'])
                ->orderBy('id', 'desc')
                ->first();
        }

        // 4. Fallback to the latest active non-addon daily meal
        if (!$meal) {
            $meal = DailyMeal::active()
                ->where('restaurant_id', $restaurantId)
                ->whereNotIn('meal_type', ['WEEKLY', 'TOMORROW', 'ADDON'])
                ->orderBy('id', 'desc')
                ->first();
        }

        if (!$meal) {
            return $this->successResponse(null, 'No meal scheduled for today.');
        }

        return $this->successResponse($this->formatMealResponse($meal), 'Today\'s meal fetched successfully.');
    }

    public function tomorrowMeal(int $restaurantId): JsonResponse
    {
        $restaurant = Restaurant::active()->findOrFail($restaurantId);

        // 1. Try meal_type 'TOMORROW' on tomorrow's date
        $meal = DailyMeal::active()
            ->where('restaurant_id', $restaurantId)
            ->where('meal_type', 'TOMORROW')
            ->whereDate('date', Carbon::tomorrow())
            ->orderBy('id', 'desc')
            ->first();

        // 2. Fallback to any active meal_type 'TOMORROW' for this restaurant
        if (!$meal) {
            $meal = DailyMeal::active()
                ->where('restaurant_id', $restaurantId)
                ->where('meal_type', 'TOMORROW')
                ->orderBy('id', 'desc')
                ->first();
        }

        // 3. Fallback to any active meal scheduled for tomorrow's date
        if (!$meal) {
            $meal = DailyMeal::active()
                ->where('restaurant_id', $restaurantId)
                ->whereDate('date', Carbon::tomorrow())
                ->whereNotIn('meal_type', ['WEEKLY', 'TODAY', 'ADDON'])
                ->orderBy('id', 'desc')
                ->first();
        }

        // 4. Fallback to second latest or any available daily meal distinct from today's
        if (!$meal) {
            $todayMeal = DailyMeal::active()
                ->where('restaurant_id', $restaurantId)
                ->where('meal_type', 'TODAY')
                ->orderBy('id', 'desc')
                ->first();

            $query = DailyMeal::active()
                ->where('restaurant_id', $restaurantId)
                ->whereNotIn('meal_type', ['WEEKLY', 'ADDON']);

            if ($todayMeal) {
                $query->where('id', '!=', $todayMeal->id);
            }

            $meal = $query->orderBy('id', 'desc')->first();
        }

        if (!$meal) {
            return $this->successResponse(null, 'No meal scheduled for tomorrow.');
        }

        return $this->successResponse($this->formatMealResponse($meal), 'Tomorrow\'s meal fetched successfully.');
    }

    public function weeklyMeal(int $restaurantId): JsonResponse
    {
        $restaurant = Restaurant::active()->findOrFail($restaurantId);
        $meal = DailyMeal::active()
            ->where('restaurant_id', $restaurantId)
            ->where('meal_type', 'WEEKLY')
            ->orderBy('id', 'desc')
            ->first();

        if (!$meal) {
            return $this->successResponse(null, 'No weekly meal scheduled.');
        }

        return $this->successResponse($this->formatMealResponse($meal), 'Weekly meal fetched successfully.');
    }

    public function index(int $restaurantId, Request $request): JsonResponse
    {
        $restaurant = Restaurant::active()->findOrFail($restaurantId);
        
        $query = DailyMeal::active()->where('restaurant_id', $restaurantId);

        if ($request->has('date')) {
            $query->whereDate('date', $request->query('date'));
        }

        $meals = $query->orderBy('id', 'desc')
            ->get()
            ->map(function ($meal) {
                return $this->formatMealResponse($meal);
            });

        return $this->successResponse($meals, 'Meals fetched successfully.');
    }

    private function formatMealResponse($meal)
    {
        if (!$meal) {
            return null;
        }

        $addonIds = is_array($meal->addons) ? $meal->addons : [];
        $addonsList = [];
        
        if (!empty($addonIds)) {
            $addonsList = Addon::active()
                ->whereIn('id', $addonIds)
                ->get();
        }

        $mealArray = $meal->toArray();
        $mealArray['addons'] = $addonsList;

        return $mealArray;
    }
}
