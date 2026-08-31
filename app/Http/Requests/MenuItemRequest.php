<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => 'required|integer|exists:menu_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|file|image|mimes:jpeg,jpg,png,webp|max:5120',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0|lt:price',
            'veg_type' => 'required|string|in:VEG,NON_VEG,JAIN,veg,non_veg,jain',
            'availability' => 'nullable|boolean',
            'status' => 'nullable|string|in:ACTIVE,INACTIVE',
            'sort_order' => 'nullable|integer|min:0',
            'is_addon' => 'nullable',
        ];
    }
}
