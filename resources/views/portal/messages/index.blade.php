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
                        $recipientPivot = $isInbox ? $msg->recipients->firstWhere('user_id', auth()->id()) : null;
                        $isUnread = $isInbox && $recipientPivot && ! $recipientPivot->is_read;
                        $isSelected = $selectedMessage && $selectedMessage->id === $msg->id;
                        $isAdmin = $msg->isFromSuperAdmin();
                        $isResident = $msg->isFromResident();
                    @endphp

                    <a href="{{ route('portal.messages.index', ['tab' => $tab, 'filter' => $filter, 'selected' => $msg->id]) }}" 
                       class="message-list-row {{ $isSelected ? 'active-row' : '' }} {{ $isUnread ? 'unread-row' : '' }}" 
                       style="display:flex; align-items:flex-start; gap:0.75rem; padding:0.9rem 1.1rem; border-bottom:1px solid var(--border-color); text-decoration:none; color:inherit; transition:background 0.15s; position:relative; background:{{ $isSelected ? 'rgba(99, 102, 241, 0.08)' : ($isUnread ? 'rgba(99, 102, 241, 0.03)' : 'transparent') }}; border-right:4px solid {{ $isSelected ? 'var(--brand-primary)' : ($isAdmin ? '#6366f1' : 'transparent') }};">
                        
                        {{-- Avatar --}}
                        <div style="width:40px; height:40px; min-width:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.15rem; background:{{ $isAdmin ? 'linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(79, 70, 229, 0.3))' : ($isResident ? 'rgba(16, 185, 129, 0.15)' : 'rgba(99, 102, 241, 0.15)') }};">
                            {{ $isAdmin ? '🛡️' : ($isResident ? '👤' : '📤') }}
                        </div>

                        {{-- Details --}}
                        <div style="flex:1; min-width:0;">
                            <div style="display:flex; align-items:center; justify-content:space-between; gap:0.35rem; margin-bottom:0.2rem;">
                                <div style="display:flex; align-items:center; gap:0.35rem; overflow:hidden;">
                                    <strong style="font-size:0.84rem; color:var(--text-primary); white-space:nowrap; text-overflow:ellipsis; overflow:hidden;">
                                        @if($isInbox)
                                            {{ $isAdmin ? 'الإدارة العامة للمنصة' : $msg->sender?->full_name }}
                                        @else
                                            إلى: {{ $msg->target_type === 'ALL_RESIDENTS' ? 'كافة سكان الإقامة' : ($msg->target_metadata['recipient_name'] ?? ($msg->target_type === 'GROUP' ? 'مجموعة سكان' : 'ساكن محدد')) }}
                                        @endif
                                    </strong>
                                    @if($isResident && isset($msg->target_metadata['unit_number']))
                                        <span class="badge badge-neutral num" style="font-size:0.68rem; padding:0.1rem 0.35rem;">
                                            {{ format_unit_label($msg->target_metadata['unit_number']) }}
                                        </span>
                                    @endif
                                </div>
                                <span class="num" style="font-size:0.7rem; color:var(--text-muted); flex-shrink:0;">
                                    {{ $msg->created_at->diffForHumans() }}
                                </span>
                            </div>

                            <div style="font-size:0.86rem; font-weight:{{ $isUnread ? '800' : '600' }}; color:var(--text-primary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                {{ $msg->subject }}
                            </div>

                            <div style="font-size:0.78rem; color:var(--text-secondary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-top:2px;">
                                {{ \Illuminate\Support\Str::limit($msg->body, 60) }}
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
                <div class="card" style="padding:1.5rem;">
                    {{-- Header of selected message --}}
                    <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; flex-wrap:wrap; padding-bottom:1.25rem; border-bottom:1px solid var(--border-color); margin-bottom:1.25rem;">
                        <div style="display:flex; align-items:center; gap:0.9rem;">
                            <div style="width:48px; height:48px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.5rem; background:{{ $selectedMessage->isFromSuperAdmin() ? 'linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(79, 70, 229, 0.3))' : 'rgba(16, 185, 129, 0.15)' }};">
                                {{ $selectedMessage->isFromSuperAdmin() ? '🛡️' : '👤' }}
                            </div>
                            <div>
                                <h3 style="margin:0; font-size:1.15rem; font-weight:800; color:var(--text-primary);">
                                    {{ $selectedMessage->subject }}
                                </h3>
                                <div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap; margin-top:0.35rem; font-size:0.8rem; color:var(--text-secondary);">
                                    @if($selectedMessage->sender_id === auth()->id())
                                        <span>
                                            <strong>إلى:</strong> 
                                            {{ $selectedMessage->target_type === 'ALL_RESIDENTS' ? 'كافة سكان الإقامة' : ($selectedMessage->target_metadata['recipient_name'] ?? 'ساكن محدد') }}
                                        </span>
                                    @else
                                        <span>
                                            <strong>من:</strong> 
                                            {{ $selectedMessage->isFromSuperAdmin() ? 'الإدارة العامة للمنصة' : $selectedMessage->sender?->full_name }}
                                        </span>
                                    @endif
                                    @if(isset($selectedMessage->target_metadata['unit_number']))
                                        <span>• <strong>{{ format_unit_label($selectedMessage->target_metadata['unit_number']) }}</strong></span>
                                    @endif
                                    @if($selectedMessage->sender_id !== auth()->id() && $selectedMessage->sender?->phone)
                                        <span>• هاتف: <strong class="num">{{ $selectedMessage->sender->phone }}</strong></span>
                                    @endif
                                    <span>• 🕒 {{ $selectedMessage->created_at->format('Y/m/d H:i') }}</span>
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

                    {{-- Message Body --}}
                    <div style="font-size:0.95rem; line-height:1.75; color:var(--text-primary); white-space:pre-wrap; padding:0.5rem 0 1.5rem; border-bottom:1px solid var(--border-color); margin-bottom:1.5rem;">
                        {{ $selectedMessage->body }}
                    </div>

                    {{-- Target Metadata if Broadcast --}}
                    @if($selectedMessage->sender_id === auth()->id())
                        <div style="background:var(--bg-secondary); padding:0.85rem 1.1rem; border-radius:10px; border:1px solid var(--border-color); font-size:0.82rem; color:var(--text-secondary); margin-bottom:1.5rem;">
                            📢 <strong>المستهدفون بالرسالة:</strong> 
                            @if($selectedMessage->target_type === 'ALL_RESIDENTS')
                                كافة سكان الإقامة
                            @elseif($selectedMessage->target_type === 'INDIVIDUAL')
                                {{ $selectedMessage->target_metadata['recipient_name'] ?? 'ساكن محدد' }}
                            @else
                                مجموعة شقق ({{ isset($selectedMessage->target_metadata['targeted_units']) ? implode(', ', $selectedMessage->target_metadata['targeted_units']) : 'محددة' }})
                            @endif
                            (تم تسليمها لـ {{ $selectedMessage->recipients()->count() }} مستخدم).
                        </div>
                    @endif

                    {{-- Conversation Replies (Threads) --}}
                    @if($selectedMessage->replies->isNotEmpty())
                        <div style="margin-bottom:1.5rem;">
                            <h4 style="font-size:0.95rem; font-weight:800; color:var(--text-primary); margin-bottom:0.75rem; display:flex; align-items:center; gap:0.4rem;">
                                <span>💬 الردود والمتابعة ({{ $selectedMessage->replies->count() }})</span>
                            </h4>

                            <div style="display:flex; flex-direction:column; gap:0.85rem;">
                                @foreach($selectedMessage->replies as $reply)
                                    <div style="padding:1rem; border-radius:12px; background:{{ $reply->sender_id === auth()->id() ? 'rgba(99, 102, 241, 0.07)' : 'var(--bg-secondary)' }}; border:1px solid var(--border-color);">
                                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:0.35rem;">
                                            <strong style="font-size:0.84rem; color:var(--text-primary);">
                                                {{ $reply->sender_id === auth()->id() ? 'أنت (السانديك)' : $reply->sender?->full_name }}
                                            </strong>
                                            <span class="num" style="font-size:0.72rem; color:var(--text-muted);">
                                                {{ $reply->created_at->format('Y/m/d H:i') }}
                                            </span>
                                        </div>
                                        <div style="font-size:0.88rem; line-height:1.6; color:var(--text-primary); white-space:pre-wrap;">
                                            {{ $reply->body }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Quick Reply Form --}}
                    @if(!$selectedMessage->isFromSuperAdmin())
                        <div style="background:var(--bg-secondary); padding:1.25rem; border-radius:12px; border:1px solid var(--border-color);">
                            <h4 style="margin:0 0 0.75rem; font-size:0.92rem; font-weight:800; color:var(--text-primary); display:flex; align-items:center; gap:0.35rem;">
                                <span>✍️ إرسال رد مباشر</span>
                            </h4>
                            <form method="POST" action="{{ route('portal.messages.reply', $selectedMessage->id) }}">
                                @csrf
                                <div class="form-group" style="margin-bottom:0.75rem;">
                                    <textarea name="body" class="form-control" rows="3" placeholder="اكتب ردك هنا..." required style="resize:vertical;"></textarea>
                                </div>
                                <div style="display:flex; justify-content:flex-end;">
                                    <button type="submit" class="btn btn-primary" style="font-weight:700; padding:0.45rem 1.1rem; font-size:0.85rem;">
                                        إرسال الرد ✉️
                                    </button>
                                </div>
                            </form>
                        </div>
                    @else
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

                    {{-- Subject --}}
                    <div class="form-group">
                        <label class="form-label" style="font-weight:700;">موضوع الرسالة</label>
                        <input type="text" name="subject" class="form-control" placeholder="مثال: إشعار بموعد الجمع العام للإقامة" required>
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
