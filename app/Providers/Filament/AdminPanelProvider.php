<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\CustomLogin;
use App\Http\Middleware\EnsurePasswordIsChanged;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Vite;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(CustomLogin::class)
            ->darkMode(false)
            ->brandName('HRIS PPNPN DJPb Lampung')
            ->brandLogo(asset('images/logo-djpb.png'))
            ->brandLogoHeight('2.25rem')
            ->favicon(asset('images/logo-djpb.png'))
            ->font('Plus Jakarta Sans')
            ->databaseNotifications()
            ->colors([
                'primary' => Color::hex('#0058A8'), // Biru Perisai Utama Kanwil DJPb
                'warning' => Color::hex('#F8B810'), // Emas Sayap & Padi
                'success' => Color::hex('#007A3D'), // Hijau Kapas
                'danger' => Color::hex('#C62828'),  // Merah Peringatan
                'gray' => Color::Slate,
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): HtmlString => new HtmlString(
                    app(Vite::class)(['resources/css/app.css'])->toHtml().'
                    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
                    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
                    <style>
                        :root {
                            --djpb-blue: #0058A8;
                            --djpb-navy: #0B2A4A;
                            --djpb-gold: #F8B810;
                            --djpb-green: #007A3D;
                            --djpb-bg: #F3F6FA;
                            --djpb-surface: #FFFFFF;
                        }
                        body.fi-body {
                            background-color: var(--djpb-bg) !important;
                            color: #111827 !important;
                        }
                        .fi-topbar {
                            background-color: #FFFFFF !important;
                            border-bottom: 2px solid #E5E7EB !important;
                        }
                        .fi-sidebar {
                            background-color: #FFFFFF !important;
                            border-right: 1px solid #E5E7EB !important;
                        }
                        .fi-sidebar-item-active .fi-sidebar-item-button {
                            background-color: #E6F0FA !important;
                            color: #0058A8 !important;
                            font-weight: 600 !important;
                            border-left: 3px solid #0058A8 !important;
                        }
                        .fi-section, .fi-ta-ctn, .fi-modal-window {
                            background-color: #FFFFFF !important;
                            border: 1px solid #E5E7EB !important;
                            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px -1px rgba(0, 0, 0, 0.05) !important;
                        }
                        /* Global SVG constraint to prevent unconstrained scaling */
                        .fi-page svg, .fi-modal svg, .fi-section svg {
                            max-width: 100%;
                            flex-shrink: 0;
                        }
                    </style>
                ')
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsurePasswordIsChanged::class,
            ]);
    }
}
