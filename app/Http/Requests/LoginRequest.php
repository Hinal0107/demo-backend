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
            'firebase_uid' => 'required|string',
            'device_type' => 'required|string|in:android,ios,web,unknown',
            'fcm_token' => 'nullable|string',
            'device_id' => 'nullable|string',
        ];
    }
}
