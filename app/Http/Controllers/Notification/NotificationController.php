<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    use ApiResponseTrait;

    /**
     * GET /api/v1/notifications
     */
    public function index(Request $request): JsonResponse
    {
        $limit = $request->query('limit', 20);

        $notifications = Notification::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate($limit);

        return $this->paginatedResponse(
            NotificationResource::collection($notifications),
            'Notifications fetched successfully.'
        );
    }

    /**
     * POST /api/v1/notifications/{id}/read
     */
    public function read(Request $request, int $id): JsonResponse
    {
        $notification = Notification::where('user_id', $request->user()->id)->findOrFail($id);
        $notification->read_at = now();
        $notification->save();

        return $this->successResponse(
            new NotificationResource($notification),
            'Notification marked as read.'
        );
    }

    /**
     * POST /api/v1/notifications/read-all
     */
    public function readAll(Request $request): JsonResponse
    {
        Notification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->successResponse(null, 'All notifications marked as read.');
    }
}
