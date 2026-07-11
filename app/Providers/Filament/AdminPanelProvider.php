<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Dashboard;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Joaopaulolndev\FilamentEditProfile\Pages\EditProfilePage;
use Joaopaulolndev\FilamentEditProfile\FilamentEditProfilePlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $brandName = setting('farm.name', 'Lelekwe Farms');
        $logo = setting('branding.logo_light');
        $logoDark = setting('branding.logo_dark');
        $favicon = setting('branding.favicon');
        $primary = trim(setting('theme.primary', '#24db4b'));

        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
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
                    /*
                     * Applies to all pages below /admin/...
                     *
                     * Examples covered:
                     * /admin/accounting/accounting-fiscal-years
                     * /admin/accounting/accounting-fiscal-years/create
                     * /admin/accounting/accounting-journal-entries
                     * /admin/crop-farming/crop-catalog/create
                     * /admin/projects/works
                     * /admin/sales/customers
                     * /admin/human-resource/employees
                     *
                     * The main dashboard at /admin remains unchanged.
                     */
                    $hideResourcePageHeadings = request()->is('admin/*')
                        ? '
                <style>
                    /*
                     * Keep breadcrumbs, actions, forms, tables and page logic.
                     * Hide only the large duplicated page title.
                     */
                    .fi-main .fi-header .fi-header-heading {
                        display: none !important;
                    }

                    /*
                     * Reduce the space left after the heading is hidden.
                     */
                    .fi-main .fi-header {
                        gap: 0.25rem !important;
                    }
                </style>
            '
                        : '';

                    return new HtmlString('
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
            </style>

            ' . $hideResourcePageHeadings);
                }
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn(): HtmlString => new HtmlString('
                    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
                ')
            )
            ->brandName($brandName)
            ->brandLogo($logo ? asset('storage/' . $logo) : asset('images/logo.png'))
            ->darkModeBrandLogo(
                $logoDark
                    ? asset('storage/' . $logoDark)
                    : asset('images/logo-dark.png')
            )
            ->brandLogoHeight('60px')
            ->favicon($favicon ? asset('storage/' . $favicon) : asset('favicon.ico'))
            ->colors([
                'primary' => Color::hex($primary),
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
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
                    ->url(fn(): string => EditProfilePage::getUrl())
                    ->icon('heroicon-m-user-circle'),
            ])
            ->renderHook(
                PanelsRenderHook::TOPBAR_START,
                fn(): string => view('filament.admin.partials.topbar-search')->render()
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn(): string => view('filament.admin.partials.topbar-user-greeting')->render()
            )
            ->discoverResources(
                in: app_path('Filament/Resources'),
                for: 'App\Filament\Resources'
            )
            ->discoverPages(
                in: app_path('Filament/Pages'),
                for: 'App\Filament\Pages'
            )
            ->discoverClusters(
                in: app_path('Filament/Clusters'),
                for: 'App\Filament\Clusters'
            )
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(
                in: app_path('Filament/Widgets'),
                for: 'App\Filament\Widgets'
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
