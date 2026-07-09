<x-filament-panels::page>
    @php
        $primaryColor = trim(setting('theme.primary', '#008f00'));
        $secondaryColor = trim(setting('theme.secondary', '#111827'));
        $accentColor = trim(setting('theme.accent', '#f59e0b'));
    @endphp

    <style>
        .payment-page {
            --payment-primary: {{ $primaryColor }};
            --payment-secondary: {{ $secondaryColor }};
            --payment-accent: {{ $accentColor }};
        }

        .payment-hero {
            position: relative;
            overflow: hidden;
            border-radius: 30px;
            border: 1px solid rgba(255, 255, 255, .18);
            background:
                radial-gradient(circle at 88% 8%, rgba(255, 255, 255, .22), transparent 26%),
                radial-gradient(circle at 10% 92%, rgba(0, 0, 0, .22), transparent 32%),
                linear-gradient(135deg, var(--payment-primary), var(--payment-secondary));
            box-shadow: 0 24px 60px rgba(15, 23, 42, .24);
            isolation: isolate;
        }

        .payment-hero::before,
        .payment-hero::after {
            position: absolute;
            border-radius: 999px;
            content: "";
            pointer-events: none;
            z-index: 0;
        }

        .payment-hero::before {
            right: -80px;
            top: -85px;
            width: 280px;
            height: 280px;
            background: rgba(255, 255, 255, .09);
        }

        .payment-hero::after {
            left: -90px;
            bottom: -100px;
            width: 280px;
            height: 280px;
            background: rgba(0, 0, 0, .14);
        }

        .payment-hero-grid {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: minmax(0, 1fr) 390px;
            gap: 32px;
            align-items: center;
            padding: 34px;
        }

        .payment-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            border: 1px solid rgba(255, 255, 255, .22);
            border-radius: 999px;
            background: rgba(255, 255, 255, .15);
            padding: 7px 15px;
            color: #ffffff;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: .20em;
            text-transform: uppercase;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .14);
            backdrop-filter: blur(16px);
        }

        .payment-title {
            margin-top: 20px;
            max-width: 860px;
            color: #ffffff;
            font-size: clamp(28px, 4vw, 46px);
            font-weight: 950;
            line-height: 1.03;
            letter-spacing: -.055em;
        }

        .payment-copy {
            margin-top: 16px;
            max-width: 780px;
            color: rgba(255, 255, 255, .86);
            font-size: 15px;
            line-height: 1.75;
        }

        .payment-feature-grid {
            margin-top: 24px;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 11px;
            max-width: 760px;
        }

        .payment-feature-pill {
            display: inline-flex;
            min-height: 46px;
            align-items: center;
            gap: 9px;
            border: 1px solid rgba(255, 255, 255, .17);
            border-radius: 15px;
            background: rgba(255, 255, 255, .12);
            padding: 10px 13px;
            color: #ffffff;
            font-size: 11px;
            font-weight: 850;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .12);
            backdrop-filter: blur(14px);
        }

        .payment-feature-pill svg {
            width: 17px;
            height: 17px;
            flex: 0 0 auto;
        }

        .payment-status-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .payment-glass-card {
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            gap: 13px;
            border: 1px solid rgba(255, 255, 255, .17);
            border-radius: 20px;
            background: rgba(255, 255, 255, .13);
            padding: 15px;
            color: #ffffff;
            box-shadow: 0 16px 38px rgba(15, 23, 42, .18);
            backdrop-filter: blur(18px);
        }

        .payment-glass-card::after {
            position: absolute;
            right: -24px;
            top: -28px;
            width: 88px;
            height: 88px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .10);
            content: "";
        }

        .payment-glass-icon {
            display: flex;
            width: 48px;
            height: 48px;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            border-radius: 17px;
            background: rgba(255, 255, 255, .15);
            ring: 1px solid rgba(255, 255, 255, .20);
        }

        .payment-glass-icon svg {
            width: 23px;
            height: 23px;
        }

        .payment-glass-title {
            font-size: 13px;
            font-weight: 950;
            line-height: 1.1;
            letter-spacing: .04em;
        }

        .payment-glass-subtitle {
            margin-top: 4px;
            color: rgba(255, 255, 255, .70);
            font-size: 9px;
            font-weight: 850;
            letter-spacing: .18em;
            text-transform: uppercase;
        }

        .payment-form-shell {
            margin-top: 24px;
            overflow: hidden;
            border: 1px solid rgba(148, 163, 184, .24);
            border-radius: 26px;
            background:
                radial-gradient(circle at top right, color-mix(in srgb, var(--payment-primary) 10%, transparent), transparent 30%),
                linear-gradient(135deg, #ffffff, #f8fafc);
            box-shadow: 0 14px 36px rgba(15, 23, 42, .07);
        }

        .payment-form-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
            border-bottom: 1px solid rgba(148, 163, 184, .20);
            background:
                linear-gradient(135deg, color-mix(in srgb, var(--payment-primary) 9%, #ffffff), #ffffff);
            padding: 18px 20px;
        }

        .payment-form-heading {
            color: #0f172a;
            font-size: 16px;
            font-weight: 950;
            line-height: 1.25;
        }

        .payment-form-description {
            margin-top: 4px;
            max-width: 780px;
            color: #64748b;
            font-size: 12px;
            line-height: 1.55;
        }

        .payment-form-badge {
            display: inline-flex;
            flex: 0 0 auto;
            align-items: center;
            gap: 7px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--payment-primary) 12%, #ffffff);
            color: var(--payment-primary);
            padding: 8px 11px;
            font-size: 11px;
            font-weight: 900;
            white-space: nowrap;
        }

        .payment-form-body {
            padding: 18px;
        }

        .payment-save-bar {
            position: sticky;
            right: 0;
            bottom: 18px;
            z-index: 30;
            display: flex;
            justify-content: flex-end;
            pointer-events: none;
        }

        .payment-save-card {
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid rgba(148, 163, 184, .28);
            border-radius: 20px;
            background: rgba(255, 255, 255, .92);
            padding: 9px;
            box-shadow: 0 18px 46px rgba(15, 23, 42, .18);
            backdrop-filter: blur(18px);
            pointer-events: auto;
        }

        .payment-save-note {
            max-width: 260px;
            color: #64748b;
            font-size: 11px;
            line-height: 1.35;
            padding-left: 6px;
        }

        /*
         * Filament tabs responsiveness inside the form.
         * This keeps tabs usable on phones instead of squeezing or breaking.
         */
        .payment-form-shell .fi-tabs,
        .payment-form-shell [role="tablist"] {
            max-width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            display: flex;
            flex-wrap: nowrap;
            gap: 6px;
            padding-bottom: 4px;
            scrollbar-width: thin;
            -webkit-overflow-scrolling: touch;
        }

        .payment-form-shell .fi-tabs button,
        .payment-form-shell .fi-tabs-item,
        .payment-form-shell [role="tab"] {
            flex: 0 0 auto;
            white-space: nowrap;
        }

        .payment-form-shell .fi-tabs::-webkit-scrollbar,
        .payment-form-shell [role="tablist"]::-webkit-scrollbar {
            height: 6px;
        }

        .payment-form-shell .fi-tabs::-webkit-scrollbar-thumb,
        .payment-form-shell [role="tablist"]::-webkit-scrollbar-thumb {
            border-radius: 999px;
            background: rgba(148, 163, 184, .55);
        }

        .payment-form-shell .fi-section,
        .payment-form-shell .fi-fo-section {
            border-radius: 18px;
        }

        .payment-form-shell input,
        .payment-form-shell textarea,
        .payment-form-shell select {
            max-width: 100%;
        }

        @media (max-width: 1180px) {
            .payment-hero-grid {
                grid-template-columns: 1fr;
            }

            .payment-status-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 860px) {
            .payment-hero-grid {
                padding: 26px 20px;
            }

            .payment-feature-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .payment-form-header {
                flex-direction: column;
            }

            .payment-form-badge {
                width: fit-content;
            }
        }

        @media (max-width: 640px) {
            .payment-hero {
                border-radius: 22px;
            }

            .payment-hero-grid {
                padding: 22px 15px;
                gap: 22px;
            }

            .payment-eyebrow {
                max-width: 100%;
                font-size: 9px;
                letter-spacing: .14em;
                padding: 7px 11px;
            }

            .payment-copy {
                font-size: 13px;
                line-height: 1.65;
            }

            .payment-feature-grid {
                grid-template-columns: 1fr;
                gap: 8px;
            }

            .payment-feature-pill {
                min-height: 42px;
                justify-content: flex-start;
            }

            .payment-status-grid {
                grid-template-columns: 1fr;
                gap: 9px;
            }

            .payment-glass-card {
                padding: 13px;
            }

            .payment-glass-icon {
                width: 42px;
                height: 42px;
                border-radius: 14px;
            }

            .payment-form-shell {
                border-radius: 20px;
            }

            .payment-form-header {
                padding: 15px;
            }

            .payment-form-body {
                padding: 12px;
            }

            .payment-save-bar {
                bottom: 10px;
                justify-content: stretch;
            }

            .payment-save-card {
                width: 100%;
                justify-content: space-between;
                border-radius: 17px;
            }

            .payment-save-note {
                display: none;
            }

            .payment-save-card .fi-btn {
                width: 100%;
                justify-content: center;
            }
        }

        .dark .payment-form-shell {
            border-color: rgba(148, 163, 184, .20);
            background:
                radial-gradient(circle at top right, color-mix(in srgb, var(--payment-primary) 15%, transparent), transparent 30%),
                linear-gradient(135deg, #111827, #020617);
        }

        .dark .payment-form-header {
            border-color: rgba(148, 163, 184, .20);
            background:
                linear-gradient(135deg, color-mix(in srgb, var(--payment-primary) 18%, #111827), #020617);
        }

        .dark .payment-form-heading {
            color: #f8fafc;
        }

        .dark .payment-form-description {
            color: #cbd5e1;
        }

        .dark .payment-form-badge {
            background: color-mix(in srgb, var(--payment-primary) 24%, #111827);
            color: #ffffff;
        }

        .dark .payment-save-card {
            border-color: rgba(148, 163, 184, .24);
            background: rgba(17, 24, 39, .92);
        }

        .dark .payment-save-note {
            color: #cbd5e1;
        }
    </style>

    <div class="payment-page">
        <div class="payment-hero">
            <div class="payment-hero-grid">
                <div>
                    <div class="payment-eyebrow">
                        <x-heroicon-m-credit-card class="h-4 w-4" />
                        Payment Infrastructure
                    </div>

                    <h1 class="payment-title">
                        Payment Configuration
                    </h1>

                    <p class="payment-copy">
                        Manage M-Pesa Daraja credentials, paybill and till instructions, bank transfer details,
                        customer invoice notes, payment logos, official stamp, and authorized invoice signature.
                    </p>

                    <div class="payment-feature-grid">
                        <div class="payment-feature-pill">
                            <x-heroicon-m-device-phone-mobile />
                            <span>STK Push</span>
                        </div>

                        <div class="payment-feature-pill">
                            <x-heroicon-m-banknotes />
                            <span>Paybill / Till</span>
                        </div>

                        <div class="payment-feature-pill">
                            <x-heroicon-m-building-library />
                            <span>Bank Transfer</span>
                        </div>

                        <div class="payment-feature-pill">
                            <x-heroicon-m-finger-print />
                            <span>Stamp & Signature</span>
                        </div>
                    </div>
                </div>

                <div class="payment-status-grid">
                    <div class="payment-glass-card">
                        <div class="payment-glass-icon">
                            <x-heroicon-o-device-phone-mobile class="text-white" />
                        </div>

                        <div>
                            <div class="payment-glass-title">M-PESA</div>
                            <div class="payment-glass-subtitle">Daraja Gateway</div>
                        </div>
                    </div>

                    <div class="payment-glass-card">
                        <div class="payment-glass-icon">
                            <x-heroicon-o-building-library class="text-white" />
                        </div>

                        <div>
                            <div class="payment-glass-title">BANK</div>
                            <div class="payment-glass-subtitle">Transfer Details</div>
                        </div>
                    </div>

                    <div class="payment-glass-card">
                        <div class="payment-glass-icon">
                            <x-heroicon-o-document-check class="text-white" />
                        </div>

                        <div>
                            <div class="payment-glass-title">INVOICE</div>
                            <div class="payment-glass-subtitle">Branding Rules</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <form wire:submit.prevent="save" class="space-y-6">
            <div class="payment-form-shell">
                <div class="payment-form-header">
                    <div>
                        <div class="payment-form-heading">
                            Payment Settings Workspace
                        </div>

                        <div class="payment-form-description">
                            Update credentials, payment channels, invoice instructions, logos, stamp and signature.
                            Tabs are scrollable on small screens for easier mobile use.
                        </div>
                    </div>

                    <div class="payment-form-badge">
                        <x-heroicon-o-shield-check class="h-4 w-4" />
                        Secure Settings
                    </div>
                </div>

                <div class="payment-form-body">
                    {{ $this->form }}
                </div>
            </div>

            <div class="payment-save-bar">
                <div class="payment-save-card">
                    <div class="payment-save-note">
                        Save changes after updating credentials, logos, stamp or signature.
                    </div>

                    <x-filament::button
                        type="submit"
                        icon="heroicon-o-check-circle"
                        size="lg"
                    >
                        Save Payment Settings
                    </x-filament::button>
                </div>
            </div>
        </form>
    </div>
</x-filament-panels::page>
