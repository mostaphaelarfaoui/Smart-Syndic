{{-- Smart Syndic Modern Facebook/Instagram Style Notification Bell Component --}}
<div class="notification-dropdown-wrapper" 
     x-data="notificationDropdown()" 
     x-init="init()"
     @click.outside="open = false"
     style="position: relative; display: inline-block;">

    {{-- Bell Trigger Button --}}
    <button type="button" 
            class="notification-bell-btn" 
            @click="toggleDropdown()" 
            :class="{ 'active': open }"
            :title="unreadCount > 0 ? ('لديك ' + unreadCount + ' إشعار غير مقروء') : 'الإشعارات'"
            aria-label="الإشعارات"
            :aria-expanded="open.toString()">
        
        {{-- Bell SVG Icon --}}
        <svg class="bell-icon" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>

        {{-- Unread Badge: ONLY visible when unreadCount > 0. When 0, completely disappears (بدون رقم) --}}
        <span x-show="unreadCount > 0" 
              x-transition:enter="transition ease-out duration-300"
              x-transition:enter-start="opacity-0 scale-50"
              x-transition:enter-end="opacity-100 scale-100"
              x-transition:leave="transition ease-in duration-200"
              x-transition:leave-start="opacity-100 scale-100"
              x-transition:leave-end="opacity-0 scale-50"
              class="notification-badge"
              x-text="unreadCount > 99 ? '99+' : unreadCount"
              style="display: none;">
        </span>
    </button>

    {{-- Notifications Dropdown Window --}}
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
         class="notification-dropdown-menu"
         style="display: none;"
         @keydown.escape.window="open = false">

        {{-- Dropdown Header --}}
        <div class="notification-dropdown-header">
            <div class="header-title-box">
                <span class="header-icon">🔔</span>
                <span class="header-title">الإشعارات</span>
                <span x-show="unreadCount > 0" class="header-unread-pill" x-text="unreadCount + ' جديدة'"></span>
            </div>

            <div class="header-actions">
                <button type="button" 
                        class="header-action-btn" 
                        @click="markAllAsRead()"
                        x-show="unreadCount > 0"
                        title="تحديد الكل كمقروء">
                    <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>تحديد الكل كمقروء</span>
                </button>

                <button type="button" 
                        class="header-icon-action-btn" 
                        @click="fetchNotifications(true)" 
                        :class="{ 'spinning': loading }"
                        title="تحديث الإشعارات">
                    <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Filter Tabs (All / Unread) --}}
        <div class="notification-filter-tabs">
            <button type="button" 
                    class="filter-tab-btn" 
                    :class="{ 'active': filter === 'all' }" 
                    @click="filter = 'all'">
                <span>الكل</span>
                <span class="tab-count" x-text="notifications.length"></span>
            </button>
            <button type="button" 
                    class="filter-tab-btn" 
                    :class="{ 'active': filter === 'unread' }" 
                    @click="filter = 'unread'">
                <span>غير المقروءة</span>
                <span class="tab-count tab-count-unread" x-text="unreadCount"></span>
            </button>
        </div>

        {{-- Dropdown Body / Notifications List --}}
        <div class="notification-dropdown-body">
            {{-- Loading State --}}
            <div x-show="loading && notifications.length === 0" class="notification-state-box">
                <div class="notif-spinner"></div>
                <p>جاري جلب الإشعارات...</p>
            </div>

            {{-- Empty State --}}
            <div x-show="!loading && filteredNotifications.length === 0" class="notification-empty-state">
                <div class="empty-icon-wrap">
                    <svg width="36" height="36" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" 
                              d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                </div>
                <h4 class="empty-title">لا توجد إشعارات</h4>
                <p class="empty-desc" x-show="filter === 'unread'">جميع الإشعارات مقروءة حالياً.</p>
                <p class="empty-desc" x-show="filter === 'all'">ستظهر هنا تنبيهات النظام، والمستجدات، والفواتير فور وصولها.</p>
            </div>

            {{-- List of Notifications --}}
            <div class="notification-items-list" x-show="filteredNotifications.length > 0">
                <template x-for="item in filteredNotifications" :key="item.id">
                    <div class="notification-item" 
                         :class="{ 'unread': !item.is_read }"
                         @click="handleItemClick(item)">
                        
                        {{-- Icon Avatar --}}
                        <div class="notif-avatar" :class="'type-' + item.type">
                            <span x-text="item.icon || '🔔'"></span>
                        </div>

                        {{-- Main Content --}}
                        <div class="notif-content">
                            <div class="notif-head">
                                <h5 class="notif-title" x-text="item.title"></h5>
                                <span class="notif-time" x-text="item.time_ago"></span>
                            </div>
                            <p class="notif-message" x-text="item.message"></p>
                        </div>

                        {{-- Right Status & Actions --}}
                        <div class="notif-status-col" @click.stop>
                            {{-- Blue unread glowing dot --}}
                            <span x-show="!item.is_read" class="unread-glow-dot" title="غير مقروء"></span>

                            {{-- Delete action button --}}
                            <button type="button" 
                                    class="notif-delete-btn" 
                                    @click="deleteNotification(item.id)" 
                                    title="حذف هذا الإشعار">
                                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Dropdown Footer --}}
        <div class="notification-dropdown-footer" x-show="notifications.length > 0">
            <button type="button" class="footer-clear-btn" @click="clearAllNotifications()">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                <span>مسح كل الإشعارات</span>
            </button>
            <span class="footer-note" x-text="notifications.length + ' إشعار في السجل'"></span>
        </div>
    </div>
