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
     * POST /api/v1/notifications/read
     * POST /api/v1/notifications/{id}/read
     */
    public function read(Request $request, ?int $id = null): JsonResponse
    {
        $notificationId = $id ?? $request->input('notification_id');

        if (!$notificationId) {
            return $this->errorResponse('Notification ID is required.', 422);
        }

        $notification = Notification::where('user_id', $request->user()->id)->findOrFail($notificationId);
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

    /**
     * GET /api/v1/notifications/unread-count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = Notification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        return $this->successResponse([
            'unread_count' => $count,
        ], 'Unread notification count fetched successfully.');
    }

    /**
     * POST /api/v1/notifications
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string',
            'body' => 'nullable|string',
            'message' => 'nullable|string',
            'role' => 'nullable|string',
        ]);

        $notificationService = app(\App\Services\NotificationService::class);
        $user = $request->user();
        $title = $request->input('title');
        $message = $request->input('body') ?? $request->input('message') ?? '';
        $data = $request->input('data', []);

        $notification = $notificationService->sendNotification($user, 'general', $title, $message, $data);

        return $this->successResponse(
            new NotificationResource($notification),
            'Notification created successfully.'
        );
    }
}
