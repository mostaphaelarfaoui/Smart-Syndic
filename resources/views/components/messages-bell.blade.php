{{-- Smart Syndic Modern Message Center Header Icon Component --}}
<div class="message-dropdown-wrapper" 
     x-data="messageDropdown()" 
     x-init="init()"
     @click.outside="open = false"
     style="position: relative; display: inline-block;">

    {{-- Messages Trigger Button --}}
    <button type="button" 
            class="message-bell-btn" 
            @click="toggleDropdown()" 
            :class="{ 'active': open }"
            :title="unreadCount > 0 ? ('لديك ' + unreadCount + ' رسالة غير مقروءة') : 'الرسائل والمحادثات'"
            aria-label="الرسائل والمحادثات"
            :aria-expanded="open.toString()">
        
        {{-- Envelope SVG Icon --}}
        <svg class="message-icon" width="21" height="21" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
        </svg>

        {{-- Unread Badge --}}
        <span x-show="unreadCount > 0" 
              x-transition:enter="transition ease-out duration-300"
              x-transition:enter-start="opacity-0 scale-50"
              x-transition:enter-end="opacity-100 scale-100"
              x-transition:leave="transition ease-in duration-200"
              x-transition:leave-start="opacity-100 scale-100"
              x-transition:leave-end="opacity-0 scale-50"
              class="message-badge"
              x-text="unreadCount > 99 ? '99+' : unreadCount"
              style="display: none;">
        </span>
    </button>

    {{-- Messages Dropdown Window --}}
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
         class="message-dropdown-menu"
         style="display: none;"
         @keydown.escape.window="open = false">

        {{-- Dropdown Header --}}
        <div class="message-dropdown-header">
            <div class="header-title-box">
                <span class="header-icon">✉️</span>
                <span class="header-title">صندوق الرسائل</span>
                <span x-show="unreadCount > 0" class="header-unread-pill" x-text="unreadCount + ' جديدة'"></span>
            </div>

            <div class="header-actions">
                <button type="button" 
                        class="header-action-btn" 
                        @click="markAllAsRead()"
                        x-show="unreadCount > 0"
                        title="تحديد الكل كمقروء">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>مقروء للكل</span>
                </button>

                <a href="{{ route('portal.messages.index') }}" 
                   class="header-action-btn header-compose-btn"
                   title="كتابة رسالة جديدة">
                    <span>➕ إنشاء</span>
                </a>
            </div>
        </div>

        {{-- Filter Tabs --}}
        <div class="message-filter-tabs">
            <button type="button" 
                    class="filter-tab-btn" 
                    :class="{ 'active': filter === 'all' }" 
                    @click="filter = 'all'">
                <span>الكل</span>
                <span class="tab-count" x-text="messages.length"></span>
            </button>
            <button type="button" 
                    class="filter-tab-btn" 
                    :class="{ 'active': filter === 'unread' }" 
                    @click="filter = 'unread'">
                <span>غير المقروءة</span>
                <span class="tab-count tab-count-unread" x-text="unreadCount"></span>
            </button>
            <button type="button" 
                    class="filter-tab-btn" 
                    :class="{ 'active': filter === 'admin' }" 
                    @click="filter = 'admin'">
                <span>إعلانات الإدارة</span>
                <span class="tab-count" x-text="messages.filter(m => m.is_admin).length"></span>
            </button>
        </div>

        {{-- Dropdown Body / Messages List --}}
        <div class="message-dropdown-body">
            <template x-if="loading && messages.length === 0">
                <div class="message-loading-state">
                    <div class="message-spinner"></div>
                    <p>جاري تحميل الرسائل...</p>
                </div>
            </template>

            <template x-if="!loading && filteredMessages.length === 0">
                <div class="message-empty-state">
                    <span class="empty-icon">📭</span>
                    <h5 class="empty-title">لا توجد رسائل حالياً</h5>
                    <p class="empty-desc" x-text="filter === 'unread' ? 'لقد قرأت جميع الرسائل الواردة!' : 'صندوق الرسائل فارغ حالياً'"></p>
                </div>
            </template>

            <template x-for="item in filteredMessages" :key="item.id">
                <a :href="'{{ url('/portal/messages') }}?selected=' + item.id"
                   class="message-item" 
                   :class="{ 'unread': !item.is_read, 'admin-announcement': item.is_admin }">
                    
                    {{-- Sender Icon / Avatar --}}
                    <div class="sender-avatar" :class="item.is_admin ? 'admin-avatar' : 'resident-avatar'">
                        <span x-text="item.is_admin ? '🛡️' : '👤'"></span>
                    </div>

                    {{-- Message Details --}}
                    <div class="message-content">
                        <div class="message-item-header">
                            <span class="sender-name" x-text="item.sender_name"></span>
                            <span class="sender-badge" :class="item.is_admin ? 'badge-admin' : 'badge-resident'" x-text="item.sender_badge"></span>
                            <span class="message-time" x-text="item.time_ago"></span>
                        </div>
                        <h5 class="message-subject" x-text="item.subject"></h5>
                        <p class="message-snippet" x-text="item.body_snippet"></p>
                    </div>

                    {{-- Unread Dot Indicator --}}
                    <span x-show="!item.is_read" class="unread-dot"></span>
                </a>
            </template>
        </div>

        {{-- Dropdown Footer --}}
        <div class="message-dropdown-footer">
            <a href="{{ route('portal.messages.index') }}" class="view-all-link">
                <span>فتح مركز الرسائل والمحادثات</span>
                <span class="arrow">⬅️</span>
            </a>
        </div>
    </div>
