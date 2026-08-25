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
        $meal = DailyMeal::active()
            ->where('restaurant_id', $restaurantId)
            ->where('meal_type', 'TODAY')
            ->whereDate('date', Carbon::today())
            ->orderBy('id', 'desc')
            ->first();

        if (!$meal) {
            // Fallback to date if no meal_type 'TODAY' is explicitly scheduled
            $meal = DailyMeal::active()
                ->where('restaurant_id', $restaurantId)
                ->whereDate('date', Carbon::today())
                ->where('meal_type', '!=', 'WEEKLY')
                ->where('meal_type', '!=', 'TOMORROW')
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
        $meal = DailyMeal::active()
            ->where('restaurant_id', $restaurantId)
            ->where('meal_type', 'TOMORROW')
            ->whereDate('date', Carbon::tomorrow())
            ->orderBy('id', 'desc')
            ->first();

        if (!$meal) {
            // Fallback to date if no meal_type 'TOMORROW' is explicitly scheduled
            $meal = DailyMeal::active()
                ->where('restaurant_id', $restaurantId)
                ->whereDate('date', Carbon::tomorrow())
                ->where('meal_type', '!=', 'WEEKLY')
                ->where('meal_type', '!=', 'TODAY')
                ->orderBy('id', 'desc')
                ->first();
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
        
        $date = $request->query('date', Carbon::today()->toDateString());
        
        $meals = DailyMeal::active()
            ->where('restaurant_id', $restaurantId)
            ->whereDate('date', $date)
            ->orderBy('id', 'desc')
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
