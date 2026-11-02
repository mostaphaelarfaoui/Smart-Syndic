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
                    'apartment_id'    => $apartment?->id,
                    'unit_number'     => $apartment ? $apartment->clean_unit_number : 'غير محدد',
                    'building_name'   => $apartment?->building?->name,
                    'resident_name'   => $resident->full_name,
                    'resident_phone'  => $resident->phone,
                    'recipient_label' => 'إدارة السانديك',
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
                $metadata['recipient_label'] = 'كافة سكان الإقامة';
            } elseif ($targetType === 'INDIVIDUAL') {
                // targetIds[0] can be user_id or apartment_id
                $id = $targetIds[0] ?? null;
                if ($id) {
                    $user = User::where('tenant_id', $tenantId)->where('id', $id)->first();
                    $apartment = null;
                    if (! $user) {
                        // Check if it's an apartment ID
                        $apartment = Apartment::find($id);
                        $user = $apartment?->residents()->first();
                    } else {
                        $apartment = $user->apartments()->first();
                    }
                    if ($user) {
                        $targetUsers = collect([$user]);
                        $metadata['recipient_name'] = $user->full_name;
                        $metadata['unit_number'] = $apartment?->clean_unit_number;
                        $metadata['building_name'] = $apartment?->building?->name;
                        $metadata['recipient_label'] = $user->full_name . ($apartment ? ' (' . format_unit_label($apartment->clean_unit_number) . ')' : '');
                    }
                }
            } elseif ($targetType === 'GROUP') {
                // targetIds can be array of apartment IDs or user IDs
                $apartments = Apartment::whereIn('id', $targetIds)->with(['residents', 'building'])->get();
                $users = collect();
                $unitNumbers = [];

                foreach ($apartments as $apt) {
                    $unitNumbers[] = $apt->clean_unit_number;
                    foreach ($apt->residents as $res) {
                        $users->push($res);
                    }
                }

                // In case direct user IDs were passed
                $directUsers = User::whereIn('id', $targetIds)->where('role', 'RESIDENT')->get();
                $targetUsers = $users->merge($directUsers)->unique('id');
                $metadata['targeted_units'] = $unitNumbers;
                $metadata['count'] = $targetUsers->count();
                $formattedUnits = array_map(fn($u) => format_unit_label($u), $unitNumbers);
                $metadata['recipient_label'] = 'مجموعة شقق: ' . implode(', ', $formattedUnits);
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

        // Resolve root message if called on a reply
        $rootMessage = $parentMessage->parent_id ? ($parentMessage->parent ?? $parentMessage) : $parentMessage;

        return DB::transaction(function () use ($sender, $rootMessage, $body, $role) {
            $reply = Message::create([
                'tenant_id'   => $rootMessage->tenant_id,
                'sender_id'   => $sender->id,
                'sender_role' => $role,
                'target_type' => 'INDIVIDUAL',
                'subject'     => 'رد: ' . $rootMessage->subject,
                'body'        => $body,
                'category'    => $rootMessage->category,
                'priority'    => $rootMessage->priority,
                'parent_id'   => $rootMessage->id,
            ]);

            // Smart recipient resolution:
            // If sender is NOT parent sender, recipient is parent sender (e.g. syndic)
            // If sender IS parent sender, recipient is resident who replied last, or parent recipient
            if ($rootMessage->sender_id !== $sender->id) {
                $recipientId = $rootMessage->sender_id;
            } else {
                $lastOtherSender = $rootMessage->replies()
                    ->where('sender_id', '!=', $sender->id)
                    ->latest('created_at')
                    ->value('sender_id');

                $recipientId = $lastOtherSender 
                    ?? $rootMessage->recipients()->where('user_id', '!=', $sender->id)->value('user_id');
            }

            if ($recipientId) {
                MessageRecipient::create([
                    'message_id' => $reply->id,
                    'user_id'    => $recipientId,
                    'is_read'    => false,
                ]);

                // Ensure the recipient record on the root message is updated/created and marked unread
                // so the entire conversation thread shows as unread in their inbox!
                $rootRec = MessageRecipient::firstOrCreate(
                    ['message_id' => $rootMessage->id, 'user_id' => $recipientId],
                    ['is_read' => false]
                );
                $rootRec->update([
                    'is_read' => false,
                    'read_at' => null,
                ]);
            }

            // Touch the root message to update its updated_at timestamp
            $rootMessage->touch();

            return $reply;
        });
    }

    /**
     * Mark a message as read for a specific user (marks all messages in the conversation thread).
     */
    public function markAsRead(string $messageId, string $userId): bool
    {
        $message = Message::find($messageId);
        if (! $message) {
            return false;
        }

        $threadId = $message->parent_id ?? $message->id;
        $allIds = Message::where('id', $threadId)
            ->orWhere('parent_id', $threadId)
            ->pluck('id')
            ->toArray();

        MessageRecipient::whereIn('message_id', $allIds)
            ->where('user_id', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return true;
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
     * Get unread messages/conversations count for a user.
     */
    public function getUnreadCount(User $user): int
    {
        return MessageRecipient::where('message_recipients.user_id', $user->id)
            ->where('message_recipients.is_read', false)
            ->where('message_recipients.is_archived', false)
            ->join('messages', 'messages.id', '=', 'message_recipients.message_id')
            ->distinct()
            ->count(DB::raw('COALESCE(messages.parent_id, messages.id)'));
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
