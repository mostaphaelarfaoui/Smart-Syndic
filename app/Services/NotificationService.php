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
        $category = $extra['category'] ?? null;
        if ($category && ! $user->wantsNotification($category)) {
            return;
        }

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
        ?string $icon = null,
        array $extra = []
    ): void {
        $syndics = User::where('tenant_id', $tenantId)
            ->whereIn('role', ['ADMIN_SYNDIC', 'SYNDIC'])
            ->where('status', 'ACTIVE')
            ->get();

        foreach ($syndics as $syndic) {
            $this->send($syndic, $title, $message, $type, $url, $icon, $extra);
        }
    }

    /**
     * Send notification to all active residents of a tenant.
     */
    public function sendToTenantResidents(
        string $tenantId,
        string $title,
        string $message,
        string $type = 'info',
        ?string $url = null,
        ?string $icon = null,
        array $extra = []
    ): void {
        $residents = User::where('tenant_id', $tenantId)
            ->where('role', 'RESIDENT')
            ->where('status', 'ACTIVE')
            ->get();

        foreach ($residents as $resident) {
            $this->send($resident, $title, $message, $type, $url, $icon, $extra);
        }
    }

    /**
     * Send notification to all active members of a tenant (syndics + residents).
     */
    public function sendToTenantAll(
        string $tenantId,
        string $title,
        string $message,
        string $type = 'info',
        ?string $url = null,
        ?string $icon = null,
        array $extra = []
    ): void {
        $users = User::where('tenant_id', $tenantId)
            ->where('status', 'ACTIVE')
            ->get();

        foreach ($users as $user) {
            $this->send($user, $title, $message, $type, $url, $icon, $extra);
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
        ?string $icon = null,
        array $extra = []
    ): void {
        $admins = User::where('role', 'SUPER_ADMIN')
            ->where('status', 'ACTIVE')
            ->get();

        foreach ($admins as $admin) {
            $this->send($admin, $title, $message, $type, $url, $icon, $extra);
        }
    }

    /**
     * Synchronize active global platform announcements into notifications.
     * Note: Announcements are now delivered exclusively through the dedicated Messaging System.
     */
    public function syncAnnouncements(User $user): void
    {
        // Deprecated: Announcements are now displayed exclusively in the Message Center / Message Bell
    }

    /**
     * Get formatted notifications payload for dropdown.
     */
    public function getFeed(User $user, int $limit = 25): array
    {
        $notifications = $user->notifications()->take($limit)->get();
        $unreadCount = $user->unreadNotifications()->count();

        $formatted = $notifications->map(function ($n) {
            $data = is_array($n->data) ? $n->data : json_decode($n->data, true);
            $createdAt = Carbon::parse($n->created_at);

            $extra = is_array($data['extra'] ?? null) ? $data['extra'] : [];
            $targetScreen = $data['target_screen'] ?? ($extra['target_screen'] ?? null);
            $targetId = $data['target_id'] ?? ($extra['target_id'] ?? null);

            $actorName = $extra['actor_name'] ?? null;
            $actorRole = $extra['actor_role'] ?? null;
            $unitNumber = $extra['unit_number'] ?? null;
            $buildingName = $extra['building_name'] ?? null;
            $recipientName = $extra['recipient_name'] ?? null;
            $recipientLabel = $extra['recipient_label'] ?? null;

            // Intelligent resolution for legacy or newly queued events
            if (! $actorName && $targetScreen === 'pending_residents' && $targetId) {
                $resUser = User::with('apartments.building')->find($targetId);
                if ($resUser) {
                    $actorName = $resUser->full_name;
                    $actorRole = 'ساكن جديد';
                    $apt = $resUser->apartments->first();
                    $unitNumber = $unitNumber ?: $apt?->clean_unit_number;
                    $buildingName = $buildingName ?: $apt?->building?->name;
                }
            } elseif (! $recipientName && $targetScreen === 'payments' && $targetId) {
                $payment = \App\Models\Payment::with(['apartment.residents', 'apartment.building'])->find($targetId);
                if ($payment) {
                    $actorName = $actorName ?: 'إدارة السانديك';
                    $actorRole = $actorRole ?: 'سانديك';
                    $res = $payment->apartment?->residents?->first();
                    $recipientName = $res?->full_name;
                    $unitNumber = $unitNumber ?: $payment->apartment?->clean_unit_number;
                    $buildingName = $buildingName ?: $payment->apartment?->building?->name;
                }
            } elseif (! $actorName && ($targetScreen === 'community' || $targetScreen === 'suggestions') && $targetId) {
                $suggestion = \App\Models\Suggestion::with(['user.apartments.building', 'apartment.building'])->find($targetId);
                if ($suggestion) {
                    $actorName = $suggestion->user?->full_name;
                    $actorRole = 'ساكن';
                    $apt = $suggestion->apartment ?: $suggestion->user?->apartments?->first();
                    $unitNumber = $unitNumber ?: $apt?->clean_unit_number;
                    $buildingName = $buildingName ?: $apt?->building?->name;
                    $recipientName = 'إدارة السانديك';
                }
            } elseif ($targetScreen === 'polls') {
                $actorName = $actorName ?: 'إدارة السانديك';
                $actorRole = $actorRole ?: 'سانديك';
                $recipientLabel = $recipientLabel ?: 'كافة سكان الإقامة';
            } elseif (! empty($extra['announcement_id'])) {
                $actorName = $actorName ?: 'الإدارة العامة للمنصة';
                $actorRole = $actorRole ?: 'إعلان عام';
                $recipientLabel = $recipientLabel ?: 'كافة سكان الإقامة';
            }

            // Clean format unit label without duplication
            $unitLabel = $unitNumber ? format_unit_label($unitNumber) : null;
            $actorLabel = null;
            if ($actorName) {
                $actorLabel = $actorName . ($unitLabel ? ' (' . $unitLabel . ')' : '');
            }

            if (! $recipientLabel) {
                if ($recipientName) {
                    $recipientLabel = $recipientName . ($unitLabel ? ' (' . $unitLabel . ')' : '');
                }
            }

            return [
                'id'              => $n->id,
                'title'           => $data['title'] ?? 'إشعار جديد',
                'message'         => $data['message'] ?? '',
                'type'            => $data['type'] ?? 'info',
                'icon'            => $data['icon'] ?? '🔔',
                'url'             => $data['url'] ?? null,
                'target_screen'   => $targetScreen,
                'target_id'       => $targetId,
                'actor_name'      => $actorName,
                'actor_role'      => $actorRole,
                'actor_label'     => $actorLabel,
                'recipient_name'  => $recipientName,
                'recipient_label' => $recipientLabel,
                'unit_number'     => $unitNumber,
                'unit_label'      => $unitLabel,
                'building_name'   => $buildingName,
                'extra'           => array_merge($extra, [
                    'actor_name'      => $actorName,
                    'actor_role'      => $actorRole,
                    'actor_label'     => $actorLabel,
                    'recipient_name'  => $recipientName,
                    'recipient_label' => $recipientLabel,
                    'unit_number'     => $unitNumber,
                    'unit_label'      => $unitLabel,
                    'building_name'   => $buildingName,
                ]),
                'is_read'         => ! is_null($n->read_at),
                'read_at'         => $n->read_at?->toIso8601String(),
                'time_ago'        => $createdAt->locale('ar')->diffForHumans(),
                'created_at'      => $createdAt->toIso8601String(),
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
