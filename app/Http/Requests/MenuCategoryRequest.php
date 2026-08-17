<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MenuCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'image' => 'nullable|file|image|mimes:jpeg,jpg,png,webp|max:5120',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'nullable|string|in:ACTIVE,INACTIVE',
        ];
    }
}
