@extends('layouts.dashboard')

@section('title', 'مركز الرسائل والمحادثات')

@section('content')
<div class="messages-container" x-data="messagesManager()" x-init="init()">
    {{-- Page Header --}}
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem; margin-bottom:1.5rem;">
        <div>
            <h2 style="margin:0; font-size:1.45rem; font-weight:800; color:var(--text-primary); display:flex; align-items:center; gap:0.5rem;">
                <span>✉️ مركز الرسائل والتواصل</span>
            </h2>
            <p style="margin:0.25rem 0 0; font-size:0.86rem; color:var(--text-secondary);">
                استقبال إعلانات الإدارة العامة، والرد على مراسلات السكان، وإرسال رسائل عامة أو مخصصة
            </p>
        </div>

        <div style="display:flex; align-items:center; gap:0.6rem;">
            <button type="button" 
                    class="btn btn-primary" 
                    @click="openComposeModal()"
                    style="padding:0.6rem 1.25rem; font-weight:800; font-size:0.9rem; display:inline-flex; align-items:center; gap:0.45rem; box-shadow:0 4px 14px rgba(79, 70, 229, 0.25);">
                <span>➕</span>
                <span>كتابة رسالة جديدة</span>
            </button>
        </div>
    </div>

    {{-- KPI Cards Row --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-bottom:1.75rem;">
        <div class="card" style="padding:1.15rem; display:flex; align-items:center; gap:1rem;">
            <div style="width:48px; height:48px; border-radius:12px; background:rgba(99, 102, 241, 0.12); color:var(--brand-primary); display:flex; align-items:center; justify-content:center; font-size:1.4rem;">
                📥
            </div>
            <div>
                <div style="font-size:0.75rem; color:var(--text-muted); font-weight:700;">صندوق الوارد</div>
                <div class="num" style="font-size:1.35rem; font-weight:900; color:var(--text-primary); margin-top:2px;">
                    {{ number_format($totalInboxCount) }}
                </div>
            </div>
        </div>

        <div class="card" style="padding:1.15rem; display:flex; align-items:center; gap:1rem; border-color:{{ $unreadCount > 0 ? 'var(--brand-primary)' : 'var(--border-color)' }};">
            <div style="width:48px; height:48px; border-radius:12px; background:rgba(239, 68, 68, 0.12); color:#ef4444; display:flex; align-items:center; justify-content:center; font-size:1.4rem;">
                📬
            </div>
            <div>
                <div style="font-size:0.75rem; color:var(--text-muted); font-weight:700;">غير المقروءة</div>
                <div class="num" style="font-size:1.35rem; font-weight:900; color:{{ $unreadCount > 0 ? '#ef4444' : 'var(--text-primary)' }}; margin-top:2px;">
                    {{ number_format($unreadCount) }}
                </div>
            </div>
        </div>

        <div class="card" style="padding:1.15rem; display:flex; align-items:center; gap:1rem;">
            <div style="width:48px; height:48px; border-radius:12px; background:rgba(79, 70, 229, 0.12); color:#6366f1; display:flex; align-items:center; justify-content:center; font-size:1.4rem;">
                🛡️
            </div>
            <div>
                <div style="font-size:0.75rem; color:var(--text-muted); font-weight:700;">إعلانات الإدارة العامة</div>
                <div class="num" style="font-size:1.35rem; font-weight:900; color:var(--text-primary); margin-top:2px;">
                    {{ number_format($adminMessagesCount) }}
                </div>
            </div>
        </div>

        <div class="card" style="padding:1.15rem; display:flex; align-items:center; gap:1rem;">
            <div style="width:48px; height:48px; border-radius:12px; background:rgba(16, 185, 129, 0.12); color:#10b981; display:flex; align-items:center; justify-content:center; font-size:1.4rem;">
                👤
            </div>
            <div>
                <div style="font-size:0.75rem; color:var(--text-muted); font-weight:700;">رسائل واستفسارات السكان</div>
                <div class="num" style="font-size:1.35rem; font-weight:900; color:var(--text-primary); margin-top:2px;">
                    {{ number_format($residentMessagesCount) }}
                </div>
            </div>
        </div>
    </div>

    {{-- Main Two-Column Layout (Messages List + Details View) --}}
    <div style="display:grid; grid-template-columns: minmax(320px, 420px) 1fr; gap:1.25rem; align-items:flex-start;">
        
        {{-- Column 1: Tabs & Message List --}}
        <div class="card" style="padding:0; overflow:hidden;">
            {{-- Main Tabs (Inbox / Sent) --}}
            <div style="display:flex; border-bottom:1px solid var(--border-color); background:var(--bg-secondary);">
                <a href="{{ route('portal.messages.index', ['tab' => 'inbox']) }}" 
                   class="tab-btn {{ $tab === 'inbox' ? 'active' : '' }}" 
                   style="flex:1; padding:0.85rem 1rem; text-align:center; font-weight:800; font-size:0.88rem; text-decoration:none; color:inherit; display:flex; align-items:center; justify-content:center; gap:0.4rem; border-bottom:2px solid {{ $tab === 'inbox' ? 'var(--brand-primary)' : 'transparent' }}; background:{{ $tab === 'inbox' ? 'var(--bg-surface)' : 'transparent' }};">
                    <span>📥 صندوق الوارد</span>
                    @if($unreadCount > 0)
                        <span class="badge badge-primary num" style="font-size:0.7rem; padding:0.12rem 0.45rem;">{{ $unreadCount }}</span>
                    @endif
                </a>

                <a href="{{ route('portal.messages.index', ['tab' => 'sent']) }}" 
                   class="tab-btn {{ $tab === 'sent' ? 'active' : '' }}" 
                   style="flex:1; padding:0.85rem 1rem; text-align:center; font-weight:800; font-size:0.88rem; text-decoration:none; color:inherit; display:flex; align-items:center; justify-content:center; gap:0.4rem; border-bottom:2px solid {{ $tab === 'sent' ? 'var(--brand-primary)' : 'transparent' }}; background:{{ $tab === 'sent' ? 'var(--bg-surface)' : 'transparent' }};">
                    <span>📤 الرسائل المرسلة</span>
                </a>
            </div>

            {{-- Filter Pills for Inbox --}}
            @if($tab === 'inbox')
                <div style="display:flex; align-items:center; gap:0.4rem; padding:0.75rem 1rem; border-bottom:1px solid var(--border-color); background:var(--bg-surface); overflow-x:auto;">
                    <a href="{{ route('portal.messages.index', ['tab' => 'inbox', 'filter' => 'all']) }}" 
                       class="btn btn-sm {{ $filter === 'all' ? 'btn-primary' : 'btn-secondary' }}" 
                       style="font-size:0.76rem; font-weight:700; padding:0.25rem 0.65rem; border-radius:20px;">
                        الكل
                    </a>
                    <a href="{{ route('portal.messages.index', ['tab' => 'inbox', 'filter' => 'unread']) }}" 
                       class="btn btn-sm {{ $filter === 'unread' ? 'btn-primary' : 'btn-secondary' }}" 
                       style="font-size:0.76rem; font-weight:700; padding:0.25rem 0.65rem; border-radius:20px;">
                        غير المقروءة ({{ $unreadCount }})
                    </a>
                    <a href="{{ route('portal.messages.index', ['tab' => 'inbox', 'filter' => 'admin']) }}" 
                       class="btn btn-sm {{ $filter === 'admin' ? 'btn-primary' : 'btn-secondary' }}" 
                       style="font-size:0.76rem; font-weight:700; padding:0.25rem 0.65rem; border-radius:20px;">
                        🛡️ إعلانات الإدارة
                    </a>
                    <a href="{{ route('portal.messages.index', ['tab' => 'inbox', 'filter' => 'residents']) }}" 
                       class="btn btn-sm {{ $filter === 'residents' ? 'btn-primary' : 'btn-secondary' }}" 
                       style="font-size:0.76rem; font-weight:700; padding:0.25rem 0.65rem; border-radius:20px;">
                        👤 السكان
                    </a>
                </div>
            @endif

            {{-- Messages List --}}
            <div style="max-height:650px; overflow-y:auto;">
                @php
                    $list = $tab === 'inbox' ? $inboxMessages : $sentMessages;
                @endphp

                @forelse($list as $msg)
                    @php
                        $isInbox = $tab === 'inbox';
                        $isUnread = $msg->isUnreadForUser(auth()->id());
                        $isSelected = $selectedMessage && $selectedMessage->id === $msg->id;
                        $isAdmin = $msg->isFromSuperAdmin();
                        $isResident = $msg->isFromResident();
                        $latestTime = $msg->latest_activity_at ?? $msg->created_at;
                        $latestSnippet = $msg->latest_message_body ?? $msg->body;
                    @endphp

                    <a href="{{ route('portal.messages.index', ['tab' => $tab, 'filter' => $filter, 'selected' => $msg->id]) }}" 
                       class="message-list-row {{ $isSelected ? 'active-row' : '' }} {{ $isUnread ? 'unread-row' : '' }}" 
                       style="display:flex; align-items:flex-start; gap:0.75rem; padding:0.9rem 1.1rem; border-bottom:1px solid var(--border-color); text-decoration:none; color:inherit; transition:background 0.15s; position:relative; background:{{ $isSelected ? 'rgba(99, 102, 241, 0.08)' : ($isUnread ? 'rgba(99, 102, 241, 0.03)' : 'transparent') }}; border-right:4px solid {{ $isSelected ? 'var(--brand-primary)' : ($isAdmin ? '#6366f1' : 'transparent') }};">
                        
                        <div style="width:40px; height:40px; min-width:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.15rem; background:{{ $isAdmin ? 'linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(79, 70, 229, 0.3))' : ($isResident ? 'rgba(16, 185, 129, 0.15)' : 'rgba(99, 102, 241, 0.15)') }};">
                            {{ $isAdmin ? '🛡️' : ($isResident ? '👤' : '📤') }}
                        </div>

                        {{-- Details --}}
                        <div style="flex:1; min-width:0;">
                            <div style="display:flex; align-items:center; justify-content:space-between; gap:0.35rem; margin-bottom:0.25rem;">
                                <div style="display:flex; align-items:center; gap:0.35rem; overflow:hidden;">
                                    <strong style="font-size:0.88rem; color:var(--text-primary); white-space:nowrap; text-overflow:ellipsis; overflow:hidden;">
                                        @if($isInbox)
                                            من: {{ $msg->sender_display_name }}
                                        @else
                                            إلى: {{ $msg->recipient_label }}
                                        @endif
                                    </strong>
                                    @if($isInbox && $msg->sender_unit_label)
                                        <span class="badge badge-neutral num" style="font-size:0.68rem; padding:0.1rem 0.35rem;">
                                            {{ $msg->sender_unit_label }}
                                        </span>
                                    @endif
                                </div>
                                <span class="num" style="font-size:0.7rem; color:{{ $isUnread ? 'var(--brand-primary)' : 'var(--text-muted)' }}; font-weight:{{ $isUnread ? '700' : '500' }}; flex-shrink:0;">
                                    {{ $latestTime->diffForHumans() }}
                                </span>
                            </div>

                            {{-- Social Media Chat Style: Latest Message in Conversation --}}
                            <div style="font-size:0.82rem; color:{{ $isUnread ? 'var(--text-primary)' : 'var(--text-secondary)' }}; font-weight:{{ $isUnread ? '700' : '400' }}; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; line-height:1.4;">
                                {{ $msg->last_message_preview }}
                            </div>

                            <div style="font-size:0.72rem; color:var(--text-muted); margin-top:5px; display:flex; align-items:center; justify-content:space-between; gap:0.4rem;">
                                <span class="badge" style="font-size:0.68rem; padding:0.1rem 0.4rem; background:rgba(99, 102, 241, 0.08); color:var(--brand-primary); font-weight:600;">
                                    {{ $msg->category_label }}
                                </span>
                                @if(($msg->replies_count ?? $msg->replies->count()) > 0)
                                    <span style="font-size:0.7rem; color:var(--brand-primary); font-weight:700; display:flex; align-items:center; gap:0.2rem;">
                                        💬 {{ $msg->replies_count ?? $msg->replies->count() }} ردود
                                    </span>
                                @endif
                            </div>
                        </div>

                        @if($isUnread)
                            <span style="width:8px; height:8px; border-radius:50%; background:var(--brand-primary); margin-top:0.4rem; flex-shrink:0;"></span>
                        @endif
                    </a>
                @empty
                    <div style="padding:3.5rem 1.5rem; text-align:center; color:var(--text-muted);">
                        <div style="font-size:2.5rem; margin-bottom:0.5rem;">📭</div>
                        <div style="font-weight:700; font-size:0.95rem; color:var(--text-primary);">لا توجد رسائل في هذا القسم</div>
                        <div style="font-size:0.8rem; margin-top:0.25rem;">يمكنك كتابة رسالة جديدة لكافة السكان أو لساكن بعينه</div>
                    </div>
                @endforelse
            </div>

            @if($list->hasPages())
                <div style="padding:0.75rem 1rem; border-top:1px solid var(--border-color); background:var(--bg-secondary);">
                    {{ $list->links() }}
                </div>
            @endif
        </div>

        {{-- Column 2: Message Details & Conversation View --}}
        <div>
            @if($selectedMessage)
                @php
                    $isSentByMe = $selectedMessage->sender_id === auth()->id();
                    $hasReplies = $selectedMessage->replies->isNotEmpty();
                    $canReply = !$selectedMessage->isFromSuperAdmin() && (!$isSentByMe || $hasReplies);
                @endphp
                <div class="card" style="padding:1.5rem;">
                    {{-- Header of selected message: Only recipient if sent, only sender if received --}}
                    <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; flex-wrap:wrap; padding-bottom:1.25rem; border-bottom:1px solid var(--border-color); margin-bottom:1.25rem;">
                        <div style="display:flex; align-items:center; gap:0.9rem;">
                            <div style="width:48px; height:48px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.5rem; background:{{ $selectedMessage->isFromSuperAdmin() ? 'linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(79, 70, 229, 0.3))' : ($isSentByMe ? 'rgba(99, 102, 241, 0.15)' : 'rgba(16, 185, 129, 0.15)') }};">
                                {{ $selectedMessage->isFromSuperAdmin() ? '🛡️' : ($isSentByMe ? '📤' : '👤') }}
                            </div>
                            <div>
                                <h3 style="margin:0; font-size:1.15rem; font-weight:800; color:var(--text-primary); display:flex; align-items:center; gap:0.5rem;">
                                    @if($isSentByMe)
                                        <span>إلى: {{ $selectedMessage->recipient_label }}</span>
                                    @else
                                        <span>{{ $selectedMessage->sender_display_name }}</span>
                                        @if($selectedMessage->sender_unit_label)
                                            <span class="badge badge-neutral num" style="font-size:0.75rem; vertical-align:middle;">
                                                {{ $selectedMessage->sender_unit_label }}
                                            </span>
                                        @endif
                                    @endif
                                </h3>
                                
                                <div style="display:flex; align-items:center; gap:0.6rem; flex-wrap:wrap; margin-top:0.45rem; font-size:0.82rem; color:var(--text-secondary);">
                                    @if(!$isSentByMe && ($selectedMessage->sender?->phone || isset($selectedMessage->target_metadata['resident_phone'])))
                                        <span>📞 هاتف: <strong class="num">{{ $selectedMessage->target_metadata['resident_phone'] ?? $selectedMessage->sender->phone }}</strong></span>
                                        <span>•</span>
                                    @endif
                                    <span>🕒 بدأت المحادثة: {{ $selectedMessage->created_at->format('Y/m/d H:i') }}</span>
                                </div>
                            </div>
                        </div>

                        <div style="display:flex; align-items:center; gap:0.4rem;">
                            <span class="badge" style="background:rgba(99, 102, 241, 0.12); color:var(--brand-primary); font-size:0.75rem; font-weight:700;">
                                {{ $selectedMessage->category_label }}
                            </span>
                            @if($selectedMessage->priority !== 'NORMAL')
                                <span class="badge badge-danger" style="font-size:0.75rem; font-weight:800;">
                                    {{ $selectedMessage->priority_label }}
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Unified Chat Stream (نمط الدردشة الاجتماعية المباشرة) --}}
                    <div style="margin-bottom:1.25rem;">
                        <div id="chatMessagesBox" x-init="$nextTick(() => { $el.scrollTop = $el.scrollHeight })" style="background:var(--bg-secondary); border:1px solid var(--border-color); border-radius:14px; padding:1.2rem; display:flex; flex-direction:column; gap:1rem; max-height:550px; overflow-y:auto; scroll-behavior:smooth;">
                            {{-- First/Original Message Bubble --}}
                            @php
                                $origIsMe = $selectedMessage->sender_id === auth()->id();
                            @endphp
                            <div style="display:flex; flex-direction:column; align-items:{{ $origIsMe ? 'flex-end' : 'flex-start' }}; max-width:85%; {{ $origIsMe ? 'margin-inline-start:auto;' : 'margin-inline-end:auto;' }}">
                                <div style="display:flex; align-items:center; gap:0.4rem; margin-bottom:0.25rem; font-size:0.72rem;">
                                    <strong style="color:{{ $origIsMe ? 'var(--brand-primary)' : 'var(--text-primary)' }};">
                                        {{ $origIsMe ? 'أنت (السانديك)' : $selectedMessage->sender_display_name }}
                                    </strong>
                                    @if(!$origIsMe && $selectedMessage->sender_unit_label)
                                        <span class="badge badge-neutral num" style="font-size:0.65rem; padding:0.05rem 0.35rem;">
                                            {{ $selectedMessage->sender_unit_label }}
                                        </span>
                                    @endif
                                    <span class="num" style="color:var(--text-muted); font-size:0.68rem;">
                                        {{ $selectedMessage->created_at->format('H:i - Y/m/d') }}
                                    </span>
                                </div>
                                <div style="padding:0.85rem 1.1rem; border-radius:14px; border-{{ $origIsMe ? 'bottom-left' : 'bottom-right' }}-radius:3px; background:{{ $origIsMe ? 'rgba(99, 102, 241, 0.12)' : 'var(--bg-primary)' }}; border:1px solid {{ $origIsMe ? 'rgba(99, 102, 241, 0.25)' : 'var(--border-color)' }}; color:var(--text-primary); font-size:0.9rem; line-height:1.7; white-space:pre-wrap; word-break:break-word;">
                                    {{ $selectedMessage->body }}
                                </div>
                            </div>

                            {{-- Replies Bubbles --}}
                            @foreach($selectedMessage->replies as $reply)
                                @php
                                    $replyIsMe = $reply->sender_id === auth()->id();
                                @endphp
                                <div style="display:flex; flex-direction:column; align-items:{{ $replyIsMe ? 'flex-end' : 'flex-start' }}; max-width:85%; {{ $replyIsMe ? 'margin-inline-start:auto;' : 'margin-inline-end:auto;' }}">
                                    <div style="display:flex; align-items:center; gap:0.4rem; margin-bottom:0.25rem; font-size:0.72rem;">
                                        <strong style="color:{{ $replyIsMe ? 'var(--brand-primary)' : 'var(--text-primary)' }};">
                                            {{ $replyIsMe ? 'أنت (السانديك)' : ($reply->sender?->full_name ?? 'مستخدم') }}
                                        </strong>
                                        <span class="num" style="color:var(--text-muted); font-size:0.68rem;">
                                            {{ $reply->created_at->format('H:i - Y/m/d') }}
                                        </span>
                                    </div>
                                    <div style="padding:0.85rem 1.1rem; border-radius:14px; border-{{ $replyIsMe ? 'bottom-left' : 'bottom-right' }}-radius:3px; background:{{ $replyIsMe ? 'rgba(99, 102, 241, 0.12)' : 'var(--bg-primary)' }}; border:1px solid {{ $replyIsMe ? 'rgba(99, 102, 241, 0.25)' : 'var(--border-color)' }}; color:var(--text-primary); font-size:0.9rem; line-height:1.7; white-space:pre-wrap; word-break:break-word;">
                                        {{ $reply->body }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Target Metadata if Broadcast --}}
                    @if($isSentByMe && $selectedMessage->target_type !== 'INDIVIDUAL')
                        <div style="background:var(--bg-secondary); padding:0.75rem 1rem; border-radius:10px; border:1px solid var(--border-color); font-size:0.8rem; color:var(--text-secondary); margin-bottom:1.25rem;">
                            📢 <strong>المستهدفون:</strong> {{ $selectedMessage->recipient_label }}
                            (تم تسليمها لـ {{ $selectedMessage->recipients()->count() }} مستخدم).
                        </div>
                    @endif

                    {{-- Bottom Action Area: Chat Reply or Delivery Status --}}
                    @if($canReply)
                        {{-- Reply Form --}}
                        <div style="background:var(--bg-secondary); padding:1.15rem; border-radius:12px; border:1px solid var(--border-color);">
                            <h4 style="margin:0 0 0.6rem; font-size:0.88rem; font-weight:800; color:var(--text-primary); display:flex; align-items:center; gap:0.35rem;">
                                <span>✍️ {{ $hasReplies ? 'متابعة الدردشة والرد' : 'إرسال رد على الرسالة' }}</span>
                            </h4>
                            <form method="POST" action="{{ route('portal.messages.reply', $selectedMessage->id) }}">
                                @csrf
                                <div style="display:flex; gap:0.6rem; align-items:flex-end;">
                                    <div style="flex:1;">
                                        <textarea name="body" class="form-control" rows="2" placeholder="اكتب ردك هنا لمتابعة المحادثة..." required style="resize:vertical; min-height:48px; border-radius:10px;"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary" style="font-weight:700; padding:0.55rem 1.25rem; font-size:0.86rem; border-radius:10px; display:flex; align-items:center; gap:0.35rem; flex-shrink:0;">
                                        <span>إرسال</span>
                                        <span>✉️</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    @elseif($isSentByMe && !$hasReplies)
                        {{-- Sent message without replies: Clean delivery status banner, NO reply box! --}}
                        <div style="background:rgba(99, 102, 241, 0.05); border:1px dashed rgba(99, 102, 241, 0.25); padding:0.9rem 1.15rem; border-radius:12px; display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
                            <div style="display:flex; align-items:center; gap:0.6rem; color:var(--text-secondary); font-size:0.84rem;">
                                <span style="color:#6366f1; font-weight:800; font-size:1.1rem;">✓</span>
                                <div>
                                    <strong style="color:var(--text-primary);">تم إرسال هذه الرسالة بنجاح إلى:</strong>
                                    <span>{{ $selectedMessage->recipient_label }}</span>
                                </div>
                            </div>
                            <span class="badge badge-neutral" style="font-size:0.72rem; padding:0.2rem 0.5rem;">
                                بانتظار ردود المستلمين إن وجدت
                            </span>
                        </div>
                    @elseif($selectedMessage->isFromSuperAdmin())
                        {{-- Official Super Admin announcement banner --}}
                        <div style="background:rgba(99, 102, 241, 0.08); border:1px solid rgba(99, 102, 241, 0.25); padding:1rem; border-radius:12px; color:var(--text-secondary); font-size:0.84rem; display:flex; align-items:center; gap:0.6rem;">
                            <span>ℹ️</span>
                            <span>هذه رسالة إعلان رسمي مرسلة من الإدارة العامة للمنصة لكافة المسيرين. تم تأكيد استلامك وقراءتك لها بنجاح.</span>
                        </div>
                    @endif
                </div>
            @else
                <div class="card" style="padding:4rem 2rem; text-align:center; color:var(--text-muted);">
                    <div style="font-size:3.5rem; margin-bottom:0.75rem; opacity:0.8;">✉️</div>
                    <h3 style="margin:0 0 0.4rem; font-size:1.15rem; font-weight:800; color:var(--text-primary);">اختر رسالة من القائمة لعرض تفاصيلها</h3>
                    <p style="margin:0; font-size:0.86rem; color:var(--text-secondary);">
                        يمكنك الاطلاع على تفاصيل الرسالة، إعلانات الإدارة، أو الرد الفوري على استفسارات السكان.
                    </p>
                </div>
            @endif
        </div>
    </div>

    {{-- Compose New Message Modal --}}
    <div class="modal-overlay" x-show="composeModal" x-cloak style="z-index:99995;">
        <div class="modal-dialog" style="max-width:580px;" @click.away="composeModal = false">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title" style="margin:0;">✉️ كتابة وإرسال رسالة جديدة</h3>
                    <p style="margin:0.25rem 0 0; font-size:0.82rem; color:var(--text-secondary);">
                        إرسال رسالة عامة لكافة السكان أو تخصيصها لساكن أو شقق بعينها
                    </p>
                </div>
                <button type="button" class="icon-btn" @click="composeModal = false">✕</button>
            </div>

            <form method="POST" action="{{ route('portal.messages.store') }}">
                @csrf
                <div class="modal-body">
                    {{-- Target Type Selector --}}
                    <div class="form-group">
                        <label class="form-label" style="font-weight:700;">المستهدفون بالرسالة</label>
                        <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:0.5rem;">
                            <label style="border:2px solid; border-color:var(--border-color); border-radius:10px; padding:0.65rem 0.5rem; text-align:center; cursor:pointer; transition:all 0.15s;"
                                   :style="composeData.targetType === 'ALL_RESIDENTS' ? 'border-color:var(--brand-primary); background:rgba(99, 102, 241, 0.08); font-weight:800;' : ''">
                                <input type="radio" name="target_type" value="ALL_RESIDENTS" x-model="composeData.targetType" style="display:none;">
                                <div style="font-size:1.2rem;">📢</div>
                                <div style="font-size:0.8rem; margin-top:2px;">كافة السكان</div>
                            </label>

                            <label style="border:2px solid; border-color:var(--border-color); border-radius:10px; padding:0.65rem 0.5rem; text-align:center; cursor:pointer; transition:all 0.15s;"
                                   :style="composeData.targetType === 'INDIVIDUAL' ? 'border-color:var(--brand-primary); background:rgba(99, 102, 241, 0.08); font-weight:800;' : ''">
                                <input type="radio" name="target_type" value="INDIVIDUAL" x-model="composeData.targetType" style="display:none;">
                                <div style="font-size:1.2rem;">👤</div>
                                <div style="font-size:0.8rem; margin-top:2px;">ساكن محدد</div>
                            </label>

                            <label style="border:2px solid; border-color:var(--border-color); border-radius:10px; padding:0.65rem 0.5rem; text-align:center; cursor:pointer; transition:all 0.15s;"
                                   :style="composeData.targetType === 'GROUP' ? 'border-color:var(--brand-primary); background:rgba(99, 102, 241, 0.08); font-weight:800;' : ''">
                                <input type="radio" name="target_type" value="GROUP" x-model="composeData.targetType" style="display:none;">
                                <div style="font-size:1.2rem;">👥</div>
                                <div style="font-size:0.8rem; margin-top:2px;">مجموعة شقق</div>
                            </label>
                        </div>
                    </div>

                    {{-- Conditional: Single Resident Selector --}}
                    <div class="form-group" x-show="composeData.targetType === 'INDIVIDUAL'" x-cloak>
                        <label class="form-label" style="font-weight:700;">اختر الساكن المستهدف</label>
                        <select name="target_ids[]" class="form-select" :disabled="composeData.targetType !== 'INDIVIDUAL'">
                            <option value="">-- اختر الساكن --</option>
                            @foreach($residents as $res)
                                @php
                                    $aptUnits = $res->apartments->map(fn($a) => $a->formatted_unit)->join('، ');
                                @endphp
                                <option value="{{ $res->id }}">
                                    {{ $res->full_name }} {{ $aptUnits ? '(' . $aptUnits . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Conditional: Multi-apartment Group Selector --}}
                    <div class="form-group" x-show="composeData.targetType === 'GROUP'" x-cloak>
                        <label class="form-label" style="font-weight:700;">اختر الشقق المستهدفة (يمكن تحديد عدة شقق)</label>
                        <div style="max-height:160px; overflow-y:auto; border:1px solid var(--border-color); border-radius:10px; padding:0.75rem; background:var(--bg-secondary);">
                            @foreach($buildings as $b)
                                <div style="font-weight:800; font-size:0.8rem; color:var(--brand-primary); margin-bottom:0.4rem;">
                                    🏢 {{ $b->name }}{{ $b->block ? ' - بلوك ' . $b->block : '' }}
                                </div>
                                <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(100px, 1fr)); gap:0.4rem; margin-bottom:0.75rem;">
                                    @foreach($b->apartments as $apt)
                                        <label style="display:flex; align-items:center; gap:0.4rem; font-size:0.78rem; cursor:pointer; background:var(--bg-surface); padding:0.35rem 0.5rem; border-radius:6px; border:1px solid var(--border-color);">
                                            <input type="checkbox" name="target_ids[]" value="{{ $apt->id }}" style="accent-color:var(--brand-primary);" :disabled="composeData.targetType !== 'GROUP'">
                                            <span>{{ $apt->formatted_unit }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
                        {{-- Category --}}
                        <div class="form-group">
                            <label class="form-label" style="font-weight:700;">تصنيف الرسالة</label>
                            <select name="category" class="form-select">
                                <option value="GENERAL">عامة</option>
                                <option value="ANNOUNCEMENT">إعلان رسمي</option>
                                <option value="FINANCIAL">إشعار بالواجبات والماليات</option>
                                <option value="MAINTENANCE">أشغال وصيانة</option>
                                <option value="INQUIRY">استفسار ومتابعة</option>
                            </select>
                        </div>

                        {{-- Priority --}}
                        <div class="form-group">
                            <label class="form-label" style="font-weight:700;">درجة الأهمية</label>
                            <select name="priority" class="form-select">
                                <option value="NORMAL">عادي</option>
                                <option value="HIGH">⚠️ هام</option>
                                <option value="URGENT">🚨 عاجل جداً</option>
                            </select>
                        </div>
                    </div>

                    {{-- Body --}}
                    <div class="form-group">
                        <label class="form-label" style="font-weight:700;">نص الرسالة</label>
                        <textarea name="body" class="form-control" rows="5" placeholder="اكتب تفاصيل الرسالة هنا..." required style="resize:vertical;"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="composeModal = false">إلغاء</button>
                    <button type="submit" class="btn btn-primary" style="font-weight:700;">إرسال الرسالة الآن ✉️</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function messagesManager() {
    return {
        composeModal: false,
        composeData: {
            targetType: 'ALL_RESIDENTS',
        },

        init() {
            // Initialization logic if needed
        },

        openComposeModal() {
            this.composeData.targetType = 'ALL_RESIDENTS';
            this.composeModal = true;
        }
    };
}
</script>
@endsection
