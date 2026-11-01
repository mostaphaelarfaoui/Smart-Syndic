<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\MessageRecipient;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageApiController extends Controller
{
    public function __construct(
        protected MessageService $messageService
    ) {}

    /**
     * GET /api/v1/messages
     *
     * List received and sent messages for the current user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Sync any active platform announcements
        $this->messageService->syncAnnouncementsForUser($user);

        $filter = $request->get('filter', 'all'); // all, unread, admin

        $inboxQuery = Message::whereHas('recipients', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->with(['sender.apartments', 'recipients' => function ($q) use ($user) {
            $q->where('user_id', $user->id);
        }]);

        if ($filter === 'unread') {
            $inboxQuery->whereHas('recipients', function ($q) use ($user) {
                $q->where('user_id', $user->id)->where('is_read', false);
            });
        } elseif ($filter === 'admin') {
            $inboxQuery->where('sender_role', 'SUPER_ADMIN');
        }

        $inbox = $inboxQuery->orderBy('created_at', 'desc')->paginate(20);

        $sent = Message::where('sender_id', $user->id)
            ->with('recipients.user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'unread_count' => $this->messageService->getUnreadCount($user),
            'inbox'        => $inbox,
            'sent'         => $sent,
        ]);
    }

    /**
     * GET /api/v1/messages/{id}
     *
     * View message details and mark as read.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $message = Message::with(['sender.apartments', 'recipients.user', 'replies.sender'])
            ->where(function ($q) use ($user) {
                $q->where('sender_id', $user->id)
                  ->orWhereHas('recipients', fn ($r) => $r->where('user_id', $user->id));
            })
            ->findOrFail($id);

        // Mark as read for this user
        $this->messageService->markAsRead($message->id, $user->id);

        return response()->json([
            'message'      => $message,
            'unread_count' => $this->messageService->getUnreadCount($user),
        ]);
    }

    /**
     * POST /api/v1/messages
     *
     * Send a new message (Resident -> Syndic, or Syndic -> Residents).
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isResident()) {
            // Resident sending to Syndic
            $request->validate([
                'subject'  => ['required', 'string', 'max:255'],
                'body'     => ['required', 'string', 'max:10000'],
                'category' => ['nullable', 'in:GENERAL,INQUIRY,COMPLAINT,MAINTENANCE,FINANCIAL'],
                'priority' => ['nullable', 'in:NORMAL,HIGH,URGENT'],
            ]);

            $message = $this->messageService->sendResidentToSyndic(
                resident: $user,
                subject: $request->subject,
                body: $request->body,
                category: $request->category ?? 'INQUIRY',
                priority: $request->priority ?? 'NORMAL'
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'تم إرسال رسالتك إلى إدارة السانديك بنجاح.',
                'data'    => $message,
            ], 201);
        }

        // Syndic sending to Residents
        $rawTargetIds = $request->input('target_ids', []);
        $targetIds = is_array($rawTargetIds)
            ? array_values(array_filter($rawTargetIds, fn($id) => !empty($id) && is_string($id)))
            : [];
        $request->merge(['target_ids' => $targetIds]);

        $request->validate([
            'target_type'  => ['required', 'in:ALL_RESIDENTS,INDIVIDUAL,GROUP'],
            'target_ids'   => ['nullable', 'array'],
            'target_ids.*' => ['nullable', 'string'],
            'subject'      => ['required', 'string', 'max:255'],
            'body'         => ['required', 'string', 'max:10000'],
            'category'     => ['nullable', 'in:GENERAL,ANNOUNCEMENT,INQUIRY,COMPLAINT,MAINTENANCE,FINANCIAL'],
            'priority'     => ['nullable', 'in:NORMAL,HIGH,URGENT'],
        ]);

        if ($request->target_type === 'ALL_RESIDENTS') {
            $targetIds = [];
        } elseif (in_array($request->target_type, ['INDIVIDUAL', 'GROUP'], true) && empty($targetIds)) {
            return response()->json([
                'status'  => 'error',
                'message' => $request->target_type === 'INDIVIDUAL' 
                    ? 'يرجى اختيار الساكن المستهدف.' 
                    : 'يرجى اختيار شقة واحدة على الأقل.',
            ], 422);
        }

        $message = $this->messageService->sendSyndicToResidents(
            syndic: $user,
            targetType: $request->target_type,
            targetIds: $targetIds,
            subject: $request->subject,
            body: $request->body,
            category: $request->category ?? 'GENERAL',
            priority: $request->priority ?? 'NORMAL'
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'تم إرسال الرسالة إلى السكان المستهدفين بنجاح.',
            'data'    => $message,
        ], 201);
    }

    /**
     * POST /api/v1/messages/{id}/reply
     */
    public function reply(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $parent = Message::findOrFail($id);

        $reply = $this->messageService->reply(
            sender: $request->user(),
            parentMessage: $parent,
            body: $request->body
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'تم إرسال الرد بنجاح.',
            'reply'   => $reply,
        ], 201);
    }

    /**
     * POST /api/v1/messages/{id}/read
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $this->messageService->markAsRead($id, $request->user()->id);

        return response()->json([
            'success'      => true,
            'unread_count' => $this->messageService->getUnreadCount($request->user()),
        ]);
    }

    /**
     * POST /api/v1/messages/mark-all-read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $count = $this->messageService->markAllAsRead($request->user()->id);

        return response()->json([
            'success'      => true,
            'marked_count' => $count,
            'unread_count' => 0,
        ]);
    }
}
