<?php

namespace App\Http\Controllers\FCM;

use App\Http\Controllers\Controller;
use App\Models\UserDevice;
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

        // Clean up any existing records with the same FCM token to avoid unique constraint violations
        UserDevice::where('fcm_token', $token)->delete();

        $user = $request->user();
        if ($user) {
            $user->fcm_token = $token;
            $user->device_type = $deviceType;
            $user->save();
        }

        $fcm = UserDevice::updateOrCreate(
            [
                'user_id' => $userId,
                'device_id' => $deviceId,
            ],
            [
                'fcm_token' => $token,
                'device_type' => $deviceType,
                'is_active' => true,
                'last_login_at' => now(),
            ]
        );

        return $this->successResponse($fcm, 'FCM token registered successfully.');
    }

    /**
     * POST /api/v1/devices/register
     */
    public function register(Request $request): JsonResponse
    {
        return $this->store($request);
    }

    /**
     * POST /api/v1/devices/unregister
     */
    public function unregister(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $token = $request->input('fcm_token');
        $userId = $request->user()->id;

        UserDevice::where('fcm_token', $token)
            ->where('user_id', $userId)
            ->update(['is_active' => false]);

        return $this->successResponse(null, 'FCM token unregistered successfully.');
    }
}