</div>

<style>
/* Modern Message Center Styles */
.message-dropdown-wrapper {
    position: relative;
    display: inline-block;
}

.message-bell-btn {
    position: relative;
    width: 40px;
    height: 40px;
    border-radius: 10px;
    border: 1px solid var(--border-color, rgba(226, 232, 240, 0.8));
    background: var(--bg-surface, #ffffff);
    color: var(--text-secondary, #64748b);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    outline: none;
    padding: 0;
}

.message-bell-btn:hover,
.message-bell-btn.active {
    background: var(--bg-secondary, #f8fafc);
    color: var(--brand-primary, #4f46e5);
    border-color: var(--brand-primary, #4f46e5);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.12);
}

.message-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    background: linear-gradient(135deg, #6366f1, #4f46e5);
    color: #ffffff;
    font-size: 0.68rem;
    font-weight: 800;
    min-width: 19px;
    height: 19px;
    line-height: 19px;
    border-radius: 999px;
    text-align: center;
    padding: 0 4px;
    border: 2px solid var(--bg-surface, #ffffff);
    box-shadow: 0 2px 6px rgba(79, 70, 229, 0.4);
}

.message-dropdown-menu {
    position: absolute;
    top: calc(100% + 10px);
    left: 0;
    width: 380px;
    max-width: 92vw;
    background: var(--bg-surface, #ffffff);
    border: 1px solid var(--border-color, rgba(226, 232, 240, 0.8));
    border-radius: 16px;
    box-shadow: 0 15px 35px -5px rgba(0, 0, 0, 0.15), 0 5px 15px rgba(0, 0, 0, 0.05);
    z-index: 10000;
    overflow: hidden;
    direction: rtl;
    text-align: right;
    font-family: inherit;
}

.message-dropdown-header {
    padding: 1rem 1.15rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid var(--border-color, rgba(226, 232, 240, 0.8));
    background: var(--bg-surface, #ffffff);
}

.header-title-box {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.header-title {
    font-size: 0.98rem;
    font-weight: 800;
    color: var(--text-primary, #0f172a);
}

.header-unread-pill {
    background: rgba(99, 102, 241, 0.12);
    color: var(--brand-primary, #4f46e5);
    font-size: 0.72rem;
    font-weight: 800;
    padding: 0.15rem 0.55rem;
    border-radius: 999px;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.header-action-btn {
    background: none;
    border: none;
    color: var(--brand-primary, #4f46e5);
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.3rem 0.6rem;
    border-radius: 6px;
    transition: background 0.15s;
    text-decoration: none;
}

.header-action-btn:hover {
    background: rgba(99, 102, 241, 0.1);
}

.header-compose-btn {
    background: var(--brand-primary, #4f46e5);
    color: #ffffff !important;
}

.header-compose-btn:hover {
    background: #4338ca !important;
}

.message-filter-tabs {
    display: flex;
    padding: 0.5rem 0.75rem;
    background: var(--bg-secondary, #f8fafc);
    border-bottom: 1px solid var(--border-color, rgba(226, 232, 240, 0.8));
    gap: 0.4rem;
}

.filter-tab-btn {
    flex: 1;
    background: none;
    border: none;
    padding: 0.4rem 0.6rem;
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--text-secondary, #64748b);
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    transition: all 0.15s;
}

.filter-tab-btn.active {
    background: var(--bg-surface, #ffffff);
    color: var(--brand-primary, #4f46e5);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.tab-count {
    background: rgba(148, 163, 184, 0.2);
    font-size: 0.68rem;
    padding: 0.1rem 0.45rem;
    border-radius: 999px;
}

.tab-count-unread {
    background: rgba(99, 102, 241, 0.18);
    color: var(--brand-primary, #4f46e5);
    font-weight: 800;
}

.message-dropdown-body {
    max-height: 380px;
    overflow-y: auto;
}

.message-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.85rem 1.15rem;
    border-bottom: 1px solid var(--border-color, rgba(226, 232, 240, 0.6));
    text-decoration: none;
    color: inherit;
    transition: background 0.15s;
    position: relative;
}

.message-item:hover {
    background: var(--bg-secondary, #f8fafc);
}

.message-item.unread {
    background: rgba(99, 102, 241, 0.04);
}

.message-item.admin-announcement {
    border-right: 3px solid #6366f1;
}

.sender-avatar {
    width: 36px;
    height: 36px;
    min-width: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
}

.admin-avatar {
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(79, 70, 229, 0.3));
    border: 1px solid rgba(99, 102, 241, 0.3);
}

.resident-avatar {
    background: rgba(16, 185, 129, 0.12);
    border: 1px solid rgba(16, 185, 129, 0.25);
}

.message-content {
    flex: 1;
    min-width: 0;
}

.message-item-header {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    flex-wrap: wrap;
    margin-bottom: 0.2rem;
}

.sender-name {
    font-size: 0.82rem;
    font-weight: 800;
    color: var(--text-primary, #0f172a);
}

.sender-badge {
    font-size: 0.66rem;
    font-weight: 700;
    padding: 0.1rem 0.4rem;
    border-radius: 4px;
}

.badge-admin {
    background: rgba(99, 102, 241, 0.15);
    color: #6366f1;
}

.badge-resident {
    background: rgba(16, 185, 129, 0.15);
    color: #059669;
}

.message-time {
    font-size: 0.7rem;
    color: var(--text-muted, #94a3b8);
    margin-right: auto;
}

.message-subject {
    margin: 0;
    font-size: 0.88rem;
    font-weight: 700;
    color: var(--text-primary, #0f172a);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.message-snippet {
    margin: 0.15rem 0 0;
    font-size: 0.78rem;
    color: var(--text-secondary, #64748b);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.4;
}

.unread-dot {
    width: 8px;
    height: 8px;
    min-width: 8px;
    border-radius: 50%;
    background: var(--brand-primary, #4f46e5);
    margin-top: 0.4rem;
}

.message-empty-state,
.message-loading-state {
    padding: 2.5rem 1.5rem;
    text-align: center;
    color: var(--text-muted, #94a3b8);
}

.empty-icon {
    font-size: 2.2rem;
    display: block;
    margin-bottom: 0.4rem;
}

.empty-title {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--text-primary, #0f172a);
}

.empty-desc {
    margin: 0.25rem 0 0;
    font-size: 0.78rem;
}

.message-dropdown-footer {
    padding: 0.75rem 1rem;
    background: var(--bg-secondary, #f8fafc);
    border-top: 1px solid var(--border-color, rgba(226, 232, 240, 0.8));
    text-align: center;
}

.view-all-link {
    font-size: 0.82rem;
    font-weight: 800;
    color: var(--brand-primary, #4f46e5);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
}

.view-all-link:hover {
    text-decoration: underline;
}
</style>

<script>
function messageDropdown() {
    return {
        open: false,
        loading: false,
        unreadCount: 0,
        messages: [],
        filter: 'all',

        init() {
            this.fetchMessages();
            // Silent polling every 30 seconds
            setInterval(() => {
                if (!this.open) {
                    this.fetchUnreadCountOnly();
                }
            }, 30000);
        },

        toggleDropdown() {
            this.open = !this.open;
            if (this.open) {
                this.fetchMessages();
            }
        },

        get filteredMessages() {
            if (this.filter === 'unread') {
                return this.messages.filter(m => !m.is_read);
            }
            if (this.filter === 'admin') {
                return this.messages.filter(m => m.is_admin);
            }
            return this.messages;
        },

        async fetchMessages() {
            this.loading = true;
            try {
                const res = await fetch('{{ route('portal.messages.quick-list') }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (res.ok) {
                    const data = await res.json();
                    this.messages = data.messages || [];
                    this.unreadCount = data.unread_count || 0;
                }
            } catch (err) {
                console.error('Failed to fetch messages:', err);
            } finally {
                this.loading = false;
            }
        },

        async fetchUnreadCountOnly() {
            try {
                const res = await fetch('{{ route('portal.messages.unread-count') }}', {
                    headers: { 'Accept': 'application/json' }
                });
                if (res.ok) {
                    const data = await res.json();
                    this.unreadCount = data.unread_count || 0;
                }
            } catch (e) {}
        },

        async markAllAsRead() {
            try {
                const res = await fetch('{{ route('portal.messages.mark-all-read') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                if (res.ok) {
                    this.messages.forEach(m => m.is_read = true);
                    this.unreadCount = 0;
                }
            } catch (err) {
                console.error('Failed to mark messages as read:', err);
            }
        }
    };
}
</script>
