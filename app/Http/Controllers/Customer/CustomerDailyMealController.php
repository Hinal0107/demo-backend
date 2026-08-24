<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\DailyMeal;
use App\Models\Restaurant;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class CustomerDailyMealController extends Controller
{
    use ApiResponseTrait;

    public function todayMeal(int $restaurantId): JsonResponse
    {
        $restaurant = Restaurant::active()->findOrFail($restaurantId);
        $meal = DailyMeal::active()
            ->where('restaurant_id', $restaurantId)
            ->whereDate('date', Carbon::today())
            ->first();

        if (!$meal) {
            return $this->successResponse(null, 'No meal scheduled for today.');
        }

        return $this->successResponse($meal, 'Today\'s meal fetched successfully.');
    }

    public function tomorrowMeal(int $restaurantId): JsonResponse
    {
        $restaurant = Restaurant::active()->findOrFail($restaurantId);
        $meal = DailyMeal::active()
            ->where('restaurant_id', $restaurantId)
            ->whereDate('date', Carbon::tomorrow())
            ->first();

        if (!$meal) {
            return $this->successResponse(null, 'No meal scheduled for tomorrow.');
        }

        return $this->successResponse($meal, 'Tomorrow\'s meal fetched successfully.');
    }
}
