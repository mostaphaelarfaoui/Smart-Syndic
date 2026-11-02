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

        $latestActivitySql = 'COALESCE((SELECT MAX(created_at) FROM messages as r WHERE r.parent_id = messages.id), messages.created_at) DESC';

        // Inbox query: Threads where user received the message or received a reply
        $inboxQuery = Message::whereNull('parent_id')
            ->where(function ($q) use ($user) {
                $q->whereHas('recipients', fn ($r) => $r->where('user_id', $user->id))
                  ->orWhereHas('replies.recipients', fn ($r) => $r->where('user_id', $user->id));
            })
            ->with([
                'sender.apartments',
                'recipients' => fn ($q) => $q->where('user_id', $user->id),
                'replies' => fn ($q) => $q->orderBy('created_at', 'asc')->with(['sender.apartments', 'recipients']),
            ]);

        if ($filter === 'unread') {
            $inboxQuery->where(function ($q) use ($user) {
                $q->whereHas('recipients', fn ($r) => $r->where('user_id', $user->id)->where('is_read', false))
                  ->orWhereHas('replies.recipients', fn ($r) => $r->where('user_id', $user->id)->where('is_read', false));
            });
        } elseif ($filter === 'admin') {
            $inboxQuery->where('sender_role', 'SUPER_ADMIN');
        } elseif ($filter === 'residents') {
            $inboxQuery->where(function ($q) {
                $q->where('sender_role', 'RESIDENT')
                  ->orWhereHas('replies', fn ($rq) => $rq->where('sender_role', 'RESIDENT'));
            });
        }

        $inboxMessages = $inboxQuery->orderByRaw($latestActivitySql)->paginate(15, ['*'], 'inbox_page');

        // Sent query: Threads where user sent either the initial message or sent a reply
        $sentQuery = Message::whereNull('parent_id')
            ->where(function ($q) use ($user) {
                $q->where('sender_id', $user->id)
                  ->orWhereHas('replies', fn ($rq) => $rq->where('sender_id', $user->id));
            })
            ->with([
                'recipients.user',
                'replies' => fn ($q) => $q->orderBy('created_at', 'asc')->with(['sender.apartments', 'recipients']),
            ]);

        $sentMessages = $sentQuery->orderByRaw($latestActivitySql)->paginate(15, ['*'], 'sent_page');

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
        $totalInboxCount = Message::whereNull('parent_id')
            ->where(function ($q) use ($user) {
                $q->whereHas('recipients', fn ($r) => $r->where('user_id', $user->id))
                  ->orWhereHas('replies.recipients', fn ($r) => $r->where('user_id', $user->id));
            })->count();

        $adminMessagesCount = Message::whereNull('parent_id')
            ->where('sender_role', 'SUPER_ADMIN')
            ->whereHas('recipients', fn ($q) => $q->where('user_id', $user->id))
            ->count();

        $residentMessagesCount = Message::whereNull('parent_id')
            ->where(function ($q) use ($user) {
                $q->where('sender_role', 'RESIDENT')
                  ->orWhereHas('replies', fn ($rq) => $rq->where('sender_role', 'RESIDENT'));
            })
            ->where(function ($q) use ($user) {
                $q->whereHas('recipients', fn ($r) => $r->where('user_id', $user->id))
                  ->orWhereHas('replies.recipients', fn ($r) => $r->where('user_id', $user->id));
            })
            ->count();

        $selectedMessageId = $request->get('selected');
        $selectedMessage = null;
        if ($selectedMessageId) {
            $selectedMessage = Message::with(['sender.apartments', 'recipients.user', 'replies.sender.apartments', 'replies.recipients'])->find($selectedMessageId);
            if ($selectedMessage) {
                // If a reply ID was passed, point to the root conversation thread
                if ($selectedMessage->parent_id) {
                    $selectedMessage = Message::with(['sender.apartments', 'recipients.user', 'replies.sender.apartments', 'replies.recipients'])->find($selectedMessage->parent_id);
                }
                $this->messageService->markAsRead($selectedMessage->id, $user->id);
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
            'subject'      => ['nullable', 'string', 'max:255'],
            'body'         => ['required', 'string', 'max:10000'],
            'category'     => ['required', 'in:GENERAL,ANNOUNCEMENT,INQUIRY,COMPLAINT,MAINTENANCE,FINANCIAL'],
            'priority'     => ['required', 'in:NORMAL,HIGH,URGENT'],
        ], [
            'target_type.required' => 'يرجى تحديد الفئة المستهدفة للرسالة.',
            'body.required'        => 'يرجى كتابة نص الرسالة.',
        ]);

        if ($validated['target_type'] === 'ALL_RESIDENTS') {
            $targetIds = [];
        } elseif (in_array($validated['target_type'], ['INDIVIDUAL', 'GROUP'], true) && empty($targetIds)) {
            return back()->with('error', $validated['target_type'] === 'INDIVIDUAL' 
                ? 'يرجى اختيار الساكن المستهدف بالرسالة.' 
                : 'يرجى اختيار شقة واحدة على الأقل من قائمة الشقق.');
        }

        $subject = !empty($validated['subject']) 
            ? trim($validated['subject']) 
            : \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', trim($validated['body'])), 50);

        $message = $this->messageService->sendSyndicToResidents(
            syndic: auth()->user(),
            targetType: $validated['target_type'],
            targetIds: $targetIds,
            subject: $subject,
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

        $latestActivitySql = 'COALESCE((SELECT MAX(created_at) FROM messages as r WHERE r.parent_id = messages.id), messages.created_at) DESC';

        $threads = Message::whereNull('parent_id')
            ->where(function ($q) use ($user) {
                $q->where('sender_id', $user->id)
                  ->orWhereHas('recipients', fn ($r) => $r->where('user_id', $user->id))
                  ->orWhereHas('replies', fn ($rq) => $rq->where('sender_id', $user->id))
                  ->orWhereHas('replies.recipients', fn ($rq) => $rq->where('user_id', $user->id));
            })
            ->with([
                'sender.apartments',
                'recipients.user.apartments',
                'replies' => fn ($q) => $q->orderBy('created_at', 'asc')->with(['sender.apartments', 'recipients']),
            ])
            ->orderByRaw($latestActivitySql)
            ->limit(10)
            ->get();

        $messages = $threads->map(function ($m) use ($user) {
            $lastMsg = $m->last_message;
            $isUnread = $m->isUnreadForUser($user->id);

            // Determine contact person / recipient display name
            $isSentByMe = ($m->sender_id === $user->id);
            if ($isSentByMe) {
                $contactName = $m->recipient_label;
                $contactBadge = in_array($m->target_type, ['ALL_RESIDENTS', 'GROUP']) ? 'جماعي' : 'ساكن';
            } else {
                $contactName = $m->isFromSuperAdmin()
                    ? 'الإدارة العامة للمنصة'
                    : ($m->sender?->full_name ?? 'مستخدم');
                $contactBadge = $m->isFromSuperAdmin()
                    ? 'إعلان رسمي'
                    : ($m->isFromResident()
                        ? format_unit_label($m->target_metadata['unit_number'] ?? ($m->sender?->apartments?->first()?->unit_number ?? '—'))
                        : 'سانديك');
            }

            // Preview of the LAST message in the thread
            $lastBody = preg_replace('/\s+/', ' ', trim($lastMsg->body));
            $isLastFromMe = ($lastMsg->sender_id === $user->id);
            $cleanSnippet = \Illuminate\Support\Str::limit($lastBody, 65);
            $preview = $isLastFromMe ? ('أنت: ' . $cleanSnippet) : $cleanSnippet;

            return [
                'id'           => $m->id,
                'subject'      => $contactName,
                'contact_name' => $contactName,
                'body_snippet' => $preview,
                'sender_name'  => $contactName,
                'sender_badge' => $contactBadge,
                'is_admin'     => $m->isFromSuperAdmin(),
                'is_read'      => ! $isUnread,
                'time_ago'     => $lastMsg->created_at->diffForHumans(),
            ];
        })->values();

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
