<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationApiController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Get feed and unread count for current authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $feed = $this->notificationService->getFeed($user, 30);

        return response()->json($feed);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $unreadCount = $this->notificationService->markAsRead($user, $id);

        return response()->json([
            'success'      => true,
            'unread_count' => $unreadCount,
            'message'      => 'تم تحديد الإشعار كمقروء.',
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->notificationService->markAllAsRead($user);

        return response()->json([
            'success'      => true,
            'unread_count' => 0,
            'message'      => 'تم تحديد كافة الإشعارات كمقروءة.',
        ]);
    }

    /**
     * Delete a notification.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $unreadCount = $this->notificationService->delete($user, $id);

        return response()->json([
            'success'      => true,
            'unread_count' => $unreadCount,
            'message'      => 'تم حذف الإشعار من السجل.',
        ]);
    }

    /**
     * Clear all notifications.
     */
    public function clearAll(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->notificationService->clearAll($user);

        return response()->json([
            'success'      => true,
            'unread_count' => 0,
            'message'      => 'تم مسح كافة الإشعارات.',
        ]);
    }
}