</div>

{{-- Modern Styles for Notification Bell & Dropdown --}}
<style>
.notification-dropdown-wrapper {
    position: relative;
    display: inline-block;
}

/* Bell Trigger Button */
.notification-bell-btn {
    position: relative;
    width: 40px;
    height: 40px;
    border-radius: var(--radius-full, 9999px);
    background: var(--bg-surface-elevated, #1f2937);
    border: 1px solid var(--border-color, rgba(255, 255, 255, 0.1));
    color: var(--text-primary, #f1f5f9);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
}

.notification-bell-btn:hover,
.notification-bell-btn.active {
    background: var(--brand-primary-light, rgba(13, 148, 136, 0.15));
    border-color: var(--brand-primary, #0d9488);
    color: var(--brand-primary, #0d9488);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(13, 148, 136, 0.2);
}

.notification-bell-btn .bell-icon {
    transition: transform 0.2s ease;
}

.notification-bell-btn:hover .bell-icon {
    animation: bellShake 0.6s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
}

@keyframes bellShake {
    0%, 100% { transform: rotate(0); }
    20% { transform: rotate(15deg); }
    40% { transform: rotate(-12deg); }
    60% { transform: rotate(8deg); }
    80% { transform: rotate(-4deg); }
}

/* Red Count Badge */
.notification-badge {
    position: absolute;
    top: -4px;
    left: -4px;
    min-width: 19px;
    height: 19px;
    padding: 0 5px;
    border-radius: 9999px;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: #ffffff;
    font-size: 0.68rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid var(--bg-surface, #111827);
    box-shadow: 0 2px 6px rgba(239, 68, 68, 0.45);
    animation: badgePulse 2s infinite;
    pointer-events: none;
    line-height: 1;
}

@keyframes badgePulse {
    0%, 100% { transform: scale(1); box-shadow: 0 2px 6px rgba(239, 68, 68, 0.45); }
    50% { transform: scale(1.08); box-shadow: 0 3px 10px rgba(239, 68, 68, 0.7); }
}

/* Dropdown Menu Container */
.notification-dropdown-menu {
    position: absolute;
    top: calc(100% + 10px);
    left: 0;
    width: 390px;
    max-width: calc(100vw - 2rem);
    background: var(--bg-surface, #111827);
    border: 1px solid var(--border-color, rgba(255, 255, 255, 0.12));
    border-radius: var(--radius-lg, 18px);
    box-shadow: 0 20px 40px -8px rgba(0, 0, 0, 0.35), 0 0 1px 1px var(--border-color, rgba(255, 255, 255, 0.05));
    backdrop-filter: blur(16px);
    z-index: 99999;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

/* Header */
.notification-dropdown-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem 0.85rem;
    border-bottom: 1px solid var(--border-subtle, rgba(255, 255, 255, 0.07));
    background: var(--bg-surface-elevated, #1f2937);
}

.header-title-box {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.header-icon {
    font-size: 1.15rem;
}

.header-title {
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--text-primary, #ffffff);
}

.header-unread-pill {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.18), rgba(220, 38, 38, 0.28));
    color: #f87171;
    border: 1px solid rgba(239, 68, 68, 0.3);
    font-size: 0.72rem;
    font-weight: 800;
    padding: 0.18rem 0.6rem;
    border-radius: 9999px;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.header-action-btn {
    background: transparent;
    border: 1px solid var(--border-color, rgba(255, 255, 255, 0.15));
    color: var(--brand-primary, #0d9488);
    font-size: 0.76rem;
    font-weight: 700;
    padding: 0.3rem 0.65rem;
    border-radius: var(--radius-sm, 8px);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    transition: all 0.15s ease;
}

.header-action-btn:hover {
    background: var(--brand-primary-light, rgba(13, 148, 136, 0.15));
    border-color: var(--brand-primary, #0d9488);
}

.header-icon-action-btn {
    width: 28px;
    height: 28px;
    border-radius: var(--radius-sm, 8px);
    background: transparent;
    border: 1px solid var(--border-color, rgba(255, 255, 255, 0.12));
    color: var(--text-secondary, #94a3b8);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s ease;
}

.header-icon-action-btn:hover {
    color: var(--text-primary, #ffffff);
    background: var(--bg-sidebar-hover, rgba(255, 255, 255, 0.08));
}

.header-icon-action-btn.spinning svg {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Tabs */
.notification-filter-tabs {
    display: flex;
    align-items: center;
    padding: 0.5rem 1.25rem 0.35rem;
    gap: 0.5rem;
    background: var(--bg-surface-elevated, #1f2937);
    border-bottom: 1px solid var(--border-subtle, rgba(255, 255, 255, 0.05));
}

.filter-tab-btn {
    background: transparent;
    border: none;
    color: var(--text-secondary, #94a3b8);
    font-size: 0.82rem;
    font-weight: 700;
    padding: 0.35rem 0.75rem;
    border-radius: 9999px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    transition: all 0.15s ease;
}

.filter-tab-btn:hover {
    color: var(--text-primary, #ffffff);
    background: rgba(255, 255, 255, 0.05);
}

.filter-tab-btn.active {
    background: var(--brand-primary, #0d9488);
    color: #ffffff;
}

.tab-count {
    background: rgba(255, 255, 255, 0.15);
    padding: 0.08rem 0.4rem;
    border-radius: 9999px;
    font-size: 0.68rem;
    font-weight: 800;
}

.tab-count-unread {
    background: #ef4444;
    color: #ffffff;
}

/* Dropdown Body */
.notification-dropdown-body {
    max-height: 380px;
    overflow-y: auto;
    overscroll-behavior: contain;
}

/* Custom Scrollbar */
.notification-dropdown-body::-webkit-scrollbar {
    width: 6px;
}
.notification-dropdown-body::-webkit-scrollbar-track {
    background: transparent;
}
.notification-dropdown-body::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.15);
    border-radius: 9999px;
}
.notification-dropdown-body::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.25);
}

/* States */
.notification-state-box,
.notification-empty-state {
    padding: 2.5rem 1.5rem;
    text-align: center;
    color: var(--text-muted, #94a3b8);
}

.notif-spinner {
    width: 28px;
    height: 28px;
    border: 3px solid rgba(13, 148, 136, 0.2);
    border-top-color: var(--brand-primary, #0d9488);
    border-radius: 50%;
    margin: 0 auto 0.75rem;
    animation: spin 0.8s linear infinite;
}

.empty-icon-wrap {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: var(--bg-surface-elevated, rgba(255, 255, 255, 0.05));
    border: 1px dashed var(--border-color, rgba(255, 255, 255, 0.15));
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    color: var(--text-muted, #94a3b8);
}

.empty-title {
    font-size: 1rem;
    font-weight: 800;
    color: var(--text-primary, #ffffff);
    margin: 0 0 0.35rem;
}

.empty-desc {
    font-size: 0.82rem;
    line-height: 1.5;
    margin: 0;
    max-width: 280px;
    margin: 0 auto;
}

/* Notification Items List */
.notification-items-list {
    display: flex;
    flex-direction: column;
}

.notification-item {
    display: flex;
    align-items: flex-start;
    gap: 0.85rem;
    padding: 0.95rem 1.25rem;
    border-bottom: 1px solid var(--border-subtle, rgba(255, 255, 255, 0.05));
    cursor: pointer;
    transition: background 0.15s ease, transform 0.15s ease;
    position: relative;
}

.notification-item:hover {
    background: var(--bg-sidebar-hover, rgba(255, 255, 255, 0.04));
}

/* Unread item highlight styling */
.notification-item.unread {
    background: rgba(13, 148, 136, 0.08);
}

.notification-item.unread:hover {
    background: rgba(13, 148, 136, 0.12);
}

/* Avatar by type */
.notif-avatar {
    width: 40px;
    height: 40px;
    min-width: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
    border: 1px solid transparent;
}

.notif-avatar.type-info {
    background: rgba(2, 132, 199, 0.18);
    border-color: rgba(2, 132, 199, 0.35);
}

.notif-avatar.type-warning {
    background: rgba(245, 158, 11, 0.18);
    border-color: rgba(245, 158, 11, 0.35);
}

.notif-avatar.type-danger {
    background: rgba(244, 63, 94, 0.18);
    border-color: rgba(244, 63, 94, 0.35);
}

.notif-avatar.type-success {
    background: rgba(16, 185, 129, 0.18);
    border-color: rgba(16, 185, 129, 0.35);
}

/* Content */
.notif-content {
    flex: 1;
    min-width: 0;
}

.notif-head {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 0.5rem;
    margin-bottom: 0.25rem;
}

.notif-title {
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--text-primary, #ffffff);
    margin: 0;
    line-height: 1.3;
}

.notification-item.unread .notif-title {
    font-weight: 800;
    color: #ffffff;
}

.notif-time {
    font-size: 0.73rem;
    color: var(--text-muted, #94a3b8);
    white-space: nowrap;
}

.notif-message {
    font-size: 0.81rem;
    color: var(--text-secondary, #cbd5e1);
    margin: 0;
    line-height: 1.45;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    word-break: break-word;
}

/* Right Status / Actions Column */
.notif-status-col {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: space-between;
    min-width: 22px;
    padding-top: 0.2rem;
    gap: 0.5rem;
}

.unread-glow-dot {
    width: 9px;
    height: 9px;
    border-radius: 50%;
    background: var(--brand-primary, #0d9488);
    box-shadow: 0 0 10px var(--brand-primary, #0d9488);
}

.notif-delete-btn {
    opacity: 0;
    background: transparent;
    border: none;
    color: var(--text-muted, #94a3b8);
    cursor: pointer;
    padding: 3px;
    border-radius: 4px;
    transition: all 0.15s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.notification-item:hover .notif-delete-btn {
    opacity: 1;
}

.notif-delete-btn:hover {
    color: #f43f5e;
    background: rgba(244, 63, 94, 0.15);
}

/* Footer */
.notification-dropdown-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1.25rem;
    background: var(--bg-surface-elevated, #1f2937);
    border-top: 1px solid var(--border-subtle, rgba(255, 255, 255, 0.05));
    font-size: 0.78rem;
}

.footer-clear-btn {
    background: transparent;
    border: none;
    color: var(--text-muted, #94a3b8);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.76rem;
    font-weight: 600;
    padding: 0.25rem 0.5rem;
    border-radius: var(--radius-sm, 6px);
    transition: all 0.15s ease;
}

.footer-clear-btn:hover {
    color: #f43f5e;
    background: rgba(244, 63, 94, 0.12);
}

.footer-note {
    color: var(--text-muted, #94a3b8);
}

/* Responsive adjustment */
@media (max-width: 480px) {
    .notification-dropdown-menu {
        width: 320px;
        left: -50px;
    }
}
</style>

{{-- Alpine.js Component Script --}}
<script>
function notificationDropdown() {
    return {
        open: false,
        loading: false,
        unreadCount: 0,
        notifications: [],
        filter: 'all', // 'all' or 'unread'
        pollInterval: null,

        get filteredNotifications() {
            if (this.filter === 'unread') {
                return this.notifications.filter(n => !n.is_read);
            }
            return this.notifications;
        },

        init() {
            this.fetchNotifications(false);

            // Silent poll every 40 seconds to update unread badge
            this.pollInterval = setInterval(() => {
                if (!this.open) {
                    this.fetchNotifications(false);
                }
            }, 40000);
        },

        toggleDropdown() {
            this.open = !this.open;
            if (this.open) {
                this.fetchNotifications(true);
            }
        },

        async fetchNotifications(showSpinner = false) {
            if (showSpinner) {
                this.loading = true;
            }

            try {
                const response = await fetch('/portal/notifications', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    this.unreadCount = data.unread_count ?? 0;
                    this.notifications = data.notifications ?? [];
                }
            } catch (err) {
                console.error('Failed to fetch notifications:', err);
            } finally {
                this.loading = false;
            }
        },

        async markAsRead(notificationId) {
            const item = this.notifications.find(n => n.id === notificationId);
            if (item && !item.is_read) {
                item.is_read = true;
                if (this.unreadCount > 0) {
                    this.unreadCount--;
                }
            }

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const response = await fetch(`/portal/notifications/${notificationId}/read`, {
                    method: 'POST',
                    keepalive: true,
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || '',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    if (typeof data.unread_count === 'number') {
                        this.unreadCount = data.unread_count;
                    }
                }
            } catch (err) {
                console.error('Failed to mark notification as read:', err);
            }
        },

        async markAllAsRead() {
            // Optimistic UI update
            this.unreadCount = 0;
            this.notifications.forEach(n => n.is_read = true);

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const response = await fetch('/portal/notifications/mark-all-read', {
                    method: 'POST',
                    keepalive: true,
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || '',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    this.unreadCount = data.unread_count ?? 0;
                }
            } catch (err) {
                console.error('Failed to mark all as read:', err);
            }
        },

        async handleItemClick(item) {
            if (!item.is_read) {
                this.markAsRead(item.id);
            }

            if (item.url && item.url !== '#' && item.url.trim() !== '') {
                window.location.href = item.url;
            }
        },

        async deleteNotification(id) {
            const index = this.notifications.findIndex(n => n.id === id);
            if (index !== -1) {
                const wasUnread = !this.notifications[index].is_read;
                this.notifications.splice(index, 1);
                if (wasUnread && this.unreadCount > 0) {
                    this.unreadCount--;
                }
            }

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const response = await fetch(`/portal/notifications/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || '',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    if (typeof data.unread_count === 'number') {
                        this.unreadCount = data.unread_count;
                    }
                }
            } catch (err) {
                console.error('Failed to delete notification:', err);
            }
        },

        async clearAllNotifications() {
            if (!confirm('هل أنت متأكد من رغبتك في مسح كافة الإشعارات من السجل؟')) {
                return;
            }

            this.notifications = [];
            this.unreadCount = 0;

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                await fetch('/portal/notifications/clear-all', {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || '',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
            } catch (err) {
                console.error('Failed to clear notifications:', err);
            }
        }
    };
}
</script>
