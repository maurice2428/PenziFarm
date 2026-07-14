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

                                /*
                                 * Mobile topbar layout:
                                 *
                                 * Row 1: sidebar/navigation control on the far
                                 * left and notification/profile controls on
                                 * the far right.
                                 *
                                 * Row 2: Universal Search across the complete
                                 * available topbar width.
                                 *
                                 * Filament renders TOPBAR_START directly
                                 * inside the <nav>; there is no
                                 * .fi-topbar-start wrapper to reposition.
                                 */
                                @media (max-width: 768px) {
                                    .fi-topbar {
                                        height: auto !important;
                                    }

                                    .fi-topbar nav {
                                        display: grid !important;
                                        grid-template-columns:
                                            auto minmax(0, 1fr) auto auto !important;
                                        grid-template-rows:
                                            auto auto !important;
                                        align-items: center !important;
                                        column-gap: 0.5rem !important;
                                        row-gap: 0.625rem !important;
                                        height: auto !important;
                                        min-height: 0 !important;
                                        padding-block: 0.625rem !important;
                                    }

                                    /*
                                     * First row, far left:
                                     * Filament mobile sidebar trigger.
                                     */
                                    .fi-topbar nav >
                                    .fi-topbar-open-sidebar-btn,
                                    .fi-topbar nav >
                                    .fi-topbar-close-sidebar-btn {
                                        grid-column: 1 !important;
                                        grid-row: 1 !important;
                                        justify-self: start !important;
                                        margin: 0 !important;
                                    }

                                    /*
                                     * First row, far right:
                                     * Filament action container containing
                                     * notifications, greeting and profile.
                                     */
                                    .fi-topbar nav > div.ms-auto {
                                        grid-column: 3 !important;
                                        grid-row: 1 !important;
                                        justify-self: end !important;
                                        align-self: center !important;
                                        min-width: 0 !important;
                                        max-width: 100% !important;
                                        margin-inline-start: 0 !important;
                                        flex-wrap: nowrap !important;
                                    }

                                    /*
                                     * TOPBAR_END is rendered immediately after
                                     * the notification/profile container. Keep
                                     * it in the same row, after the avatar.
                                     */
                                    .fi-topbar nav > div.ms-auto + * {
                                        grid-column: 4 !important;
                                        grid-row: 1 !important;
                                        justify-self: end !important;
                                        align-self: center !important;
                                        margin: 0 !important;
                                    }

                                    /*
                                     * Second row:
                                     * Search occupies every grid column.
                                     */
                                    .fi-topbar nav >
                                    .penzi-global-search-hook {
                                        grid-column: 1 / -1 !important;
                                        grid-row: 2 !important;
                                        display: block !important;
                                        width: 100% !important;
                                        max-width: none !important;
                                        min-width: 0 !important;
                                        margin: 0 !important;
                                    }

                                    .penzi-global-search-hook,
                                    .penzi-global-search-hook > *,
                                    .penzi-global-search-hook form,
                                    .penzi-global-search-hook .fi-input-wrp,
                                    .penzi-global-search-hook input {
                                        width: 100% !important;
                                        max-width: none !important;
                                        min-width: 0 !important;
                                    }
                                }

                                @media (max-width: 520px) {
                                    .fi-topbar nav {
                                        padding-inline: 0.75rem !important;
                                    }

                                    .fi-topbar nav > div.ms-auto {
                                        max-width:
                                            calc(100vw - 4.5rem) !important;
                                    }
                                }

                                /*
                                 * Network status detector.
                                 * Offline and slow states remain visible.
                                 * The restored state disappears automatically.
                                 */
                                .penzi-network-status {
                                    position: fixed;
                                    left: 50%;
                                    bottom: max(
                                        1rem,
                                        env(safe-area-inset-bottom)
                                    );
                                    z-index: 99999;
                                    display: inline-flex;
                                    align-items: center;
                                    justify-content: center;
                                    gap: 0.625rem;
                                    width: max-content;
                                    max-width: calc(100vw - 2rem);
                                    min-height: 2.75rem;
                                    padding: 0.625rem 1rem;
                                    border: 1px solid transparent;
                                    border-radius: 9999px;
                                    color: #ffffff;
                                    font-size: 0.8125rem;
                                    font-weight: 700;
                                    line-height: 1.25rem;
                                    letter-spacing: 0.01em;
                                    box-shadow:
                                        0 14px 30px rgba(15, 23, 42, 0.22),
                                        0 4px 10px rgba(15, 23, 42, 0.14);
                                    opacity: 0;
                                    visibility: hidden;
                                    pointer-events: none;
                                    transform:
                                        translate(-50%, calc(100% + 1.5rem));
                                    transition:
                                        opacity 180ms ease,
                                        visibility 180ms ease,
                                        transform 220ms ease,
                                        background-color 180ms ease,
                                        border-color 180ms ease;
                                }

                                .penzi-network-status.is-visible {
                                    opacity: 1;
                                    visibility: visible;
                                    transform: translate(-50%, 0);
                                }

                                .penzi-network-status[data-state="offline"] {
                                    background: #b91c1c;
                                    border-color: #ef4444;
                                }

                                .penzi-network-status[data-state="slow"] {
                                    background: #b45309;
                                    border-color: #f59e0b;
                                }

                                .penzi-network-status[data-state="online"] {
                                    background: #15803d;
                                    border-color: #22c55e;
                                }

                                .penzi-network-status__icon {
                                    display: inline-flex;
                                    width: 1.25rem;
                                    height: 1.25rem;
                                    flex: 0 0 auto;
                                }

                                .penzi-network-status__icon svg {
                                    display: block;
                                    width: 100%;
                                    height: 100%;
                                }

                                .penzi-network-status__message {
                                    overflow: hidden;
                                    text-overflow: ellipsis;
                                    white-space: nowrap;
                                }

                                @media (max-width: 520px) {
                                    .penzi-network-status {
                                        bottom: max(
                                            0.75rem,
                                            env(safe-area-inset-bottom)
                                        );
                                        max-width: calc(100vw - 1.5rem);
                                        padding: 0.5625rem 0.875rem;
                                        font-size: 0.75rem;
                                    }
                                }

                                @media (prefers-reduced-motion: reduce) {
                                    .penzi-network-status {
                                        transition: none;
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

                        <div
                            id="penzi-network-status"
                            class="penzi-network-status"
                            data-state="online"
                            role="status"
                            aria-live="polite"
                            aria-atomic="true"
                            hidden
                        >
                            <span
                                class="penzi-network-status__icon"
                                aria-hidden="true"
                            ></span>

                            <span
                                class="penzi-network-status__message"
                            ></span>
                        </div>

                        <script>
                            (() => {
                                if (window.__penziNetworkDetectorLoaded) {
                                    return;
                                }

                                window.__penziNetworkDetectorLoaded = true;

                                const detector = document.getElementById(
                                    'penzi-network-status'
                                );

                                if (! detector) {
                                    return;
                                }

                                const icon = detector.querySelector(
                                    '.penzi-network-status__icon'
                                );
                                const message = detector.querySelector(
                                    '.penzi-network-status__message'
                                );

                                const connection =
                                    navigator.connection
                                    || navigator.mozConnection
                                    || navigator.webkitConnection
                                    || null;

                                const checkEveryMs = 12000;
                                const probeTimeoutMs = 5000;
                                const slowLatencyMs = 2500;
                                const restoredDisplayMs = 4500;

                                let currentState = 'unknown';
                                let checkTimer = null;
                                let hideTimer = null;
                                let checking = false;
                                let checkVersion = 0;
                                const activeProbeControllers = new Set();

                                const cancelActiveProbes = () => {
                                    activeProbeControllers.forEach(
                                        (controller) => controller.abort()
                                    );

                                    activeProbeControllers.clear();
                                };

                                const invalidateCurrentCheck = () => {
                                    checkVersion += 1;
                                    checking = false;
                                    cancelActiveProbes();
                                };

                                const icons = {
                                    offline: `
                                        <svg viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <path d="M2 8.82a15 15 0 0 1 3.17-1.91" />
                                            <path d="M10.66 5.13A15.1 15.1 0 0 1 22 8.82" />
                                            <path d="M5 12.86a10 10 0 0 1 2.55-1.58" />
                                            <path d="M14.24 10.59A10 10 0 0 1 19 12.86" />
                                            <path d="M8.5 16.43a5 5 0 0 1 7 0" />
                                            <path d="M12 20h.01" />
                                            <path d="m3 3 18 18" />
                                        </svg>
                                    `,
                                    slow: `
                                        <svg viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <path d="M5 12.86a10 10 0 0 1 14 0" />
                                            <path d="M8.5 16.43a5 5 0 0 1 7 0" />
                                            <path d="M12 20h.01" />
                                            <path d="M12 3v4" />
                                            <path d="M12 11h.01" />
                                        </svg>
                                    `,
                                    online: `
                                        <svg viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <path d="M5 12.86a10 10 0 0 1 14 0" />
                                            <path d="M8.5 16.43a5 5 0 0 1 7 0" />
                                            <path d="M12 20h.01" />
                                            <path d="m16 5 2 2 4-4" />
                                        </svg>
                                    `,
                                };

                                const getNetworkLabel = () => {
                                    if (! connection) {
                                        return null;
                                    }

                                    const type = String(
                                        connection.type || ''
                                    ).toLowerCase();

                                    const labels = {
                                        wifi: 'Wi-Fi',
                                        ethernet: 'Ethernet cable',
                                        cellular: 'Mobile data',
                                        bluetooth: 'Bluetooth',
                                        wimax: 'WiMAX',
                                        mixed: 'Mixed connection',
                                        other: 'Other network',
                                    };

                                    return labels[type] || null;
                                };

                                const buildMessage = (state) => {
                                    const networkLabel = getNetworkLabel();
                                    const suffix = networkLabel
                                        ? ` • ${networkLabel}`
                                        : '';

                                    if (state === 'offline') {
                                        return `No internet connection${suffix}`;
                                    }

                                    if (state === 'slow') {
                                        return `Slow internet connection${suffix}`;
                                    }

                                    return `Internet connection restored${suffix}`;
                                };

                                const clearHideTimer = () => {
                                    if (hideTimer) {
                                        window.clearTimeout(hideTimer);
                                        hideTimer = null;
                                    }
                                };

                                const hideDetector = () => {
                                    detector.classList.remove('is-visible');

                                    window.setTimeout(() => {
                                        if (! detector.classList.contains(
                                            'is-visible'
                                        )) {
                                            detector.hidden = true;
                                        }
                                    }, 230);
                                };

                                const showState = (state) => {
                                    const previousState = currentState;

                                    /*
                                     * Do nothing when the detected state has
                                     * not changed. Periodic checks, focus
                                     * events and visibility changes must not
                                     * repeatedly reopen the same notification.
                                     */
                                    if (state === previousState) {
                                        return;
                                    }

                                    currentState = state;
                                    clearHideTimer();

                                    /*
                                     * A healthy connection on first page load
                                     * is silent. Green is shown only when the
                                     * app genuinely recovers from OFFLINE or
                                     * SLOW to ONLINE.
                                     */
                                    if (
                                        state === 'online'
                                        && previousState !== 'offline'
                                        && previousState !== 'slow'
                                    ) {
                                        hideDetector();
                                        return;
                                    }

                                    detector.dataset.state = state;
                                    icon.innerHTML = icons[state];
                                    message.textContent = buildMessage(state);
                                    detector.hidden = false;

                                    window.requestAnimationFrame(() => {
                                        detector.classList.add('is-visible');
                                    });

                                    if (state === 'online') {
                                        hideTimer = window.setTimeout(
                                            hideDetector,
                                            restoredDisplayMs
                                        );
                                    }
                                };

                                const browserReportsSlowConnection = () => {
                                    if (! connection) {
                                        return false;
                                    }

                                    const effectiveType = String(
                                        connection.effectiveType || ''
                                    ).toLowerCase();
                                    const downlink = Number(
                                        connection.downlink || 0
                                    );
                                    const rtt = Number(connection.rtt || 0);

                                    /*
                                     * These values are considered only after
                                     * a real internet probe succeeds. This
                                     * prevents stale browser estimates from
                                     * producing a false "slow" state while
                                     * the device is actually offline.
                                     */
                                    return Boolean(
                                        effectiveType === 'slow-2g'
                                        || effectiveType === '2g'
                                        || (downlink > 0 && downlink < 0.75)
                                        || rtt >= 1200
                                    );
                                };

                                const fetchProbe = async (probe) => {
                                    const controller = new AbortController();
                                    activeProbeControllers.add(controller);

                                    const timeout = window.setTimeout(
                                        () => controller.abort(),
                                        probeTimeoutMs
                                    );
                                    const startedAt = performance.now();

                                    try {
                                        const response = await fetch(
                                            probe.url,
                                            {
                                                cache: 'no-store',
                                                credentials:
                                                    probe.credentials
                                                    || 'omit',
                                                mode: probe.mode || 'no-cors',
                                                redirect: 'follow',
                                                signal: controller.signal,
                                            }
                                        );

                                        if (
                                            probe.requireOk
                                            && ! response.ok
                                        ) {
                                            throw new Error(
                                                `Probe failed: ${response.status}`
                                            );
                                        }

                                        return {
                                            latency:
                                                performance.now() - startedAt,
                                            source: probe.source,
                                        };
                                    } finally {
                                        window.clearTimeout(timeout);
                                        activeProbeControllers.delete(
                                            controller
                                        );
                                    }
                                };

                                const probeInternet = async () => {
                                    if (! navigator.onLine) {
                                        return {
                                            reachable: false,
                                            latency: null,
                                            source: null,
                                        };
                                    }

                                    const cacheBuster = Date.now();
                                    const hostname =
                                        window.location.hostname.toLowerCase();
                                    const isLocalDevelopment =
                                        hostname === 'localhost'
                                        || hostname === '127.0.0.1'
                                        || hostname === '::1';

                                    const probes = [
                                        {
                                            source: 'gstatic',
                                            url:
                                                'https://www.gstatic.com/generate_204'
                                                + '?penzi_network_check='
                                                + cacheBuster,
                                        },
                                        {
                                            source: 'google-connectivity',
                                            url:
                                                'https://connectivitycheck.gstatic.com/generate_204'
                                                + '?penzi_network_check='
                                                + cacheBuster,
                                        },
                                        {
                                            source: 'cloudflare',
                                            url:
                                                'https://www.cloudflare.com/cdn-cgi/trace'
                                                + '?penzi_network_check='
                                                + cacheBuster,
                                        },
                                    ];

                                    /*
                                     * On a public deployment, the application
                                     * origin is also a valid internet target.
                                     * Do not use it on localhost because a
                                     * local Laravel server remains reachable
                                     * even when the real internet is absent.
                                     */
                                    if (! isLocalDevelopment) {
                                        probes.push({
                                            source: 'application',
                                            url:
                                                window.location.origin
                                                + '/favicon.ico'
                                                + '?penzi_network_check='
                                                + cacheBuster,
                                            mode: 'same-origin',
                                            credentials: 'same-origin',
                                            requireOk: true,
                                        });
                                    }

                                    const results = await Promise.allSettled(
                                        probes.map(fetchProbe)
                                    );

                                    const successful = results
                                        .filter(
                                            (result) =>
                                                result.status === 'fulfilled'
                                        )
                                        .map((result) => result.value)
                                        .sort(
                                            (left, right) =>
                                                left.latency - right.latency
                                        );

                                    if (successful.length === 0) {
                                        return {
                                            reachable: false,
                                            latency: null,
                                            source: null,
                                        };
                                    }

                                    /*
                                     * Use the fastest successful endpoint so
                                     * one slow or temporarily blocked service
                                     * does not falsely classify the user's
                                     * whole connection as slow.
                                     */
                                    return {
                                        reachable: true,
                                        latency: successful[0].latency,
                                        source: successful[0].source,
                                    };
                                };

                                const runConnectionCheck = async () => {
                                    if (checking) {
                                        return;
                                    }

                                    const thisCheckVersion = ++checkVersion;
                                    checking = true;

                                    try {
                                        /*
                                         * Stage 1: establish whether internet
                                         * access exists. A failed reachability
                                         * check is always OFFLINE, never SLOW.
                                         */
                                        const result = await probeInternet();

                                        /*
                                         * Ignore a result from a check that was
                                         * invalidated by an offline/online or
                                         * network-change event. Without this,
                                         * an old successful request can finish
                                         * after Wi-Fi is switched off and
                                         * incorrectly show "restored".
                                         */
                                        if (
                                            thisCheckVersion !== checkVersion
                                        ) {
                                            return;
                                        }

                                        if (! result.reachable) {
                                            showState('offline');
                                            return;
                                        }

                                        /*
                                         * Stage 2: internet is proven to exist;
                                         * only now evaluate whether it is slow.
                                         */
                                        const isSlow =
                                            result.latency >= slowLatencyMs
                                            || browserReportsSlowConnection();

                                        if (isSlow) {
                                            showState('slow');
                                            return;
                                        }

                                        showState('online');
                                    } catch (error) {
                                        if (
                                            thisCheckVersion === checkVersion
                                        ) {
                                            showState('offline');
                                        }
                                    } finally {
                                        if (
                                            thisCheckVersion === checkVersion
                                        ) {
                                            checking = false;
                                        }
                                    }
                                };

                                const scheduleChecks = () => {
                                    if (checkTimer) {
                                        window.clearInterval(checkTimer);
                                    }

                                    checkTimer = window.setInterval(
                                        runConnectionCheck,
                                        checkEveryMs
                                    );
                                };

                                const restartConnectionCheck = (
                                    delay = 0
                                ) => {
                                    invalidateCurrentCheck();

                                    window.setTimeout(
                                        runConnectionCheck,
                                        delay
                                    );
                                };

                                window.addEventListener('offline', () => {
                                    /*
                                     * Immediately cancel every in-flight
                                     * external probe. This prevents an older
                                     * successful request from overwriting the
                                     * offline state with a false restoration.
                                     */
                                    invalidateCurrentCheck();
                                    showState('offline');
                                });

                                window.addEventListener('online', () => {
                                    /*
                                     * navigator.onLine only means a network
                                     * interface may be available. The external
                                     * probes must still succeed before the
                                     * green restored notice is shown.
                                     */
                                    restartConnectionCheck(500);
                                });

                                window.addEventListener('focus', () => {
                                    restartConnectionCheck();
                                });

                                document.addEventListener(
                                    'visibilitychange',
                                    () => {
                                        if (! document.hidden) {
                                            restartConnectionCheck();
                                        }
                                    }
                                );

                                if (connection?.addEventListener) {
                                    connection.addEventListener(
                                        'change',
                                        () => restartConnectionCheck(250)
                                    );
                                }

                                if (! navigator.onLine) {
                                    showState('offline');
                                }

                                runConnectionCheck();
                                scheduleChecks();
                            })();
                        </script>
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
