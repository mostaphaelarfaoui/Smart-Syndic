<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Get unread count and latest notifications.
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth('web')->user();
        if (! $user) {
            return response()->json(['unread_count' => 0, 'notifications' => []], 401);
        }

        $feed = $this->notificationService->getFeed($user, 30);

        return response()->json($feed);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $user = auth('web')->user();
        if (! $user) {
            return response()->json(['success' => false], 401);
        }

        $unreadCount = $this->notificationService->markAsRead($user, $id);

        return response()->json([
            'success'      => true,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = auth('web')->user();
        if (! $user) {
            return response()->json(['success' => false], 401);
        }

        $this->notificationService->markAllAsRead($user);

        return response()->json([
            'success'      => true,
            'unread_count' => 0,
        ]);
    }

    /**
     * Delete a single notification.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = auth('web')->user();
        if (! $user) {
            return response()->json(['success' => false], 401);
        }

        $unreadCount = $this->notificationService->delete($user, $id);

        return response()->json([
            'success'      => true,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Clear all notifications for user.
     */
    public function clearAll(Request $request): JsonResponse
    {
        $user = auth('web')->user();
        if (! $user) {
            return response()->json(['success' => false], 401);
        }

        $this->notificationService->clearAll($user);

        return response()->json([
            'success'      => true,
            'unread_count' => 0,
        ]);
    }
}
