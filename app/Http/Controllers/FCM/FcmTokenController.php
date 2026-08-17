<?php

namespace App\Http\Controllers\FCM;

use App\Http\Controllers\Controller;
use App\Models\FcmToken;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FcmTokenController extends Controller
{
    use ApiResponseTrait;

    /**
     * POST /api/v1/notifications/token
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => 'required|string',
            'device_type' => 'required|string|in:android,ios,web,unknown',
            'device_id' => 'required|string',
        ]);

        $userId = $request->user()->id;
        $token = $request->input('fcm_token');
        $deviceType = $request->input('device_type');
        $deviceId = $request->input('device_id');

        // Prevent duplicate tokens across users or device IDs
        // Clean up tokens that are registerd to other users but match the token string
        FcmToken::where('token', $token)->where('user_id', '!=', $userId)->delete();

        $fcm = FcmToken::updateOrCreate(
            [
                'user_id' => $userId,
                'device_id' => $deviceId,
            ],
            [
                'token' => $token,
                'device_type' => $deviceType,
                'status' => 'ACTIVE',
                'last_used_at' => now(),
            ]
        );

        return $this->successResponse($fcm, 'FCM token registered successfully.');
    }
}
