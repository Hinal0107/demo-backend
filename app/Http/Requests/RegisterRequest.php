<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone' => 'required|string|max:20|unique:users,phone',
            'firebase_uid' => 'required|string|unique:users,firebase_uid',
            'role' => 'required|string|in:customer,restaurant',
            // Optional Restaurant Details during registration
            'restaurant_name' => 'required_if:role,restaurant|string|max:255',
            'restaurant_address' => 'required_if:role,restaurant|string|max:255',
            'restaurant_city' => 'required_if:role,restaurant|string|max:100',
            'restaurant_state' => 'required_if:role,restaurant|string|max:100',
            'restaurant_country' => 'required_if:role,restaurant|string|max:100',
            'restaurant_pincode' => 'required_if:role,restaurant|string|max:20',
            // Bank Details
            'bank_account_holder' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_ifsc' => 'nullable|string|max:20',
            'bank_branch' => 'nullable|string|max:255',
        ];
    }
}
