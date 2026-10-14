<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Smart Syndic') — لوحة التسيير</title>

    {{-- Design System CSS --}}
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v={{ file_exists(public_path('css/dashboard.css')) ? filemtime(public_path('css/dashboard.css')) : time() }}">

    {{-- Local Scripts --}}
    <script src="{{ asset('js/alpine.min.js') }}" defer></script>
    <script src="{{ asset('js/chart.min.js') }}"></script>

    {{-- Theme script to prevent flash --}}
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
                <div class="brand-logo-icon">
                    <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <span>Smart Syndic</span>
            </div>
            <span class="brand-badge">PRO</span>
        </div>

        <nav class="sidebar-menu">
            <div class="sidebar-group-label">الرئيسية والمتابعة</div>
            <a href="{{ route('portal.dashboard') }}" class="nav-link {{ request()->routeIs('portal.dashboard') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
                <span>لوحة القيادة</span>
            </a>

            <div class="sidebar-group-label">إدارة العقار والسكان</div>
            <a href="{{ route('portal.apartments.index') }}" class="nav-link {{ request()->routeIs('portal.apartments.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span>الشقق والوحدات</span>
            </a>

            <a href="{{ route('portal.residents.index') }}" class="nav-link {{ request()->routeIs('portal.residents.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span>السكان والمقيمين</span>
            </a>

            <div class="sidebar-group-label">المالية والحسابات</div>
            <a href="{{ route('portal.payments.index') }}" class="nav-link {{ request()->routeIs('portal.payments.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>الواجبات الشهرية والتحصيل</span>
            </a>

            <a href="{{ route('portal.expenses.index') }}" class="nav-link {{ request()->routeIs('portal.expenses.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                </svg>
                <span>المصاريف والخزينة</span>
            </a>

            <div class="sidebar-group-label">التقارير والأرشيف</div>
            <a href="{{ route('portal.analytics.index') }}" class="nav-link {{ request()->routeIs('portal.analytics.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <span>📊 التحليلات والإحصائيات</span>
            </a>

            <a href="{{ route('portal.archives.index') }}" class="nav-link {{ request()->routeIs('portal.archives.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                </svg>
                <span>🗄️ الأرشيف وسجل العمليات</span>
            </a>

            <div class="sidebar-group-label">المجتمع والإعدادات</div>
            <a href="{{ route('portal.community.index') }}" class="nav-link {{ request()->routeIs('portal.community.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <span>الشكاوى والتصويت</span>
            </a>

            <a href="{{ route('portal.settings.index') }}" class="nav-link {{ request()->routeIs('portal.settings.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>إعدادات الإقامة والخاتم</span>
            </a>
        </nav>

        {{-- User Snippet in Sidebar Footer --}}
        <div class="sidebar-footer">
            <div class="user-snippet">
                <div class="avatar">
                    {{ mb_substr(auth()->user()?->first_name ?? 'س', 0, 1) }}
                </div>
                <div class="user-info">
                    <div class="user-name">{{ auth()->user()?->full_name ?? 'مسير السانديك' }}</div>
                    <div class="user-role">{{ auth()->user()?->tenant?->name ?? 'الإقامة' }}</div>
                </div>
            </div>
            <form method="POST" action="{{ route('portal.logout') }}" style="display:inline;">
                @csrf
                <button type="submit" class="icon-btn" title="تسجيل الخروج" style="border:none; background:transparent; color:#ef4444;">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </button>
            </form>
        </div>
    </aside>

    {{-- Main Area --}}
    <main class="app-main">
        @if(session()->has('impersonator_id'))
            <div style="background: linear-gradient(90deg, #92400e, #b45309, #d97706); color:#ffffff; padding:0.65rem 1.5rem; display:flex; justify-content:space-between; align-items:center; font-size:0.875rem; font-weight:600; box-shadow:0 3px 12px rgba(0,0,0,0.25); position:sticky; top:0; z-index:9999;">
                <div style="display:flex; align-items:center; gap:0.6rem;">
                    <span style="font-size:1.2rem;">👤</span>
                    <span>وضع الدعم الفني: أنت تتصفح حالياً بصفتك السانديك <strong>{{ auth()->user()->full_name }}</strong> (إقامة: <strong>{{ auth()->user()->tenant?->name }}</strong>)</span>
                </div>
                <form method="POST" action="{{ route('impersonate.leave') }}" style="margin:0;">
                    @csrf
                    <button type="submit" style="background:#ffffff; color:#92400e; border:none; padding:0.35rem 0.9rem; border-radius:6px; font-weight:700; font-size:0.8rem; cursor:pointer; display:inline-flex; align-items:center; gap:0.4rem; box-shadow:0 2px 4px rgba(0,0,0,0.15); transition:transform 0.15s ease;">
                        <span>إنهاء التقمص والعودة للسوبر أدمن</span>
                        <span>&larr;</span>
                    </button>
                </form>
            </div>
        @endif

        {{-- Active Platform Broadcasts & Announcements --}}
        @php
            $activeAnnouncements = \App\Models\PlatformAnnouncement::currentlyActive(auth()->user()?->role, auth()->user()?->tenant_id)->get();
        @endphp
        @foreach($activeAnnouncements as $bc)
            <div x-data="{ dismissed: false }" x-show="!dismissed"
                 style="display:flex; align-items:center; justify-content:space-between; padding:0.75rem 1.5rem; font-size:0.875rem; font-weight:600; box-shadow:0 2px 8px rgba(0,0,0,0.12); z-index:9998;
                 @if($bc->type === 'DANGER') background: linear-gradient(90deg, #991b1b, #dc2626); color:#ffffff;
                 @elseif($bc->type === 'WARNING') background: linear-gradient(90deg, #b45309, #d97706); color:#ffffff;
                 @elseif($bc->type === 'SUCCESS') background: linear-gradient(90deg, #065f46, #059669); color:#ffffff;
                 @else background: linear-gradient(90deg, #1e40af, #3b82f6); color:#ffffff;
                 @endif">
                <div style="display:flex; align-items:center; gap:0.75rem; flex:1;">
                    <span style="font-size:1.25rem;">
                        @if($bc->type === 'DANGER') 🚨
                        @elseif($bc->type === 'WARNING') ⚠️
                        @elseif($bc->type === 'SUCCESS') ✨
                        @else 📢
                        @endif
                    </span>
                    <div>
                        <strong style="text-decoration:underline; margin-left:0.4rem;">{{ $bc->title }}:</strong>
                        <span>{{ $bc->message }}</span>
                    </div>
                </div>
                @if($bc->is_dismissible)
                    <button type="button" @click="dismissed = true" style="background:rgba(255,255,255,0.2); border:none; color:#ffffff; border-radius:50%; width:26px; height:26px; display:flex; align-items:center; justify-content:center; cursor:pointer; font-weight:bold;" title="إغلاق الإشعار">
                        &times;
                    </button>
                @endif
            </div>
        @endforeach

        {{-- Sticky Topbar --}}
        <header class="app-topbar">
            <div class="topbar-right">
                <button type="button" class="icon-btn" @click="sidebarOpen = !sidebarOpen" style="display:none;" class="mobile-toggle">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <div class="residence-chip">
                    <span style="color:var(--brand-primary); font-size:1.1rem;">🏢</span>
                    <span>{{ auth()->user()?->tenant?->name ?? 'Smart Syndic' }}</span>
                </div>
            </div>

            <div class="topbar-left" style="display:flex; align-items:center; gap:0.75rem;">
                <span class="badge badge-neutral" style="font-size:0.85rem; padding:0.4rem 0.9rem;">
                    🗓️ {{ now()->locale('ar')->translatedFormat('l، j F Y') }}
                </span>

                {{-- In-App Notification Bell --}}
                <x-notification-bell />

                {{-- Dark/Light toggle --}}
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

        {{-- Main Page Content --}}
        <div class="app-content">
            {{-- Flash Messages --}}
            @if(session('success'))
                <div style="background:var(--color-success-light); border:1px solid rgba(16,185,129,0.3); color:var(--color-success); padding:1rem 1.25rem; border-radius:var(--radius-md); font-weight:700; margin-bottom:1.5rem; display:flex; align-items:center; gap:0.5rem;">
                    <span>✓</span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div style="background:var(--color-danger-light); border:1px solid rgba(244,63,94,0.3); color:var(--color-danger); padding:1rem 1.25rem; border-radius:var(--radius-md); font-weight:700; margin-bottom:1.5rem; display:flex; align-items:center; gap:0.5rem;">
                    <span>⚠</span>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div style="background:var(--color-danger-light); border:1px solid rgba(244,63,94,0.3); color:var(--color-danger); padding:1rem 1.25rem; border-radius:var(--radius-md); font-weight:700; margin-bottom:1.5rem; display:flex; align-items:center; gap:0.5rem;">
                    <span>⚠</span>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            @yield('content')
        </div>
    </main>
</div>

@include('components.confirm-dialog')

@stack('scripts')
</body>
</html>
