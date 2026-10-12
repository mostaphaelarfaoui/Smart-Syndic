<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class SyndicPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('syndic')
            ->path('syndic')
            ->login()
            ->registration()
            ->brandName('Smart Syndic')
            ->colors([
                'primary'   => Color::Teal,
                'danger'    => Color::Rose,
                'gray'      => Color::Zinc,
                'info'      => Color::Cyan,
                'success'   => Color::Emerald,
                'warning'   => Color::Amber,
            ])
            ->darkMode(true)
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                'إدارة الإقامة',
                'المالية والحسابات',
                'التواصل والتصويت',
                'الإعدادات والرقابة',
            ])
            ->discoverResources(in: app_path('Filament/Syndic/Resources'), for: 'App\\Filament\\Syndic\\Resources')
            ->discoverPages(in: app_path('Filament/Syndic/Pages'), for: 'App\\Filament\\Syndic\\Pages')
            ->pages([
                \Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Syndic/Widgets'), for: 'App\\Filament\\Syndic\\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                \App\Http\Middleware\SetLocale::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->tenant(\App\Models\Tenant::class);
    }
}
