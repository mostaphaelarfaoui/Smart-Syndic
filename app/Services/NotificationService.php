<?php

namespace App\Services;

use App\Models\PlatformAnnouncement;
use App\Models\User;
use App\Notifications\AppSystemNotification;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * Send a notification to a specific user.
     */
    public function send(
        User $user,
        string $title,
        string $message,
        string $type = 'info',
        ?string $url = null,
        ?string $icon = null,
        array $extra = []
    ): void {
        $user->notify(new AppSystemNotification(
            title: $title,
            message: $message,
            type: $type,
            url: $url,
            icon: $icon,
            extra: $extra
        ));
    }

    /**
     * Send notification to all syndics of a tenant.
     */
    public function sendToTenantSyndics(
        string $tenantId,
        string $title,
        string $message,
        string $type = 'info',
        ?string $url = null,
        ?string $icon = null
    ): void {
        $syndics = User::where('tenant_id', $tenantId)
            ->whereIn('role', ['ADMIN_SYNDIC', 'SYNDIC'])
            ->where('status', 'ACTIVE')
            ->get();

        foreach ($syndics as $syndic) {
            $this->send($syndic, $title, $message, $type, $url, $icon);
        }
    }

    /**
     * Send notification to all super admins.
     */
    public function sendToSuperAdmins(
        string $title,
        string $message,
        string $type = 'info',
        ?string $url = null,
        ?string $icon = null
    ): void {
        $admins = User::where('role', 'SUPER_ADMIN')
            ->where('status', 'ACTIVE')
            ->get();

        foreach ($admins as $admin) {
            $this->send($admin, $title, $message, $type, $url, $icon);
        }
    }

    /**
     * Synchronize active global platform announcements into the user's notifications.
     */
    public function syncAnnouncements(User $user): void
    {
        $announcements = PlatformAnnouncement::where('is_active', true)
            ->where(function ($q) use ($user) {
                $q->where('target_audience', 'ALL');
                if ($user->isSuperAdmin()) {
                    $q->orWhere('target_audience', 'SUPERADMIN');
                } else {
                    $q->orWhere('target_audience', 'SYNDIC');
                }
            })
            ->get();

        foreach ($announcements as $ann) {
            // Check if already notified for this announcement
            $existing = $user->notifications()
                ->where('data->extra->announcement_id', $ann->id)
                ->first();

            if (! $existing) {
                $icon = match (strtoupper($ann->type)) {
                    'DANGER'  => '🚨',
                    'WARNING' => '⚠️',
                    'SUCCESS' => '✨',
                    default   => '📢',
                };

                $type = strtolower($ann->type);

                $this->send(
                    user: $user,
                    title: $ann->title,
                    message: $ann->message,
                    type: $type,
                    url: null,
                    icon: $icon,
                    extra: ['announcement_id' => $ann->id]
                );
            }
        }
    }

    /**
     * Get formatted notifications payload for dropdown.
     */
    public function getFeed(User $user, int $limit = 25): array
    {
        $this->syncAnnouncements($user);

        $notifications = $user->notifications()->take($limit)->get();
        $unreadCount = $user->unreadNotifications()->count();

        $formatted = $notifications->map(function ($n) {
            $data = is_array($n->data) ? $n->data : json_decode($n->data, true);
            $createdAt = Carbon::parse($n->created_at);

            return [
                'id'         => $n->id,
                'title'      => $data['title'] ?? 'إشعار جديد',
                'message'    => $data['message'] ?? '',
                'type'       => $data['type'] ?? 'info',
                'icon'       => $data['icon'] ?? '🔔',
                'url'        => $data['url'] ?? null,
                'is_read'    => ! is_null($n->read_at),
                'read_at'    => $n->read_at?->toIso8601String(),
                'time_ago'   => $createdAt->locale('ar')->diffForHumans(),
                'created_at' => $createdAt->toIso8601String(),
            ];
        });

        return [
            'unread_count'  => $unreadCount,
            'notifications' => $formatted,
        ];
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(User $user, string $id): int
    {
        $notification = $user->notifications()->where('id', $id)->first();
        if ($notification && is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        return $user->unreadNotifications()->count();
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllAsRead(User $user): int
    {
        $user->unreadNotifications->markAsRead();
        return 0;
    }

    /**
     * Delete a single notification.
     */
    public function delete(User $user, string $id): int
    {
        $user->notifications()->where('id', $id)->delete();
        return $user->unreadNotifications()->count();
    }

    /**
     * Clear all notifications for user.
     */
    public function clearAll(User $user): int
    {
        $user->notifications()->delete();
        return 0;
    }
}
