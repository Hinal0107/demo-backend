<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'restaurant_id' => 'required|integer|exists:restaurants,id',
            'address_id' => 'required|integer|exists:addresses,id',
            'items' => 'required|array|min:1',
            'items.*.menu_item_id' => 'required_without:items.*.addon_id|nullable|integer|exists:menu_items,id',
            'items.*.addon_id' => 'required_without:items.*.menu_item_id|nullable|integer|exists:addons,id',
            'items.*.quantity' => 'required|integer|min:1',
            'scheduled_date' => 'nullable|date|after_or_equal:today',
            'notes' => 'nullable|string|max:500',
        ];
    }
}
