<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = auth()->id();
        return [
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20|unique:users,phone,' . $userId,
            'profile_image' => 'nullable|file|image|mimes:jpeg,jpg,png,webp,heic|max:10240',
            'avatar' => 'nullable|file|image|mimes:jpeg,jpg,png,webp,heic|max:10240',
        ];
    }
}
