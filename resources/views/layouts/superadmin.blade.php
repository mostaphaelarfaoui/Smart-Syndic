<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'إدارة المنصة') — Smart Syndic Platform</title>

    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v={{ file_exists(public_path('css/dashboard.css')) ? filemtime(public_path('css/dashboard.css')) : time() }}">
    <script src="{{ asset('js/alpine.min.js') }}" defer></script>
    <script src="{{ asset('js/chart.min.js') }}"></script>

    <script>
        const savedTheme = localStorage.getItem('theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
</head>
<body x-data="{
    sidebarOpen: false,
    theme: localStorage.getItem('theme') || 'dark',
    toggleTheme() {
        this.theme = this.theme === 'dark' ? 'light' : 'dark';
        localStorage.setItem('theme', this.theme);
        document.documentElement.setAttribute('data-theme', this.theme);
    }
}">

<div class="app-wrapper">
    {{-- Sidebar Navigation --}}
    <aside class="app-sidebar" :class="{ 'open': sidebarOpen }">
        <div class="sidebar-brand">
            <div class="brand-emblem">
                <div class="brand-logo-icon" style="background:linear-gradient(135deg, #6366f1 0%, #a855f7 100%);">
                    <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <span>إدارة المنصة</span>
            </div>
            <span class="badge badge-info" style="font-size:0.65rem;">SUPER</span>
        </div>

        <nav class="sidebar-menu">
            <div class="sidebar-group-label">التحكم المركزي</div>
            <a href="{{ route('superadmin.dashboard') }}" class="nav-link {{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
                <span>لوحة القيادة المركزية</span>
            </a>

            <div class="sidebar-group-label">المنظمات والاشتراكات</div>
            <a href="{{ route('superadmin.tenants.index') }}" class="nav-link {{ request()->routeIs('superadmin.tenants.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <span>🏢 الإقامات والمنظمات</span>
            </a>

            <a href="{{ route('superadmin.users.index') }}" class="nav-link {{ request()->routeIs('superadmin.users.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span>👥 دليل المستخدمين الشامل</span>
            </a>

            <a href="{{ route('superadmin.subscriptions.index') }}" class="nav-link {{ request()->routeIs('superadmin.subscriptions.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
                <span>💳 باقات واشتراكات الـ SaaS</span>
            </a>

            <a href="{{ route('superadmin.announcements.index') }}" class="nav-link {{ request()->routeIs('superadmin.announcements.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                </svg>
                <span>📢 الإعلانات والإشعارات الجماعية</span>
            </a>

            <a href="{{ route('superadmin.reports.index') }}" class="nav-link {{ request()->routeIs('superadmin.reports.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <span>📑 تقارير المنصة والنمو</span>
            </a>

            <div class="sidebar-group-label">الأمان والتحكم</div>
            <a href="{{ route('superadmin.security.index') }}" class="nav-link {{ request()->routeIs('superadmin.security.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <span>🛡️ الأمان ورصد النصب</span>
            </a>

            <a href="{{ route('superadmin.audit.index') }}" class="nav-link {{ request()->routeIs('superadmin.audit.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                <span>🔍 سجل التدقيق العام</span>
            </a>

            <a href="{{ route('superadmin.settings.index') }}" class="nav-link {{ request()->routeIs('superadmin.settings.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>⚙️ إعدادات المنصة والميزات</span>
            </a>

            <div class="sidebar-group-label">السيرفر والبنية التحتية</div>
            <a href="{{ route('superadmin.server.index') }}" class="nav-link {{ request()->routeIs('superadmin.server.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                </svg>
                <span>🖥️ صحة السيرفر وقاعدة البيانات</span>
            </a>

            <a href="{{ route('superadmin.logs.index') }}" class="nav-link {{ request()->routeIs('superadmin.logs.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                </svg>
                <span>📜 سجل الأخطاء الحي (Logs)</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="user-snippet">
                <div class="avatar" style="background:linear-gradient(135deg, #6366f1, #a855f7);">
                    👑
                </div>
                <div class="user-info">
                    <div class="user-name">{{ auth()->user()?->full_name ?? 'المشرف العام' }}</div>
                    <div class="user-role">Super Admin</div>
                </div>
            </div>
            <form method="POST" action="{{ route('portal.logout') }}">
                @csrf
                <button type="submit" class="icon-btn" title="تسجيل الخروج" style="border:none; background:transparent; color:#ef4444;">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </button>
            </form>
        </div>
    </aside>

    <main class="app-main">
        <header class="app-topbar">
            <div class="topbar-right">
                <div class="residence-chip">
                    <span style="color:#6366f1;">⚡</span>
                    <span>Smart Syndic Platform</span>
                </div>
            </div>

            <div class="topbar-left" style="display:flex; align-items:center; gap:0.75rem;">
                <span class="badge badge-info" style="font-size:0.85rem; padding:0.4rem 0.9rem;">
                    🛡️ المشرف العام
                </span>

                {{-- In-App Notification Bell --}}
                <x-notification-bell />

                <button type="button" class="theme-toggle-btn" @click="toggleTheme()" :title="theme === 'dark' ? 'التحويل للوضع النهاري' : 'التحويل للوضع الليلي'">
                    <template x-if="theme === 'dark'">
                        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color:#f59e0b;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </template>
                    <template x-if="theme === 'light'">
                        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color:#6366f1;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </template>
                </button>
            </div>
        </header>

        <div class="app-content">
            @if(session('success'))
                <div style="background:var(--color-success-light); border:1px solid rgba(16,185,129,0.3); color:var(--color-success); padding:1rem 1.25rem; border-radius:var(--radius-md); font-weight:700; margin-bottom:1.5rem; display:flex; align-items:center; gap:0.5rem;">
                    <span>✓</span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div style="background:var(--color-danger-light, rgba(239,68,68,0.12)); border:1px solid rgba(239,68,68,0.3); color:var(--color-danger, #ef4444); padding:1rem 1.25rem; border-radius:var(--radius-md); font-weight:700; margin-bottom:1.5rem; display:flex; align-items:center; gap:0.5rem;">
                    <span>⚠</span>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div style="background:var(--color-danger-light, rgba(239,68,68,0.12)); border:1px solid rgba(239,68,68,0.3); color:var(--color-danger, #ef4444); padding:1rem 1.25rem; border-radius:var(--radius-md); font-weight:700; margin-bottom:1.5rem; display:flex; align-items:center; gap:0.5rem;">
                    <span>⚠</span>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            @yield('content')
        </div>
    </main>
</div>

@include('components.confirm-dialog')

</body>
</html>
