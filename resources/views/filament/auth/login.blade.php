<x-filament-panels::page.simple>
    @php
        $brandName = setting('farm.name', 'Lelekwe Farm');
        $logo = setting('branding.logo_light');
        $logoUrl = $logo ? asset('storage/' . $logo) : asset('images/logo.png');
        $loginBg = setting('branding.login_background');
        $bgUrl = $loginBg ? asset('storage/' . $loginBg) : asset('images/lelekwe-login-bg.jpg');
        $primaryColor = setting('theme.primary', '#1a5c38');
        //$secondaryColor = setting('theme.secondary', '#b8963e');
        $secondaryColor = '';
        $heroTitle = setting('auth.hero_title', 'Smart farm operations, in one place.');
        $heroText = setting(
            'auth.hero_text',
            'Manage records, reporting, workforce, approvals, and core ERP workflows through one secure platform.',
        );
    @endphp

    {{-- ─────────────────────────────────────────────────────────────
         RESET FILAMENT SIMPLE LAYOUT CHROME
    ───────────────────────────────────────────────────────────────── --}}
    <style>
        .fi-simple-header,
        .fi-simple-main>.fi-simple-main-ctn>.fi-simple-page>.fi-simple-header {
            display: none !important;
        }

        .fi-simple-main,
        .fi-simple-main-ctn,
        .fi-simple-page,
        .fi-simple-layout {
            width: 100% !important;
            max-width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
            background: transparent !important;
        }

        .fi-simple-layout {
            min-height: 100vh;
        }

        /* ── GLOBAL THEME COLOUR BRIDGE ─────────────────────────────── */
        .lf-root {
            /* Primary = branded backgrounds */
            --lf-green: var(--lf-primary);
            --lf-green-dark: color-mix(in srgb, var(--lf-primary) 72%, #000);
            --lf-green-mid: color-mix(in srgb, var(--lf-primary) 82%, #fff);
            --lf-green-light: color-mix(in srgb, var(--lf-primary) 9%, #fff);

            /* Secondary = borders, dividers and accent elements */
            --lf-gold: var(--lf-secondary);
            --lf-gold-pale: color-mix(in srgb, var(--lf-secondary) 12%, #fff);

            --lf-border: color-mix(in srgb, var(--lf-secondary) 24%, transparent);
            --lf-border-md: color-mix(in srgb, var(--lf-secondary) 45%, transparent);

            --lf-focus-ring: color-mix(in srgb, var(--lf-primary) 20%, transparent);
        }

        .lf-root[data-theme="dark"] {
            --lf-green-light: color-mix(in srgb, var(--lf-primary) 18%, transparent);
            --lf-border: color-mix(in srgb, var(--lf-secondary) 32%, transparent);
            --lf-border-md: color-mix(in srgb, var(--lf-secondary) 52%, transparent);
        }

        /* Main branded hero background uses the saved global primary colour. */
        .lf-hero {
            background-color: var(--lf-primary) !important;
        }

        .lf-hero-overlay {
            background: linear-gradient(160deg,
                    color-mix(in srgb, var(--lf-primary) 92%, #000) 0%,
                    color-mix(in srgb, var(--lf-primary) 78%, #000) 52%,
                    color-mix(in srgb, var(--lf-primary) 64%, #000) 100%) !important;
        }

        /* Secondary colour controls all decorative hero borders. */
        .lf-hero-logo-ring,
        .lf-hero-features {
            border-color: color-mix(in srgb, var(--lf-secondary) 60%, transparent) !important;
        }

        .lf-hero-feat {
            border-bottom-color: color-mix(in srgb, var(--lf-secondary) 38%, transparent) !important;
        }

        .lf-hero-feat-icon {
            border: 1px solid color-mix(in srgb, var(--lf-secondary) 55%, transparent);
            background: color-mix(in srgb, var(--lf-primary) 58%, transparent);
        }

        .lf-hero-rule-line {
            background: color-mix(in srgb, var(--lf-secondary) 72%, transparent) !important;
        }

        .lf-hero-rule-diamond {
            background: var(--lf-secondary) !important;
        }

        /* Primary button background, secondary button outline. */
        .lf-submit-wrap .fi-btn,
        .lf-submit-wrap button[type="submit"],
        .lf-submit-btn {
            background: var(--lf-primary) !important;
            border: 1px solid var(--lf-secondary) !important;
        }

        .lf-submit-wrap .fi-btn:hover,
        .lf-submit-wrap button[type="submit"]:hover {
            background: var(--lf-green-dark) !important;
            border-color: var(--lf-secondary) !important;
        }

        /* Input borders follow the global secondary colour. */
        .lf-form-fields .fi-input-wrp {
            border-color: var(--lf-border-md) !important;
        }

        .lf-form-fields .fi-input-wrp:focus-within {
            border-color: var(--lf-secondary) !important;
            box-shadow: 0 0 0 3px var(--lf-focus-ring) !important;
        }

        /* Small branded surfaces and accents. */
        .lf-header-logo-box {
            /*background: var(--lf-primary) !important;*/
            border: 1px solid var(--lf-secondary);
        }

        .lf-form-gold-bar,
        .lf-form-eyebrow {
            background-color: var(--lf-secondary) !important;
            color: var(--lf-secondary) !important;
        }

        .lf-theme-btn,
        .lf-theme-menu,
        .lf-form-side,
        .lf-form-footer {
            border-color: var(--lf-border) !important;
        }
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Comfortaa:wght@300..700&display=swap" rel="stylesheet">


    <div class="lf-root" id="lfRoot" data-theme="light"
        style="
        --lf-primary: {{ $primaryColor }};
        --lf-secondary: {{ $secondaryColor }};
    ">


        <header class="lf-header">
            <div class="lf-header-brand">
                <div class="lf-header-logo-box">
                    <img src="{{ $logoUrl }}" alt="{{ $brandName }}" class="lf-header-logo-img">
                </div>
                <div>
                    <div class="lf-header-name">{{ $brandName }}</div>
                    <div class="lf-header-sub">Farm ERP Platform</div>
                </div>
            </div>

            <div class="lf-theme-wrap" id="lfThemeWrap">
                <button type="button" class="lf-theme-btn" id="lfThemeBtn" aria-haspopup="true" aria-expanded="false">
                    <span id="lfThemeIcon" class="lf-theme-icon">
                        {{-- sun --}}
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="5" />
                            <path
                                d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" />
                        </svg>
                    </span>
                    <span id="lfThemeLabel">Light</span>
                    <svg class="lf-theme-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div class="lf-theme-menu" id="lfThemeMenu" role="menu">
                    <button type="button" class="lf-theme-opt" data-mode="light" role="menuitem">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="5" />
                            <path
                                d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" />
                        </svg>
                        Light
                    </button>
                    <button type="button" class="lf-theme-opt" data-mode="dark" role="menuitem">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
                        </svg>
                        Dark
                    </button>
                    <button type="button" class="lf-theme-opt" data-mode="system" role="menuitem">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="3" width="20" height="14" rx="2" />
                            <path d="M8 21h8M12 17v4" />
                        </svg>
                        System
                    </button>
                </div>
            </div>
        </header>

        {{-- ═══════════════════════════════════════
             BODY GRID
        ════════════════════════════════════════ --}}
        <div class="lf-body">

            {{-- ─────────────────
                 HERO (left)
            ───────────────────── --}}
            <section class="lf-hero" style="background-image: url('{{ $bgUrl }}');">
                <div class="lf-hero-overlay"></div>
                <div class="lf-hero-grid-bg"></div>

                <div class="lf-hero-inner">
                    {{-- Top: logo + text --}}
                    <div class="lf-hero-top">


                        <p class="lf-hero-eyebrow">Farm Management Software</p>

                        <h1 class="lf-hero-title">
                            {{ $heroTitle }}
                        </h1>

                        <div class="lf-hero-rule">
                            <span class="lf-hero-rule-line"></span>
                            <span class="lf-hero-rule-diamond"></span>
                            <span class="lf-hero-rule-line"></span>
                        </div>

                        <p class="lf-hero-text" id="lfTyping"></p>
                    </div>

                    {{-- Bottom: feature strips --}}
                    <div class="lf-hero-features">
                        <div class="lf-hero-feat">
                            <div class="lf-hero-feat-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                                    <polyline points="9 22 9 12 15 12 15 22" />
                                </svg>
                            </div>
                            <div class="lf-hero-feat-body">
                                <div class="lf-hero-feat-label">Operations</div>
                                <div class="lf-hero-feat-desc">Daily workflows, approvals &amp; status tracking</div>
                            </div>
                        </div>

                        <div class="lf-hero-feat">
                            <div class="lf-hero-feat-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 3v18h18" />
                                    <path d="M7 16l4-4 4 4 4-6" />
                                </svg>
                            </div>
                            <div class="lf-hero-feat-body">
                                <div class="lf-hero-feat-label">Reporting</div>
                                <div class="lf-hero-feat-desc">Structured records &amp; decision insights</div>
                            </div>
                        </div>

                        <div class="lf-hero-feat">
                            <div class="lf-hero-feat-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                    <circle cx="9" cy="7" r="4" />
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                </svg>
                            </div>
                            <div class="lf-hero-feat-body">
                                <div class="lf-hero-feat-label">Workforce</div>
                                <div class="lf-hero-feat-desc">Staff records, shifts &amp; payroll</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ─────────────────
                 FORM (right)
            ───────────────────── --}}
            <section class="lf-form-side">
                <div class="lf-form-card">

                    <div class="lf-form-gold-bar"></div>

                    <div class="lf-form-header">
                        <p class="lf-form-eyebrow">Secure Access</p>
                        <h2 class="lf-form-title">{{ $this->getHeading() }}</h2>
                        @if ($this->getSubheading())
                            <p class="lf-form-sub">{{ $this->getSubheading() }}</p>
                        @else
                            <p class="lf-form-sub">Sign in to continue to your dashboard.</p>
                        @endif
                    </div>

                    <x-filament-panels::form id="form" wire:submit="authenticate">

                        <div class="lf-form-fields" id="lfFormFields">
                            {{ $this->form }}
                        </div>

                        @if (filament()->hasPasswordReset())
                            <div class="lf-forgot-row">
                                <a href="{{ filament()->getRequestPasswordResetUrl() }}" class="lf-forgot-link">
                                    Forgot your password?
                                </a>
                            </div>
                        @endif

                        <div class="lf-submit-wrap">
                            <x-filament::button type="submit" size="lg" class="lf-submit-btn">
                                Sign in &rarr;
                            </x-filament::button>
                        </div>

                    </x-filament-panels::form>

                    <footer class="lf-form-footer">
                        <div class="lf-footer-badges">
                            <span class="lf-badge">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                                </svg>
                                Encrypted
                            </span>
                            <span class="lf-badge-sep"></span>
                            <span class="lf-badge">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="11" width="18" height="11" rx="2" />
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                </svg>
                                Authorized only
                            </span>
                            <span class="lf-badge-sep"></span>
                            <span class="lf-badge">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10" />
                                    <path d="M12 8v4l3 3" />
                                </svg>
                                24/7 uptime
                            </span>
                        </div>
                        <p class="lf-footer-copy">&copy; {{ date('Y') }} {{ $brandName }}. All rights
                            reserved.</p>
                    </footer>
                </div>
            </section>

        </div>{{-- /lf-body --}}
    </div>{{-- /lf-root --}}


    {{-- ═══════════════════════════════════════════════════════════════
         STYLES
    ════════════════════════════════════════════════════════════════ --}}
    <style>
        /* ── TOKENS ─────────────────────────────────────────────── */
        :root {
            --lf-green: #1a5c38;
            --lf-green-dark: #0f3d26;
            --lf-green-mid: #2d7a4f;
            --lf-green-light: #e8f3ed;
            --lf-gold: #b8963e;
            --lf-gold-pale: #f5edd6;
            --lf-text: #1a1a18;
            --lf-muted: #5a5a52;
            --lf-placeholder: rgba(90, 90, 82, 0.42);
            --lf-border: rgba(26, 26, 24, 0.10);
            --lf-border-md: rgba(26, 26, 24, 0.16);
            --lf-surface: #f7f7f4;
            --lf-card: #ffffff;
            --lf-shadow: 0 4px 32px rgba(26, 26, 24, 0.09), 0 1px 4px rgba(26, 26, 24, 0.05);
            --lf-radius: 10px;
            --lf-radius-lg: 18px;
            --lf-font-body: 'Comfortaa', sans-serif !important;
            --lf-font-display: 'ChopinScript', cursive;
        }

        /* DARK TOKEN OVERRIDES */
        .lf-root[data-theme="dark"] {
            --lf-text: #f0f0ec;
            --lf-muted: #a0a099;
            --lf-placeholder: rgba(160, 160, 153, 0.45);
            --lf-border: rgba(255, 255, 255, 0.09);
            --lf-border-md: rgba(255, 255, 255, 0.15);
            --lf-surface: #111210;
            --lf-card: #1c1e1a;
            --lf-shadow: 0 4px 32px rgba(0, 0, 0, 0.36), 0 1px 4px rgba(0, 0, 0, 0.20);
            --lf-green-light: rgba(26, 92, 56, 0.18);
        }

        /* ── FILAMENT OVERRIDES ─────────────────────────────────── */
        .fi-simple-header {
            display: none !important;
        }

        /* ── BASE ───────────────────────────────────────────────── */
        .lf-root *,
        .lf-root *::before,
        .lf-root *::after {
            box-sizing: border-box;
        }

        .lf-root {
            font-family: var(--lf-font-body);
            color: var(--lf-text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: var(--lf-surface);
            transition: background 0.2s, color 0.2s;
        }

        /* ── HEADER ─────────────────────────────────────────────── */
        .lf-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 64px;
            padding: 0 2rem;
            border-bottom: 1px solid var(--lf-border);
            background: var(--lf-card);
            position: sticky;
            top: 0;
            z-index: 40;
            transition: background 0.2s;
        }

        .lf-header-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .lf-header-logo-box {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            /*background: var(--lf-green);*/
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            overflow: hidden;
        }

        .lf-header-logo-img {
            width: 50px;
            height: 50px;
            object-fit: contain;
            /* filter: brightness(0);*/
        }

        .lf-header-name {
            font-family: var(--lf-font-display);
            font-size: 1.05rem;
            font-weight: 600;
            letter-spacing: -0.01em;
            line-height: 1.2;
            color: var(--lf-text);
        }

        .lf-header-sub {
            font-size: 0.68rem;
            color: var(--lf-muted);
            letter-spacing: 0.10em;
            text-transform: uppercase;
            margin-top: 1px;
        }

        /* ── THEME TOGGLE ───────────────────────────────────────── */
        .lf-theme-wrap {
            position: relative;
        }

        .lf-theme-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 7px 14px;
            border: 1px solid var(--lf-border-md);
            border-radius: var(--lf-radius);
            background: transparent;
            font-family: var(--lf-font-body);
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--lf-muted);
            cursor: pointer;
            transition: background 0.15s, color 0.15s;
        }

        .lf-theme-btn:hover {
            background: var(--lf-surface);
            color: var(--lf-text);
        }

        .lf-theme-icon svg {
            width: 15px;
            height: 15px;
            display: block;
        }

        .lf-theme-chevron {
            width: 13px;
            height: 13px;
            transition: transform 0.2s;
        }

        .lf-theme-wrap.open .lf-theme-chevron {
            transform: rotate(180deg);
        }

        .lf-theme-menu {
            display: none;
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            min-width: 170px;
            background: var(--lf-card);
            border: 1px solid var(--lf-border-md);
            border-radius: var(--lf-radius-lg);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.13);
            padding: 6px;
            z-index: 50;
        }

        .lf-theme-wrap.open .lf-theme-menu {
            display: block;
        }

        .lf-theme-opt {
            display: flex;
            align-items: center;
            gap: 9px;
            width: 100%;
            padding: 9px 12px;
            border: none;
            background: transparent;
            font-family: var(--lf-font-body);
            font-size: 0.86rem;
            font-weight: 500;
            color: var(--lf-text);
            border-radius: 10px;
            text-align: left;
            cursor: pointer;
            transition: background 0.14s;
        }

        .lf-theme-opt:hover {
            background: var(--lf-surface);
        }

        .lf-theme-opt svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }

        /* ── BODY GRID ──────────────────────────────────────────── */
        .lf-body {
            flex: 1;
            display: grid;
            grid-template-columns: 1fr 500px;
            min-height: calc(100vh - 64px);
        }

        /* ── HERO ───────────────────────────────────────────────── */
        .lf-hero {
            position: relative;
            overflow: hidden;
            background-color: var(--lf-green-dark);
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            display: flex;
            align-items: stretch;
        }

        .lf-hero-overlay {
            position: absolute;
            inset: 0;
            background:
                linear-gradient(160deg, rgba(9, 36, 21, 0.91) 0%, rgba(20, 72, 44, 0.80) 50%, rgba(30, 100, 60, 0.72) 100%);
            z-index: 1;
        }

        /* subtle grid pattern */
        .lf-hero-grid-bg {
            position: absolute;
            inset: 0;
            z-index: 2;
            opacity: 0.05;
            background-image:
                linear-gradient(rgba(255, 255, 255, 1) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 1) 1px, transparent 1px);
            background-size: 44px 44px;
        }

        .lf-hero-inner {
            position: relative;
            z-index: 3;
            width: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 3.5rem;
            gap: 3rem;
        }

        /* hero: logo ring */
        .lf-hero-logo-ring {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            background: rgba(255, 255, 255, 0.09);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            backdrop-filter: blur(4px);
        }

        .lf-hero-logo-img {
            width: 48px;
            height: 48px;
            object-fit: contain;
            filter: brightness(10);
        }

        .lf-hero-eyebrow {
            font-size: 0.68rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.48);
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .lf-hero-title {
            font-family: var(--lf-font-display);
            font-size: clamp(2rem, 3vw, 3.25rem);
            font-weight: 600;
            line-height: 1.12;
            letter-spacing: -0.02em;
            color: #ffffff;
            margin: 0 0 1.5rem;
            max-width: 560px;
        }

        /* decorative rule */
        .lf-hero-rule {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 1.5rem;
        }

        .lf-hero-rule-line {
            flex: 1;
            height: 1px;
            background: rgba(184, 150, 62, 0.45);
            max-width: 60px;
        }

        .lf-hero-rule-diamond {
            width: 7px;
            height: 7px;
            background: var(--lf-gold);
            transform: rotate(45deg);
            flex-shrink: 0;
        }

        /* typing text */
        .lf-hero-text {
            font-size: 1rem;
            line-height: 1.78;
            color: rgba(255, 255, 255, 0.68);
            max-width: 500px;
            min-height: 3.6em;
            font-weight: 400;
        }

        .lf-hero-text::after {
            content: '|';
            margin-left: 2px;
            color: rgba(255, 255, 255, 0.4);
            animation: lfBlink 1s step-end infinite;
        }

        @keyframes lfBlink {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0;
            }
        }

        /* feature strips */
        .lf-hero-features {
            border: 1px solid rgba(255, 255, 255, 0.11);
            border-radius: 16px;
            overflow: hidden;
            background: rgba(0, 0, 0, 0.18);
            backdrop-filter: blur(8px);
        }

        .lf-hero-feat {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.07);
            transition: background 0.15s;
        }

        .lf-hero-feat:last-child {
            border-bottom: none;
        }

        .lf-hero-feat:hover {
            background: rgba(255, 255, 255, 0.04);
        }

        .lf-hero-feat-icon {
            width: 36px;
            height: 36px;
            border-radius: 9px;
            background: rgba(255, 255, 255, 0.10);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .lf-hero-feat-icon svg {
            width: 17px;
            height: 17px;
            stroke: rgba(255, 255, 255, 0.82);
        }

        .lf-hero-feat-label {
            font-size: 0.86rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.92);
            margin-bottom: 2px;
        }

        .lf-hero-feat-desc {
            font-size: 0.77rem;
            color: rgba(255, 255, 255, 0.48);
            line-height: 1.4;
        }

        /* ── FORM SIDE ──────────────────────────────────────────── */
        .lf-form-side {
            background: var(--lf-card);
            border-left: 1px solid var(--lf-border);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem;
            transition: background 0.2s;
        }

        .lf-form-card {
            width: 100%;
            max-width: 400px;
        }

        .lf-form-gold-bar {
            height: 3px;
            width: 48px;
            background: var(--lf-gold);
            border-radius: 2px;
            margin-bottom: 2rem;
        }

        .lf-form-eyebrow {
            font-size: 0.68rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--lf-gold);
            font-weight: 600;
            margin-bottom: 0.6rem;
        }

        .lf-form-title {
            font-family: var(--lf-font-display);
            font-size: 2rem;
            font-weight: 600;
            letter-spacing: -0.025em;
            line-height: 1.15;
            color: var(--lf-text);
            margin: 0 0 0.5rem;
        }

        .lf-form-sub {
            font-size: 0.875rem;
            color: var(--lf-muted);
            line-height: 1.6;
            margin: 0 0 2rem;
        }

        .lf-form-header {
            margin-bottom: 0;
        }

        /* ── FILAMENT FIELD OVERRIDES ───────────────────────────── */
        .lf-form-fields {
            margin-bottom: 0.25rem;
        }

        /* label */
        .lf-form-fields .fi-fo-field-wrp-label,
        .lf-form-fields label {
            font-size: 0.72rem !important;
            font-weight: 600 !important;
            letter-spacing: 0.08em !important;
            text-transform: uppercase !important;
            color: var(--lf-text) !important;
            margin-bottom: 0.5rem !important;
        }

        /* input wrapper */
        .lf-form-fields .fi-input-wrp {
            border-radius: var(--lf-radius) !important;
            border: 1px solid var(--lf-border-md) !important;
            background: var(--lf-surface) !important;
            box-shadow: none !important;
            transition: border-color 0.18s, box-shadow 0.18s !important;
            min-height: 52px !important;
            overflow: hidden !important;
        }

        .lf-form-fields .fi-input-wrp:focus-within {
            border-color: var(--lf-green) !important;
            box-shadow: 0 0 0 3px rgba(26, 92, 56, 0.10) !important;
            background: var(--lf-card) !important;
        }

        /* input element */
        .lf-form-fields input[type="email"],
        .lf-form-fields input[type="text"],
        .lf-form-fields input[type="password"] {
            min-height: 52px !important;
            font-family: var(--lf-font-body) !important;
            font-size: 0.92rem !important;
            padding-left: 1rem !important;
            background: transparent !important;
            color: var(--lf-text) !important;
        }

        .lf-form-fields input::placeholder {
            color: var(--lf-placeholder) !important;
        }

        /* field spacing */
        .lf-form-fields .fi-fo-field-wrp {
            margin-bottom: 1.1rem !important;
        }

        /* checkbox */
        .lf-form-fields .fi-checkbox-input,
        .lf-form-fields input[type="checkbox"] {
            accent-color: var(--lf-green) !important;
        }

        /* ── FORGOT ROW ─────────────────────────────────────────── */
        .lf-forgot-row {
            display: flex;
            justify-content: flex-end;
            margin: -0.25rem 0 1.25rem;
        }

        .lf-forgot-link {
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--lf-green-mid);
            text-decoration: none;
            transition: color 0.15s;
        }

        .lf-forgot-link:hover {
            color: var(--lf-green);
            text-decoration: underline;
        }

        /* ── SUBMIT BUTTON ──────────────────────────────────────── */
        .lf-submit-wrap {
            margin-top: 0.25rem;
        }

        .lf-submit-wrap .fi-btn,
        .lf-submit-wrap button[type="submit"],
        .lf-submit-btn {
            width: 100% !important;
            min-height: 52px !important;
            border-radius: var(--lf-radius) !important;
            justify-content: center !important;
            font-family: var(--lf-font-body) !important;
            font-size: 0.84rem !important;
            font-weight: 600 !important;
            letter-spacing: 0.07em !important;
            text-transform: uppercase !important;
            background: var(--lf-green) !important;
            color: #ffffff !important;
            border: none !important;
            box-shadow: none !important;
            transition: background 0.18s, transform 0.12s !important;
        }

        .lf-submit-wrap .fi-btn:hover,
        .lf-submit-wrap button[type="submit"]:hover {
            background: var(--lf-green-dark) !important;
        }

        .lf-submit-wrap .fi-btn:active,
        .lf-submit-wrap button[type="submit"]:active {
            transform: scale(0.99) !important;
        }

        /* ── FOOTER ─────────────────────────────────────────────── */
        .lf-form-footer {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--lf-border);
        }

        .lf-footer-badges {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .lf-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.72rem;
            color: var(--lf-muted);
            font-weight: 500;
        }

        .lf-badge svg {
            width: 12px;
            height: 12px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
        }

        .lf-badge-sep {
            width: 3px;
            height: 3px;
            border-radius: 50%;
            background: var(--lf-border-md);
            display: inline-block;
        }

        .lf-footer-copy {
            text-align: center;
            font-size: 0.74rem;
            color: rgba(90, 90, 82, 0.5);
            line-height: 1.6;
        }

        .lf-root[data-theme="dark"] .lf-footer-copy {
            color: rgba(160, 160, 153, 0.5);
        }

        /* ── RESPONSIVE ─────────────────────────────────────────── */
        @media (max-width: 1200px) {
            .lf-body {
                grid-template-columns: 1fr 460px;
            }
        }

        @media (max-width: 960px) {
            .lf-body {
                grid-template-columns: 1fr;
            }

            .lf-hero {
                min-height: 440px;
            }

            .lf-hero-inner {
                padding: 2.5rem 2rem;
            }

            .lf-hero-title {
                font-size: clamp(1.9rem, 5vw, 2.8rem);
            }

            .lf-form-side {
                border-left: none;
                border-top: 1px solid var(--lf-border);
                padding: 2rem 1.5rem;
            }
        }

        @media (max-width: 640px) {
            .lf-header {
                padding: 0 1rem;
            }

            .lf-header-sub {
                display: none;
            }

            .lf-theme-btn span:nth-child(2) {
                display: none;
            }

            .lf-hero-inner {
                padding: 2rem 1.25rem;
            }

            .lf-hero-logo-ring {
                width: 64px;
                height: 64px;
                border-radius: 16px;
                margin-bottom: 1.5rem;
            }

            .lf-hero-logo-img {
                width: 38px;
                height: 38px;
            }

            .lf-hero-title {
                font-size: clamp(1.7rem, 7vw, 2.2rem);
            }

            .lf-form-side {
                padding: 1.5rem 1rem;
            }

            .lf-form-card {
                max-width: 100%;
            }

            .lf-form-title {
                font-size: 1.7rem;
            }

            .lf-form-side {
                order: 1;
                border-top: none;
                border-bottom: 1px solid var(--lf-border);
            }

            .lf-hero {
                order: 2;
                min-height: 420px;
            }
        }
    </style>


    {{-- ═══════════════════════════════════════════════════════════════
         SCRIPTS
    ════════════════════════════════════════════════════════════════ --}}
    <script>
        /* ── THEME ─────────────────────────────────────────────── */
        (function() {
            const ROOT = document.getElementById('lfRoot');
            const WRAP = document.getElementById('lfThemeWrap');
            const BTN = document.getElementById('lfThemeBtn');
            const MENU = document.getElementById('lfThemeMenu');
            const ICON = document.getElementById('lfThemeIcon');
            const LABEL = document.getElementById('lfThemeLabel');

            const ICONS = {
                light: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>',
                dark: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>',
                system: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>',
            };

            const LABELS = {
                light: 'Light',
                dark: 'Dark',
                system: 'System'
            };

            function resolveTheme(mode) {
                if (mode === 'dark') return 'dark';
                if (mode === 'light') return 'light';
                return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }

            function applyTheme(mode) {
                if (!ROOT) return;
                ROOT.setAttribute('data-theme', resolveTheme(mode));
                /* also keep Filament's dark class in sync */
                if (resolveTheme(mode) === 'dark') {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            }

            function updateUI(mode) {
                if (ICON) ICON.innerHTML = ICONS[mode] || ICONS.system;
                if (LABEL) LABEL.textContent = LABELS[mode] || 'System';
            }

            function openMenu() {
                if (WRAP) WRAP.classList.add('open');
                if (BTN) BTN.setAttribute('aria-expanded', 'true');
            }

            function closeMenu() {
                if (WRAP) WRAP.classList.remove('open');
                if (BTN) BTN.setAttribute('aria-expanded', 'false');
            }

            function setMode(mode) {
                localStorage.setItem('lf-theme', mode);
                applyTheme(mode);
                updateUI(mode);
                closeMenu();
            }

            /* init */
            const saved = localStorage.getItem('lf-theme') || 'system';
            applyTheme(saved);
            updateUI(saved);

            if (BTN) BTN.addEventListener('click', function(e) {
                e.stopPropagation();
                WRAP.classList.contains('open') ? closeMenu() : openMenu();
            });

            document.querySelectorAll('.lf-theme-opt').forEach(function(opt) {
                opt.addEventListener('click', function() {
                    setMode(this.getAttribute('data-mode'));
                });
            });

            document.addEventListener('click', function(e) {
                if (WRAP && !WRAP.contains(e.target)) closeMenu();
            });
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') closeMenu();
            });

            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function() {
                if ((localStorage.getItem('lf-theme') || 'system') === 'system') applyTheme('system');
            });
        })();

        /* ── TYPING ANIMATION ──────────────────────────────────── */
        document.addEventListener('DOMContentLoaded', function() {
            var el = document.getElementById('lfTyping');
            var text = "{{ addslashes($heroText) }}";
            var i = 0,
                deleting = false;

            function tick() {
                if (!el) return;
                if (!deleting) {
                    el.textContent = text.slice(0, ++i);
                    if (i === text.length) {
                        deleting = true;
                        setTimeout(tick, 2200);
                        return;
                    }
                    setTimeout(tick, 30);
                } else {
                    el.textContent = text.slice(0, --i);
                    if (i === 0) {
                        deleting = false;
                        setTimeout(tick, 600);
                        return;
                    }
                    setTimeout(tick, 15);
                }
            }
            tick();
        });

        /* ── PASSWORD TOGGLE ───────────────────────────────────── */
        (function attachPasswordToggles() {
            var SVG_SHOW = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
            var SVG_HIDE =
                '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>';

            function makeSVG(inner) {
                return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;display:block;">' +
                    inner + '</svg>';
            }

            function attach(input) {
                if (!input || input.dataset.lfPwReady) return;
                var wrp = input.closest('.fi-input-wrp');
                if (!wrp) return;

                /* hide Filament's built-in suffix toggle if present */
                var builtin = wrp.querySelector('.fi-input-wrp-suffix');
                if (builtin) builtin.style.display = 'none';

                input.style.paddingRight = '48px';

                var btn = document.createElement('button');
                btn.type = 'button';
                btn.setAttribute('aria-label', 'Show password');
                btn.innerHTML = makeSVG(SVG_SHOW);
                btn.style.cssText = [
                    'position:absolute', 'right:10px', 'top:50%', 'transform:translateY(-50%)',
                    'width:34px', 'height:34px', 'border:none', 'background:none',
                    'cursor:pointer', 'display:flex', 'align-items:center', 'justify-content:center',
                    'border-radius:7px', 'color:var(--lf-muted)', 'z-index:5', 'padding:0',
                ].join(';');

                btn.addEventListener('mouseover', function() {
                    this.style.background = 'var(--lf-surface)';
                });
                btn.addEventListener('mouseout', function() {
                    this.style.background = 'none';
                });

                btn.addEventListener('click', function() {
                    var show = input.type === 'password';
                    input.type = show ? 'text' : 'password';
                    btn.innerHTML = makeSVG(show ? SVG_HIDE : SVG_SHOW);
                    btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                });

                wrp.style.position = 'relative';
                wrp.appendChild(btn);
                input.dataset.lfPwReady = '1';
            }

            function scan() {
                var scope = document.getElementById('lfFormFields') || document;
                scope.querySelectorAll(
                        'input[type="password"], input[autocomplete="current-password"], input[autocomplete="new-password"]'
                    )
                    .forEach(attach);
            }

            document.addEventListener('DOMContentLoaded', scan);
            document.addEventListener('livewire:navigated', scan);
            document.addEventListener('livewire:load', scan);

            var observer = new MutationObserver(scan);
            window.addEventListener('DOMContentLoaded', function() {
                var f = document.getElementById('lfFormFields');
                if (f) observer.observe(f, {
                    childList: true,
                    subtree: true
                });
            });
        })();
    </script>

</x-filament-panels::page.simple>
