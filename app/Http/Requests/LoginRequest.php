<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required|string',
            'device_type' => 'nullable|string|in:android,ios',
            'device_id' => 'nullable|string',
            'fcm_token' => 'nullable|string',
        ];
    }
}
