<?php

namespace App\Services;

use App\Models\Apartment;
use App\Models\Message;
use App\Models\MessageRecipient;
use App\Models\PlatformAnnouncement;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MessageService
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Send or sync a Super Admin announcement into the messaging system.
     */
    public function sendSuperAdminAnnouncement(PlatformAnnouncement $announcement): Message
    {
        $priority = match (strtoupper($announcement->type)) {
            'DANGER'  => 'URGENT',
            'WARNING' => 'HIGH',
            default   => 'NORMAL',
        };

        // Check if message already generated for this announcement
        $message = Message::where('target_metadata->announcement_id', $announcement->id)->first();

        if (! $message) {
            $sender = $announcement->creator ?? User::where('role', 'SUPER_ADMIN')->first();
            $message = Message::create([
                'tenant_id'       => $announcement->tenant_id,
                'sender_id'       => $sender?->id ?? User::first()->id,
                'sender_role'     => 'SUPER_ADMIN',
                'target_type'     => $announcement->target_audience,
                'subject'         => $announcement->title,
                'body'            => $announcement->message,
                'category'        => 'ANNOUNCEMENT',
                'priority'        => $priority,
                'target_metadata' => [
                    'announcement_id' => $announcement->id,
                    'type'            => $announcement->type,
                    'is_dismissible'  => $announcement->is_dismissible,
                ],
            ]);
        }

        // Target users
        $query = User::where('status', 'ACTIVE');
        if ($announcement->target_audience === 'SYNDICS_ONLY') {
            $query->whereIn('role', ['ADMIN_SYNDIC', 'SYNDIC']);
        } elseif ($announcement->target_audience === 'RESIDENTS_ONLY') {
            $query->where('role', 'RESIDENT');
        } elseif ($announcement->target_audience === 'SPECIFIC_TENANT' && $announcement->tenant_id) {
            $query->where('tenant_id', $announcement->tenant_id);
        }

        $targetUsers = $query->get();
        foreach ($targetUsers as $targetUser) {
            MessageRecipient::firstOrCreate(
                [
                    'message_id' => $message->id,
                    'user_id'    => $targetUser->id,
                ],
                [
                    'is_read' => false,
                ]
            );
        }

        return $message;
    }

    /**
     * Resident sends a message to the Syndic of their residence.
     */
    public function sendResidentToSyndic(
        User $resident,
        string $subject,
        string $body,
        string $category = 'INQUIRY',
        string $priority = 'NORMAL'
    ): Message {
        $tenantId = $resident->tenant_id;
        $apartment = $resident->apartments()->first();

        return DB::transaction(function () use ($resident, $tenantId, $apartment, $subject, $body, $category, $priority) {
            $message = Message::create([
                'tenant_id'       => $tenantId,
                'sender_id'       => $resident->id,
                'sender_role'     => 'RESIDENT',
                'target_type'     => 'SYNDIC',
                'subject'         => $subject,
                'body'            => $body,
                'category'        => $category,
                'priority'        => $priority,
                'target_metadata' => [
                    'apartment_id'   => $apartment?->id,
                    'unit_number'    => $apartment?->unit_number ?? 'غير محدد',
                    'resident_name'  => $resident->full_name,
                    'resident_phone' => $resident->phone,
                ],
            ]);

            // Find all active syndics for this residence
            $syndics = User::where('tenant_id', $tenantId)
                ->whereIn('role', ['ADMIN_SYNDIC', 'SYNDIC'])
                ->where('status', 'ACTIVE')
                ->get();

            foreach ($syndics as $syndic) {
                MessageRecipient::create([
                    'message_id' => $message->id,
                    'user_id'    => $syndic->id,
                    'is_read'    => false,
                ]);

                // Notify syndic
                $this->notificationService->send(
                    user: $syndic,
                    title: "✉️ رسالة جديدة من الساكن {$resident->full_name} (شقة {$apartment?->unit_number})",
                    message: "الموضوع: {$subject}",
                    type: $priority === 'URGENT' ? 'warning' : 'info',
                    url: route('portal.messages.index'),
                    icon: '✉️',
                    extra: ['message_id' => $message->id, 'category' => 'residents']
                );
            }

            return $message;
        });
    }

    /**
     * Syndic sends message to residents (All, Single, or Group).
     */
    public function sendSyndicToResidents(
        User $syndic,
        string $targetType,
        array $targetIds,
        string $subject,
        string $body,
        string $category = 'GENERAL',
        string $priority = 'NORMAL'
    ): Message {
        $tenantId = $syndic->tenant_id;

        return DB::transaction(function () use ($syndic, $tenantId, $targetType, $targetIds, $subject, $body, $category, $priority) {
            $targetUsers = collect();
            $metadata = [];

            if ($targetType === 'ALL_RESIDENTS') {
                $targetUsers = User::where('tenant_id', $tenantId)
                    ->where('role', 'RESIDENT')
                    ->where('status', 'ACTIVE')
                    ->get();
                $metadata['description'] = 'رسالة عامة لكافة سكان الإقامة';
            } elseif ($targetType === 'INDIVIDUAL') {
                // targetIds[0] can be user_id or apartment_id
                $id = $targetIds[0] ?? null;
                if ($id) {
                    $user = User::where('tenant_id', $tenantId)->where('id', $id)->first();
                    if (! $user) {
                        // Check if it's an apartment ID
                        $apartment = Apartment::find($id);
                        $user = $apartment?->residents()->first();
                    }
                    if ($user) {
                        $targetUsers = collect([$user]);
                        $metadata['recipient_name'] = $user->full_name;
                    }
                }
            } elseif ($targetType === 'GROUP') {
                // targetIds can be array of apartment IDs or user IDs
                $apartments = Apartment::whereIn('id', $targetIds)->with('residents')->get();
                $users = collect();
                $unitNumbers = [];

                foreach ($apartments as $apt) {
                    $unitNumbers[] = $apt->unit_number;
                    foreach ($apt->residents as $res) {
                        $users->push($res);
                    }
                }

                // In case direct user IDs were passed
                $directUsers = User::whereIn('id', $targetIds)->where('role', 'RESIDENT')->get();
                $targetUsers = $users->merge($directUsers)->unique('id');
                $metadata['targeted_units'] = $unitNumbers;
                $metadata['count'] = $targetUsers->count();
            }

            $message = Message::create([
                'tenant_id'       => $tenantId,
                'sender_id'       => $syndic->id,
                'sender_role'     => 'ADMIN_SYNDIC',
                'target_type'     => $targetType,
                'subject'         => $subject,
                'body'            => $body,
                'category'        => $category,
                'priority'        => $priority,
                'target_metadata' => $metadata,
            ]);

            foreach ($targetUsers as $user) {
                MessageRecipient::create([
                    'message_id' => $message->id,
                    'user_id'    => $user->id,
                    'is_read'    => false,
                ]);

                // In-app notification
                $this->notificationService->send(
                    user: $user,
                    title: "✉️ رسالة جديدة من السانديك: {$subject}",
                    message: \Illuminate\Support\Str::limit($body, 90),
                    type: $priority === 'URGENT' ? 'warning' : 'info',
                    icon: '✉️',
                    extra: ['message_id' => $message->id]
                );
            }

            return $message;
        });
    }

    /**
     * Reply to an existing message.
     */
    public function reply(User $sender, Message $parentMessage, string $body): Message
    {
        $role = $sender->isResident() ? 'RESIDENT' : ($sender->isSuperAdmin() ? 'SUPER_ADMIN' : 'ADMIN_SYNDIC');

        return DB::transaction(function () use ($sender, $parentMessage, $body, $role) {
            $reply = Message::create([
                'tenant_id'   => $parentMessage->tenant_id,
                'sender_id'   => $sender->id,
                'sender_role' => $role,
                'target_type' => 'INDIVIDUAL',
                'subject'     => 'رد: ' . $parentMessage->subject,
                'body'        => $body,
                'category'    => $parentMessage->category,
                'priority'    => $parentMessage->priority,
                'parent_id'   => $parentMessage->id,
            ]);

            // Target recipient is original sender (if replying to them) or the resident
            $recipientId = ($parentMessage->sender_id === $sender->id)
                ? $parentMessage->recipients()->where('user_id', '!=', $sender->id)->first()?->user_id
                : $parentMessage->sender_id;

            if ($recipientId) {
                MessageRecipient::create([
                    'message_id' => $reply->id,
                    'user_id'    => $recipientId,
                    'is_read'    => false,
                ]);

                $recipientUser = User::find($recipientId);
                if ($recipientUser) {
                    $this->notificationService->send(
                        user: $recipientUser,
                        title: "💬 رد جديد على: {$parentMessage->subject}",
                        message: \Illuminate\Support\Str::limit($body, 90),
                        type: 'info',
                        icon: '💬',
                        extra: ['message_id' => $reply->id, 'parent_id' => $parentMessage->id]
                    );
                }
            }

            return $reply;
        });
    }

    /**
     * Mark a message as read for a specific user.
     */
    public function markAsRead(string $messageId, string $userId): bool
    {
        $recipient = MessageRecipient::where('message_id', $messageId)
            ->where('user_id', $userId)
            ->first();

        if ($recipient) {
            $recipient->markAsRead();
            return true;
        }

        return false;
    }

    /**
     * Mark all unread messages as read for a specific user.
     */
    public function markAllAsRead(string $userId): int
    {
        return MessageRecipient::where('user_id', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Get unread messages count for a user.
     */
    public function getUnreadCount(User $user): int
    {
        return MessageRecipient::where('user_id', $user->id)
            ->where('is_read', false)
            ->where('is_archived', false)
            ->count();
    }

    /**
     * Sync active platform announcements into messages for a given user.
     */
    public function syncAnnouncementsForUser(User $user): void
    {
        $activeAnnouncements = PlatformAnnouncement::currentlyActive($user->role, $user->tenant_id)->get();
        foreach ($activeAnnouncements as $ann) {
            $this->sendSuperAdminAnnouncement($ann);
        }
    }
}
