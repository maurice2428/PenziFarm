<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Dashboard;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Joaopaulolndev\FilamentEditProfile\FilamentEditProfilePlugin;
use Joaopaulolndev\FilamentEditProfile\Pages\EditProfilePage;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $brandName = setting(
            'farm.name',
            setting('company.name', 'Penzi Farm')
        );

        $logo = setting('branding.logo_light');
        $logoDark = setting('branding.logo_dark');
        $favicon = setting('branding.favicon');
        $primary = trim(
            setting('theme.primary', '#24db4b')
        );

        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()

            /*
             * The custom Universal Search replaces Filament's native
             * Resource-only global search, preventing duplicate inputs.
             */
            ->globalSearch(false)

            ->navigationGroups([
                'Livestock',
                'Animal Health',
                'Breeding Management',
                'Breed(s)',
                'Procurement',
                'Inventory',
                'Sales',
                'Human Resource',
                'Asset Valuation',
                'Crop Farming',
                'Projects & Works',
                'Project Funds',
                'Accounting',
                'Accounting Reports',
                'Accounting Setup',
                'Accounting Controls',
                'Kenya Tax & Compliance',
                'Farm Intelligence',
                'Administration',
                'Reports',
                'Audit Logs',
                'Data Center',
                'System Settings',
                'Account',
            ])

            ->renderHook(
                PanelsRenderHook::HEAD_END,
                function (): HtmlString {
                    $hideResourcePageHeadings = request()->is('admin/*')
                        ? <<<'HTML'
                            <style>
                                /*
                                 * Keep breadcrumbs, actions, forms, tables and
                                 * page logic. Hide only the duplicated title.
                                 */
                                .fi-main .fi-header .fi-header-heading {
                                    display: none !important;
                                }

                                .fi-main .fi-header {
                                    gap: 0.25rem !important;
                                }
                            </style>
                        HTML
                        : '';

                    return new HtmlString(
                        <<<'HTML'
                            <link
                                rel="stylesheet"
                                href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
                            />

                            <style>
                                .leaflet-container {
                                    width: 100%;
                                    min-height: 300px;
                                    z-index: 1;
                                }

                                .leaflet-control-container {
                                    z-index: 10;
                                }

                                /*
                                 * Keep Universal Search compact so the
                                 * greeting, notifications and user menu remain
                                 * correctly aligned. The result panel itself
                                 * is fixed to the viewport, so it is never
                                 * clipped by the topbar.
                                 */
                                .fi-topbar,
                                .fi-topbar nav,
                                .fi-topbar-start,
                                .fi-topbar-end {
                                    overflow: visible !important;
                                }

                                .fi-topbar-start {
                                    min-width: 0 !important;
                                    flex: 0 1 auto !important;
                                }

                                .fi-topbar-end {
                                    flex: 0 0 auto !important;
                                    margin-inline-start: auto !important;
                                }

                                .penzi-global-search-hook {
                                    width: min(29rem, 38vw);
                                    min-width: 17rem;
                                    margin-inline: 0.5rem;
                                }

                                @media (max-width: 1280px) {
                                    .penzi-global-search-hook {
                                        width: min(24rem, 32vw);
                                        min-width: 15rem;
                                    }
                                }

                                @media (max-width: 1024px) {
                                    .penzi-global-search-hook {
                                        width: 18rem;
                                        min-width: 13rem;
                                        margin-inline: 0.25rem;
                                    }
                                }

                                @media (max-width: 768px) {
                                    .penzi-global-search-hook {
                                        width: 11rem;
                                        min-width: 9rem;
                                    }
                                }

                                @media (max-width: 520px) {
                                    .penzi-global-search-hook {
                                        width: 9.5rem;
                                        min-width: 8.5rem;
                                    }
                                }
                            </style>
                        HTML
                        . $hideResourcePageHeadings
                    );
                }
            )

            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): HtmlString => new HtmlString(
                    <<<'HTML'
                        <script
                            src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
                        ></script>
                    HTML
                )
            )

            ->brandName($brandName)
            ->brandLogo(
                $logo
                    ? asset('storage/' . $logo)
                    : asset('images/logo.png')
            )
            ->darkModeBrandLogo(
                $logoDark
                    ? asset('storage/' . $logoDark)
                    : asset('images/logo-dark.png')
            )
            ->brandLogoHeight('60px')
            ->favicon(
                $favicon
                    ? asset('storage/' . $favicon)
                    : asset('favicon.ico')
            )
            ->colors([
                'primary' => Color::hex($primary),
            ])
            ->viteTheme(
                'resources/css/filament/admin/theme.css'
            )

            ->plugins([
                FilamentEditProfilePlugin::make()
                    ->slug('my-profile')
                    ->setTitle('My Profile')
                    ->setNavigationLabel('My Profile')
                    ->setNavigationGroup('Account')
                    ->setIcon('heroicon-o-user-circle')
                    ->setSort(1)
                    ->shouldShowEmailForm()
                    ->shouldShowAvatarForm()
                    ->shouldShowBrowserSessionsForm()
                    ->shouldShowDeleteAccountForm(false),
            ])

            ->userMenuItems([
                'profile' => MenuItem::make()
                    ->label('My Profile')
                    ->url(
                        fn (): string =>
                            EditProfilePage::getUrl()
                    )
                    ->icon('heroicon-m-user-circle'),
            ])

            /*
             * Universal Search.
             */
            ->renderHook(
                PanelsRenderHook::TOPBAR_START,
                fn (): string => view(
                    'filament.admin.partials.topbar-search'
                )->render()
            )

            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn (): string => view(
                    'filament.admin.partials.topbar-user-greeting'
                )->render()
            )

            ->discoverResources(
                in: app_path('Filament/Resources'),
                for: 'App\\Filament\\Resources'
            )
            ->discoverPages(
                in: app_path('Filament/Pages'),
                for: 'App\\Filament\\Pages'
            )
            ->discoverClusters(
                in: app_path('Filament/Clusters'),
                for: 'App\\Filament\\Clusters'
            )
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(
                in: app_path('Filament/Widgets'),
                for: 'App\\Filament\\Widgets'
            )
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
            ])

            ->authMiddleware([
                Authenticate::class,
                \App\Http\Middleware\AuditRequestTracker::class,
            ]);
    }
}
