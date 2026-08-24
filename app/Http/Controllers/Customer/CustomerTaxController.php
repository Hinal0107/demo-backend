<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Tax;
use App\Models\Restaurant;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class CustomerTaxController extends Controller
{
    use ApiResponseTrait;

    public function index(int $restaurantId): JsonResponse
    {
        $restaurant = Restaurant::active()->findOrFail($restaurantId);
        $taxes = Tax::active()
            ->where('restaurant_id', $restaurantId)
            ->get();

        return $this->successResponse($taxes, 'Taxes fetched successfully.');
    }
}
