<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubscriptionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0',
            'duration_value' => 'required|integer|min:1',
            'duration_type' => 'required|string|in:DAY,WEEK,MONTH,CUSTOM,day,week,month,custom',
            'meal_type' => 'required|string|max:100', // e.g. lunch, dinner
            'meals_per_day' => 'nullable|integer|min:1',
            'total_meals' => 'required|integer|min:1',
            'delivery_frequency' => 'required|string|max:100', // e.g. daily, weekdays
            'start_date' => 'nullable|date',
            'status' => 'nullable|string|in:ACTIVE,INACTIVE',
        ];
    }
}
