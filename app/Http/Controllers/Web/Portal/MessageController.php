<?php

namespace App\Http\Controllers\Web\Portal;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\AuditLog;
use App\Models\Building;
use App\Models\Message;
use App\Models\MessageRecipient;
use App\Models\User;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct(
        protected MessageService $messageService
    ) {}

    /**
     * Display the main Messages Center for Syndic.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        // Sync any active platform announcements into messages
        $this->messageService->syncAnnouncementsForUser($user);

        $tab = $request->get('tab', 'inbox'); // 'inbox' or 'sent'
        $filter = $request->get('filter', 'all'); // 'all', 'unread', 'admin', 'residents'

        // Inbox query
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
        } elseif ($filter === 'residents') {
            $inboxQuery->where('sender_role', 'RESIDENT');
        }

        $inboxMessages = $inboxQuery->orderBy('created_at', 'desc')->paginate(15, ['*'], 'inbox_page');

        // Sent query
        $sentMessages = Message::where('sender_id', $user->id)
            ->with(['recipients.user'])
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'sent_page');

        // Buildings & Apartments with Residents for targeting modal
        $buildings = Building::where('tenant_id', $tenantId)
            ->with(['apartments.residents'])
            ->orderBy('name')
            ->get();

        $residents = User::where('tenant_id', $tenantId)
            ->where('role', 'RESIDENT')
            ->where('status', 'ACTIVE')
            ->with('apartments')
            ->orderBy('first_name')
            ->get();

        $unreadCount = $this->messageService->getUnreadCount($user);
        $totalInboxCount = Message::whereHas('recipients', fn ($q) => $q->where('user_id', $user->id))->count();
        $adminMessagesCount = Message::whereHas('recipients', fn ($q) => $q->where('user_id', $user->id))
            ->where('sender_role', 'SUPER_ADMIN')->count();
        $residentMessagesCount = Message::whereHas('recipients', fn ($q) => $q->where('user_id', $user->id))
            ->where('sender_role', 'RESIDENT')->count();

        $selectedMessageId = $request->get('selected');
        $selectedMessage = null;
        if ($selectedMessageId) {
            $selectedMessage = Message::with(['sender.apartments', 'recipients.user', 'replies.sender'])->find($selectedMessageId);
            if ($selectedMessage) {
                $this->messageService->markAsRead($selectedMessageId, $user->id);
                $unreadCount = $this->messageService->getUnreadCount($user);
            }
        }

        return view('portal.messages.index', compact(
            'inboxMessages',
            'sentMessages',
            'buildings',
            'residents',
            'unreadCount',
            'totalInboxCount',
            'adminMessagesCount',
            'residentMessagesCount',
            'tab',
            'filter',
            'selectedMessage'
        ));
    }

    /**
     * Store and dispatch a message from Syndic to Residents.
     */
    public function store(Request $request)
    {
        // Sanitize target_ids to remove empty or null values
        $rawTargetIds = $request->input('target_ids', []);
        $targetIds = is_array($rawTargetIds)
            ? array_values(array_filter($rawTargetIds, fn($id) => !empty($id) && is_string($id)))
            : [];
        $request->merge(['target_ids' => $targetIds]);

        $validated = $request->validate([
            'target_type'  => ['required', 'in:ALL_RESIDENTS,INDIVIDUAL,GROUP'],
            'target_ids'   => ['nullable', 'array'],
            'target_ids.*' => ['nullable', 'string'],
            'subject'      => ['required', 'string', 'max:255'],
            'body'         => ['required', 'string', 'max:10000'],
            'category'     => ['required', 'in:GENERAL,ANNOUNCEMENT,INQUIRY,COMPLAINT,MAINTENANCE,FINANCIAL'],
            'priority'     => ['required', 'in:NORMAL,HIGH,URGENT'],
        ], [
            'target_type.required' => 'يرجى تحديد الفئة المستهدفة للرسالة.',
            'subject.required'     => 'يرجى كتابة موضوع الرسالة.',
            'body.required'        => 'يرجى كتابة نص الرسالة.',
        ]);

        if ($validated['target_type'] === 'ALL_RESIDENTS') {
            $targetIds = [];
        } elseif (in_array($validated['target_type'], ['INDIVIDUAL', 'GROUP'], true) && empty($targetIds)) {
            return back()->with('error', $validated['target_type'] === 'INDIVIDUAL' 
                ? 'يرجى اختيار الساكن المستهدف بالرسالة.' 
                : 'يرجى اختيار شقة واحدة على الأقل من قائمة الشقق.');
        }

        $message = $this->messageService->sendSyndicToResidents(
            syndic: auth()->user(),
            targetType: $validated['target_type'],
            targetIds: $targetIds,
            subject: $validated['subject'],
            body: $validated['body'],
            category: $validated['category'],
            priority: $validated['priority']
        );

        AuditLog::record(
            actorId: auth()->id(),
            action: 'MESSAGE_SEND_SYNDIC',
            targetTenantId: auth()->user()->tenant_id,
            metadata: [
                'message_id'  => $message->id,
                'target_type' => $message->target_type,
                'subject'     => $message->subject,
            ]
        );

        return back()->with('success', 'تم إرسال الرسالة بنجاح إلى المستهدفين.');
    }

    /**
     * Reply to a message.
     */
    public function reply(Request $request, string $id)
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ], [
            'body.required' => 'يرجى كتابة نص الرد.',
        ]);

        $parentMessage = Message::findOrFail($id);

        $reply = $this->messageService->reply(
            sender: auth()->user(),
            parentMessage: $parentMessage,
            body: $validated['body']
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم إرسال الرد بنجاح.',
                'reply'   => $reply,
            ]);
        }

        return back()->with('success', 'تم إرسال الرد بنجاح.');
    }

    /**
     * Mark message as read.
     */
    public function markAsRead(string $id): JsonResponse
    {
        $this->messageService->markAsRead($id, auth()->id());

        return response()->json([
            'success'      => true,
            'unread_count' => $this->messageService->getUnreadCount(auth()->user()),
        ]);
    }

    /**
     * Mark all messages as read.
     */
    public function markAllAsRead(): JsonResponse
    {
        $count = $this->messageService->markAllAsRead(auth()->id());

        return response()->json([
            'success'      => true,
            'marked_count' => $count,
            'unread_count' => 0,
        ]);
    }

    /**
     * Returns JSON for Header Dropdown <x-messages-bell />.
     */
    public function quickList(): JsonResponse
    {
        $user = auth()->user();
        $this->messageService->syncAnnouncementsForUser($user);

        $recipients = MessageRecipient::where('user_id', $user->id)
            ->where('is_archived', false)
            ->with(['message.sender.apartments'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $messages = $recipients->map(function ($r) {
            $m = $r->message;
            if (! $m) return null;

            $senderName = $m->isFromSuperAdmin()
                ? 'الإدارة العامة للمنصة'
                : ($m->sender?->full_name ?? 'مستخدم');

            $senderBadge = $m->isFromSuperAdmin()
                ? 'إعلان رسمي'
                : ($m->isFromResident()
                    ? ('شقة ' . ($m->target_metadata['unit_number'] ?? ($m->sender?->apartments?->first()?->unit_number ?? '—')))
                    : 'سانديك');

            return [
                'id'           => $m->id,
                'subject'      => $m->subject,
                'body_snippet' => \Illuminate\Support\Str::limit($m->body, 70),
                'sender_name'  => $senderName,
                'sender_badge' => $senderBadge,
                'is_admin'     => $m->isFromSuperAdmin(),
                'is_read'      => $r->is_read,
                'time_ago'     => $m->created_at->diffForHumans(),
            ];
        })->filter()->values();

        return response()->json([
            'messages'     => $messages,
            'unread_count' => $this->messageService->getUnreadCount($user),
        ]);
    }

    /**
     * Lightweight unread counter for polling.
     */
    public function unreadCount(): JsonResponse
    {
        return response()->json([
            'unread_count' => $this->messageService->getUnreadCount(auth()->user()),
        ]);
    }
}
