<x-filament-panels::page>
    @php
        /** @var \App\Models\AuditSession $record */
        $record = $this->getRecord();

        $logs = $record->logs()->latest('created_at')->get();

        $primaryColor = trim(setting('theme.primary', '#014a12'));
        $secondaryColor = trim(setting('theme.secondary', '#111827'));
        $accentColor = trim(setting('theme.accent', '#f59e0b'));

        $closedAt = $record->logout_at ?: $record->last_seen_at;

        $riskEvents = $logs->filter(
            fn($log) => in_array(
                $log->event,
                [
                    'deleted',
                    'force_deleted',
                    'failed_login',
                    'rejected',
                    'stock_adjustment',
                    'permission_changed',
                    'payment_deleted',
                    'payment_updated',
                    'backdated_transaction',
                ],
                true,
            ),
        );

        $createdCount = $logs->where('event', 'created')->count();
        $updatedCount = $logs->where('event', 'updated')->count();
        $printedCount = $logs->where('event', 'printed')->count();
        $exportedCount = $logs->where('event', 'exported')->count();
        $deletedCount = $logs->whereIn('event', ['deleted', 'force_deleted'])->count();

        $initials = collect(explode(' ', $record->actor_label))
            ->filter()
            ->map(fn($part) => mb_substr($part, 0, 1))
            ->take(2)
            ->implode('');

        $statusColor = $record->status === 'active' ? '#16a34a' : '#64748b';

        $kpis = [
            [
                'label' => 'HTTP Requests',
                'value' => number_format((int) $record->request_count),
                'badge' => 'Network',
                'icon' => 'heroicon-o-globe-alt',
                'color' => $primaryColor,
            ],
            [
                'label' => 'Audit Events',
                'value' => number_format($logs->count()),
                'badge' => 'Logged',
                'icon' => 'heroicon-o-circle-stack',
                'color' => '#0ea5e9',
            ],
            [
                'label' => 'Risk Events',
                'value' => number_format($riskEvents->count()),
                'badge' => 'Critical',
                'icon' => 'heroicon-o-shield-exclamation',
                'color' => '#dc2626',
            ],
            [
                'label' => 'Session Duration',
                'value' => $record->duration_label,
                'badge' => $record->status === 'active' ? 'Active' : 'Closed',
                'icon' => 'heroicon-o-clock',
                'color' => $accentColor,
            ],
        ];

        $sessionDetails = [
            ['User', $record->actor_label, 'heroicon-o-user-circle'],
            ['Email', $record->user_email ?: '—', 'heroicon-o-envelope'],
            ['Status', $record->status_label, 'heroicon-o-shield-check'],
            ['Close Reason', $record->logout_reason_label, 'heroicon-o-lock-closed'],
            ['Login Time', $record->login_at?->format('d M Y, H:i:s') ?: '—', 'heroicon-o-arrow-left-on-rectangle'],
            ['Last Seen / Closed', $closedAt?->format('d M Y, H:i:s') ?: '—', 'heroicon-o-arrow-right-on-rectangle'],
            ['Email Sent To', $record->email_to ?: 'Not sent', 'heroicon-o-paper-airplane'],
            ['Email Sent At', $record->emailed_at?->format('d M Y, H:i:s') ?: 'Not sent', 'heroicon-o-check-circle'],
        ];
    @endphp

    <style>
        .audit-view {
            --av-primary: {{ $primaryColor }};
            --av-secondary: {{ $secondaryColor }};
            --av-accent: {{ $accentColor }};
            --av-bg: #f6f8fb;
            --av-card: #ffffff;
            --av-card-soft: #f8fafc;
            --av-border: rgba(15, 23, 42, .10);
            --av-border-strong: rgba(15, 23, 42, .16);
            --av-text: #0f172a;
            --av-muted: #64748b;
            --av-soft: #94a3b8;
            --av-shadow: 0 18px 55px rgba(15, 23, 42, .09);
            --av-shadow-sm: 0 8px 28px rgba(15, 23, 42, .06);
            color: var(--av-text);
        }

        .dark .audit-view {
            --av-bg: #020617;
            --av-card: #0f172a;
            --av-card-soft: #111827;
            --av-border: rgba(255, 255, 255, .08);
            --av-border-strong: rgba(255, 255, 255, .14);
            --av-text: #f8fafc;
            --av-muted: #cbd5e1;
            --av-soft: #94a3b8;
            --av-shadow: 0 18px 55px rgba(0, 0, 0, .45);
            --av-shadow-sm: 0 8px 28px rgba(0, 0, 0, .35);
        }

        .av-shell {
            width: 100%;
            min-height: 100vh;
            border-radius: 2rem;
            padding: 1.15rem;
            background:
                radial-gradient(circle at 0% 0%, color-mix(in srgb, var(--av-primary) 14%, transparent), transparent 34%),
                radial-gradient(circle at 100% 16%, color-mix(in srgb, var(--av-accent) 10%, transparent), transparent 30%),
                var(--av-bg);
        }

        .av-wrap {
            width: 100%;
            max-width: 1660px;
            margin: 0 auto;
        }

        .av-hero {
            position: relative;
            overflow: hidden;
            border-radius: 1.75rem;
            background:
                radial-gradient(circle at 12% 10%, rgba(255, 255, 255, .20), transparent 28%),
                radial-gradient(circle at 95% 0%, color-mix(in srgb, var(--av-accent) 42%, transparent), transparent 34%),
                linear-gradient(135deg, var(--av-primary), var(--av-secondary));
            color: #fff;
            box-shadow: var(--av-shadow);
        }

        .av-hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                linear-gradient(90deg, rgba(2, 6, 23, .06), rgba(2, 6, 23, .25)),
                repeating-linear-gradient(135deg,
                    rgba(255, 255, 255, .045) 0,
                    rgba(255, 255, 255, .045) 1px,
                    transparent 1px,
                    transparent 16px);
            pointer-events: none;
        }

        .av-hero-inner {
            position: relative;
            z-index: 1;
            padding: 1.25rem;
        }

        @media (min-width: 768px) {
            .av-hero-inner {
                padding: 1.5rem;
            }
        }

        .av-chip {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            border-radius: 999px;
            padding: .4rem .72rem;
            background: rgba(255, 255, 255, .14);
            border: 1px solid rgba(255, 255, 255, .24);
            color: #fff;
            font-size: .66rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .08em;
            backdrop-filter: blur(14px);
            white-space: nowrap;
        }

        .av-chip svg {
            width: .9rem;
            height: .9rem;
        }

        .av-uuid-card {
            border-radius: 1.35rem;
            padding: 1rem;
            background: rgba(255, 255, 255, .13);
            border: 1px solid rgba(255, 255, 255, .24);
            backdrop-filter: blur(18px);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .08);
        }

        .av-uuid-label {
            display: flex;
            align-items: center;
            gap: .4rem;
            margin-bottom: .55rem;
            color: rgba(255, 255, 255, .62);
            font-size: .62rem;
            font-weight: 950;
            letter-spacing: .14em;
            text-transform: uppercase;
        }

        .av-uuid-value {
            display: block;
            padding-top: .05rem;
            color: #fff;
            font-size: .72rem;
            font-weight: 800;
            line-height: 1.65;
            word-break: break-all;
        }

        .av-status-line {
            margin-top: .85rem;
            display: flex;
            align-items: center;
            gap: .5rem;
            color: rgba(255, 255, 255, .76);
            font-size: .72rem;
            font-weight: 800;
        }

        .av-status-dot {
            width: .5rem;
            height: .5rem;
            border-radius: 999px;
            background: {{ $statusColor }};
            box-shadow: 0 0 0 5px color-mix(in srgb, {{ $statusColor }} 18%, transparent);
            flex-shrink: 0;
        }

        .av-kpi {
            position: relative;
            overflow: hidden;
            min-height: 84px;
            border-radius: 1.2rem;
            padding: .78rem .88rem;
            background: var(--av-card);
            border: 1px solid var(--av-border);
            box-shadow: var(--av-shadow-sm);
        }

        .av-kpi::after {
            content: "";
            position: absolute;
            top: -44px;
            right: -34px;
            width: 95px;
            height: 95px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--kpi-color) 15%, transparent);
        }

        .av-kpi-top {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .65rem;
        }

        .av-kpi-icon {
            width: 2.15rem;
            height: 2.15rem;
            border-radius: .85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            background: var(--kpi-color);
            box-shadow: 0 10px 24px color-mix(in srgb, var(--kpi-color) 28%, transparent);
        }

        .av-kpi-icon svg {
            width: 1rem;
            height: 1rem;
        }

        .av-kpi-badge {
            border-radius: 999px;
            padding: .2rem .52rem;
            font-size: .56rem;
            font-weight: 950;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: var(--kpi-color);
            background: color-mix(in srgb, var(--kpi-color) 10%, transparent);
            border: 1px solid color-mix(in srgb, var(--kpi-color) 23%, transparent);
            white-space: nowrap;
        }

        .av-kpi-value {
            position: relative;
            z-index: 1;
            margin-top: .58rem;
            font-size: 1.12rem;
            line-height: 1.05;
            font-weight: 950;
            letter-spacing: -.025em;
        }

        .av-kpi-label {
            position: relative;
            z-index: 1;
            margin-top: .15rem;
            color: var(--av-soft);
            font-size: .6rem;
            font-weight: 950;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .av-card {
            overflow: hidden;
            border-radius: 1.35rem;
            background: var(--av-card);
            border: 1px solid var(--av-border);
            box-shadow: var(--av-shadow-sm);
        }

        .av-card-header {
            display: flex;
            align-items: flex-start;
            gap: .78rem;
            padding: 1rem 1.1rem;
            border-bottom: 1px solid var(--av-border);
            background:
                linear-gradient(135deg, color-mix(in srgb, var(--av-primary) 6%, transparent), transparent),
                var(--av-card);
        }

        .av-card-icon {
            width: 2.15rem;
            height: 2.15rem;
            border-radius: .85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            flex-shrink: 0;
            margin-top: .05rem;
        }

        .av-card-icon svg {
            width: 1.05rem;
            height: 1.05rem;
        }

        .av-card-title {
            margin-top: .05rem;
            margin-bottom: .22rem;
            color: var(--av-text);
            font-size: .92rem;
            line-height: 1.25;
            font-weight: 950;
            letter-spacing: -.015em;
        }

        .av-card-sub {
            margin-top: .2rem;
            color: var(--av-muted);
            font-size: .72rem;
            line-height: 1.45;
        }

        .av-count {
            margin-left: auto;
            flex-shrink: 0;
            align-self: center;
            border-radius: 999px;
            padding: .25rem .65rem;
            color: var(--av-primary);
            background: color-mix(in srgb, var(--av-primary) 10%, transparent);
            border: 1px solid color-mix(in srgb, var(--av-primary) 23%, transparent);
            font-size: .62rem;
            font-weight: 950;
            text-transform: uppercase;
            letter-spacing: .07em;
        }

        .dark .av-count {
            color: #bbf7d0;
        }

        .av-scroll {
            max-height: 620px;
            overflow-y: auto;
            padding: .75rem;
            display: flex;
            flex-direction: column;
            gap: .55rem;
        }

        .av-scroll::-webkit-scrollbar,
        .av-mini-scroll::-webkit-scrollbar,
        .av-json::-webkit-scrollbar {
            width: 6px;
        }

        .av-scroll::-webkit-scrollbar-track,
        .av-mini-scroll::-webkit-scrollbar-track,
        .av-json::-webkit-scrollbar-track {
            background: transparent;
        }

        .av-scroll::-webkit-scrollbar-thumb,
        .av-mini-scroll::-webkit-scrollbar-thumb,
        .av-json::-webkit-scrollbar-thumb {
            border-radius: 999px;
            background: color-mix(in srgb, var(--av-primary) 38%, transparent);
        }

        .av-log {
            border-radius: 1rem;
            padding: .62rem .72rem;
            background: var(--av-card-soft);
            border: 1px solid var(--av-border);
            transition: .16s ease;
        }

        .av-log:hover {
            border-color: var(--av-border-strong);
            transform: translateY(-1px);
        }

        .av-log-icon {
            width: 1.9rem;
            height: 1.9rem;
            border-radius: .72rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            flex-shrink: 0;
        }

        .av-log-icon svg {
            width: .92rem;
            height: .92rem;
        }

        .av-log-title {
            font-size: .8rem;
            font-weight: 920;
            line-height: 1.32;
        }

        .av-log-module {
            color: var(--av-soft);
            font-weight: 700;
        }

        .av-log-desc {
            margin-top: .14rem;
            color: var(--av-muted);
            font-size: .7rem;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .av-severity {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: .14rem .48rem;
            font-size: .55rem;
            font-weight: 950;
            text-transform: uppercase;
            letter-spacing: .07em;
            white-space: nowrap;
        }

        .av-meta {
            display: flex;
            flex-wrap: wrap;
            gap: .36rem .6rem;
            margin-top: .45rem;
            color: var(--av-soft);
            font-size: .62rem;
            line-height: 1.4;
        }

        .av-meta span {
            display: inline-flex;
            align-items: center;
            gap: .24rem;
        }

        .av-meta svg {
            width: .76rem;
            height: .76rem;
        }

        .av-details {
            margin-top: .45rem;
            border-radius: .8rem;
            overflow: hidden;
            background: color-mix(in srgb, var(--av-card) 78%, transparent);
            border: 1px solid var(--av-border);
        }

        .av-details summary {
            cursor: pointer;
            list-style: none;
            padding: .38rem .6rem;
            color: var(--av-muted);
            font-size: .6rem;
            font-weight: 950;
            text-transform: uppercase;
            letter-spacing: .07em;
            user-select: none;
        }

        .av-details summary::-webkit-details-marker {
            display: none;
        }

        .av-details summary::before {
            content: "›";
            display: inline-block;
            margin-right: .35rem;
            font-size: .88rem;
            line-height: .5;
            transition: transform .15s ease;
        }

        .av-details[open] summary::before {
            transform: rotate(90deg);
        }

        .av-json-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: .55rem;
            padding: .58rem;
            border-top: 1px solid var(--av-border);
        }

        @media (min-width: 1024px) {
            .av-json-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        .av-json-label {
            margin-bottom: .3rem;
            color: var(--av-soft);
            font-size: .56rem;
            font-weight: 950;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .av-json {
            max-height: 130px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-break: break-word;
            border-radius: .75rem;
            background: #020617;
            padding: .52rem .62rem;
            color: #86efac;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: .6rem;
            line-height: 1.5;
        }

        .av-profile {
            padding: .85rem;
            border-bottom: 1px solid var(--av-border);
            background:
                radial-gradient(circle at top right, color-mix(in srgb, var(--av-accent) 12%, transparent), transparent 38%),
                var(--av-card);
        }

        .av-avatar {
            width: 2.8rem;
            height: 2.8rem;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 950;
            background: linear-gradient(135deg, var(--av-primary), var(--av-secondary));
            box-shadow: 0 12px 24px color-mix(in srgb, var(--av-primary) 25%, transparent);
            flex-shrink: 0;
        }

        .av-status {
            display: inline-flex;
            align-items: center;
            gap: .33rem;
            border-radius: 999px;
            padding: .22rem .55rem;
            font-size: .58rem;
            font-weight: 950;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: {{ $statusColor }};
            background: color-mix(in srgb, {{ $statusColor }} 10%, transparent);
            border: 1px solid color-mix(in srgb, {{ $statusColor }} 24%, transparent);
        }

        .av-status span {
            width: .38rem;
            height: .38rem;
            border-radius: 999px;
            background: {{ $statusColor }};
            box-shadow: 0 0 0 4px color-mix(in srgb, {{ $statusColor }} 11%, transparent);
        }

        .av-detail-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: .45rem;
            padding: .72rem;
        }

        .av-detail-row {
            display: grid;
            grid-template-columns: 1.15rem minmax(0, 1fr);
            gap: .5rem;
            align-items: start;
            border-radius: .85rem;
            padding: .52rem .6rem;
            background: var(--av-card-soft);
            border: 1px solid var(--av-border);
        }

        .av-detail-icon {
            margin-top: .05rem;
            color: var(--av-soft);
        }

        .av-detail-icon svg {
            width: .9rem;
            height: .9rem;
        }

        .av-detail-label {
            color: var(--av-soft);
            font-size: .56rem;
            font-weight: 950;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .av-detail-value {
            margin-top: .08rem;
            color: var(--av-text);
            font-size: .73rem;
            font-weight: 780;
            line-height: 1.38;
            word-break: break-word;
        }

        .av-breakdown-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .5rem;
            padding: .72rem;
        }

        .av-mini-card {
            border-radius: .9rem;
            padding: .62rem .65rem;
            background: var(--av-card-soft);
            border: 1px solid var(--av-border);
        }

        .av-mini-value {
            font-size: 1.05rem;
            line-height: 1;
            font-weight: 950;
        }

        .av-mini-label {
            margin-top: .28rem;
            font-size: .56rem;
            font-weight: 950;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .av-trace-stack {
            padding: .72rem;
            display: flex;
            flex-direction: column;
            gap: .55rem;
        }

        .av-trace {
            border-radius: .9rem;
            padding: .64rem .7rem;
            background: var(--av-card-soft);
            border: 1px solid var(--av-border);
        }

        .av-trace-label {
            display: flex;
            align-items: center;
            gap: .35rem;
            color: var(--av-soft);
            font-size: .58rem;
            font-weight: 950;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .av-trace-label svg {
            width: .85rem;
            height: .85rem;
        }

        .av-trace-value {
            margin-top: .25rem;
            color: var(--av-text);
            font-size: .72rem;
            line-height: 1.45;
            font-weight: 760;
            word-break: break-word;
        }

        .av-mini-scroll {
            max-height: 78px;
            overflow-y: auto;
            padding-right: .2rem;
        }

        .av-sticky {
            position: sticky;
            top: 1rem;
        }

        .av-risk-list {
            padding: .72rem;
            display: flex;
            flex-direction: column;
            gap: .5rem;
        }

        .av-risk-item {
            display: flex;
            align-items: center;
            gap: .55rem;
            border-radius: .85rem;
            padding: .5rem .62rem;
            background: rgba(220, 38, 38, .06);
            border: 1px solid rgba(220, 38, 38, .13);
        }

        .av-risk-dot {
            width: .38rem;
            height: .38rem;
            border-radius: 999px;
            background: #dc2626;
            flex-shrink: 0;
            box-shadow: 0 0 0 4px rgba(220, 38, 38, .10);
        }

        @media (max-width: 640px) {
            .av-shell {
                padding: .75rem;
                border-radius: 1.35rem;
            }

            .av-hero {
                border-radius: 1.35rem;
            }

            .av-scroll {
                max-height: 540px;
            }
        }
    </style>

    <div class="audit-view">
        <div class="av-shell">
            <div class="av-wrap space-y-5">

                {{-- HERO --}}
                <section class="av-hero">
                    <div class="av-hero-inner">
                        <div class="grid grid-cols-1 gap-5 xl:grid-cols-12 xl:items-center">
                            <div class="xl:col-span-8">
                                <div class="flex flex-wrap gap-2">
                                    <span class="av-chip">
                                        <x-heroicon-o-finger-print />
                                        Audit Session
                                    </span>

                                    <span class="av-chip">
                                        <x-heroicon-o-user-circle />
                                        {{ $record->actor_label }}
                                    </span>

                                    <span class="av-chip">
                                        <x-heroicon-o-shield-check />
                                        {{ $record->status_label }}
                                    </span>
                                </div>

                                <h5 class="mt-4 text-l font-black leading-tight tracking-tight sm:text-2xl lg:text-3xl">
                                    User Session Audit Trail
                                </h5>

                                <p class="mt-2 max-w-4xl text-sm leading-6 text-white/80">
                                    A complete forensic review of this session, including login activity, page
                                    navigation, record changes, printed reports,
                                    risk events, IP address, browser details, and system trace data.
                                </p>

                                <div class="mt-4 flex flex-wrap gap-3 text-xs font-bold text-white/72">
                                    @if ($record->login_at)
                                        <span class="inline-flex items-center gap-1.5">
                                            <x-heroicon-o-arrow-left-on-rectangle class="h-4 w-4" />
                                            Login: {{ $record->login_at->format('d M Y, H:i') }}
                                        </span>
                                    @endif

                                    @if ($closedAt)
                                        <span class="inline-flex items-center gap-1.5">
                                            <x-heroicon-o-arrow-right-on-rectangle class="h-4 w-4" />
                                            Last seen: {{ $closedAt->format('d M Y, H:i') }}
                                        </span>
                                    @endif

                                    @if ($record->ip_address)
                                        <span class="inline-flex items-center gap-1.5">
                                            <x-heroicon-o-globe-alt class="h-4 w-4" />
                                            {{ $record->ip_address }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <br>

                            <div class="xl:col-span-4 p-10">
                                <br>
                                <div class="av-uuid-card">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0 flex-1">
                                            <div class="av-uuid-label">
                                                <x-heroicon-o-key class="h-3.5 w-3.5" />
                                                Session UUID
                                            </div>

                                            <div class="av-uuid-value font-mono">
                                                {{ $record->uuid ?: '—' }}
                                            </div>
                                        </div>

                                        <div
                                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white/15 text-white ring-1 ring-white/20">
                                            <x-heroicon-o-finger-print class="h-5 w-5" />
                                        </div>
                                    </div>

                                    <div class="av-status-line">
                                        <span class="av-status-dot"></span>
                                        {{ $record->status_label }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- KPI CARDS --}}
                <section style="padding-top:1rem;padding-bottom:1rem;"
                    class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4 p-10">
                    @foreach ($kpis as $kpi)
                        <div class="av-kpi" style="--kpi-color: {{ $kpi['color'] }};">
                            <div class="av-kpi-top">
                                <div class="av-kpi-icon">
                                    <x-dynamic-component :component="$kpi['icon']" />
                                </div>

                                <span class="av-kpi-badge">
                                    {{ $kpi['badge'] }}
                                </span>
                            </div>

                            <div class="av-kpi-value">{{ $kpi['value'] }}</div>
                            <div class="av-kpi-label">{{ $kpi['label'] }}</div>
                        </div>
                    @endforeach
                </section>

                {{-- MAIN --}}
                <section class="grid grid-cols-1 gap-5 xl:grid-cols-12">
                    <div style="padding:;" class="xl:col-span-8">
                        <div class="av-card">
                            <div class="av-card-header">
                                <div class="av-card-icon" style="background: {{ $primaryColor }};">
                                    <x-heroicon-o-clock />
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="av-card-title">Activity Timeline</div>
                                    <div class="av-card-sub">
                                        Newest first, compact view with expandable changed data.
                                    </div>
                                </div>

                                <span class="av-count">
                                    {{ number_format($logs->count()) }} logs
                                </span>
                            </div>

                            <div class="av-scroll">
                                @forelse ($logs as $log)
                                    @php
                                        $sevColor = match ($log->severity) {
                                            'success' => '#16a34a',
                                            'warning' => '#d97706',
                                            'danger' => '#dc2626',
                                            'info' => '#0ea5e9',
                                            default => '#64748b',
                                        };

                                        $sevBg = match ($log->severity) {
                                            'success' => 'rgba(22,163,74,.10)',
                                            'warning' => 'rgba(217,119,6,.10)',
                                            'danger' => 'rgba(220,38,38,.10)',
                                            'info' => 'rgba(14,165,233,.10)',
                                            default => 'rgba(100,116,139,.09)',
                                        };

                                        $eventIcon = match ($log->event) {
                                            'created' => 'heroicon-o-plus-circle',
                                            'updated' => 'heroicon-o-pencil-square',
                                            'deleted', 'force_deleted' => 'heroicon-o-trash',
                                            'login' => 'heroicon-o-arrow-left-on-rectangle',
                                            'logout' => 'heroicon-o-arrow-right-on-rectangle',
                                            'failed_login' => 'heroicon-o-exclamation-triangle',
                                            'printed' => 'heroicon-o-printer',
                                            'exported' => 'heroicon-o-arrow-down-tray',
                                            'page_view' => 'heroicon-o-eye',
                                            default => 'heroicon-o-shield-check',
                                        };

                                        $hasData =
                                            !empty($log->old_values) ||
                                            !empty($log->new_values) ||
                                            !empty($log->metadata);
                                    @endphp

                                    <article class="av-log">
                                        <div class="flex items-start gap-3">
                                            <div class="av-log-icon" style="background: {{ $sevColor }};">
                                                <x-dynamic-component :component="$eventIcon" />
                                            </div>

                                            <div class="min-w-0 flex-1">
                                                <div
                                                    class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                                                    <div class="av-log-title">
                                                        {{ $log->event_label }}
                                                        <span class="av-log-module">—
                                                            {{ $log->module ?: 'System' }}</span>
                                                    </div>

                                                    <span class="av-severity"
                                                        style="background: {{ $sevBg }}; color: {{ $sevColor }}; border: 1px solid {{ $sevColor }}33;">
                                                        {{ $log->severity_label }}
                                                    </span>
                                                </div>

                                                @if ($log->description || $log->record_label)
                                                    <div class="av-log-desc">
                                                        {{ $log->description ?: $log->record_label }}
                                                    </div>
                                                @endif

                                                <div class="av-meta">
                                                    <span>
                                                        <x-heroicon-o-calendar-days />
                                                        {{ $log->created_at?->format('d M Y, H:i:s') }}
                                                    </span>

                                                    <span>
                                                        <x-heroicon-o-user-circle />
                                                        {{ $log->actor_label }}
                                                    </span>

                                                    @if ($log->ip_address)
                                                        <span>
                                                            <x-heroicon-o-globe-alt />
                                                            {{ $log->ip_address }}
                                                        </span>
                                                    @endif

                                                    @if ($log->route_name)
                                                        <span>
                                                            <x-heroicon-o-map-pin />
                                                            {{ $log->route_name }}
                                                        </span>
                                                    @endif
                                                </div>

                                                @if ($hasData)
                                                    <details class="av-details">
                                                        <summary>View changed data / metadata</summary>

                                                        <div class="av-json-grid">
                                                            <div>
                                                                <div class="av-json-label">Old values</div>
                                                                <pre class="av-json">{{ json_encode($log->old_values ?: [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                            </div>

                                                            <div>
                                                                <div class="av-json-label">New values</div>
                                                                <pre class="av-json" style="color:#93c5fd;">{{ json_encode($log->new_values ?: [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                            </div>

                                                            <div>
                                                                <div class="av-json-label">Metadata</div>
                                                                <pre class="av-json" style="color:#fcd34d;">{{ json_encode($log->metadata ?: [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                            </div>
                                                        </div>
                                                    </details>
                                                @endif
                                            </div>
                                        </div>
                                    </article>
                                @empty
                                    <div class="flex flex-col items-center justify-center p-10 text-center">
                                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl text-white"
                                            style="background: {{ $primaryColor }};">
                                            <x-heroicon-o-shield-check class="h-7 w-7" />
                                        </div>

                                        <div class="mt-4 text-base font-black">No audit logs yet</div>

                                        <p class="mt-1 max-w-sm text-sm text-slate-500 dark:text-slate-400">
                                            This session has no recorded activity.
                                        </p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div class="xl:col-span-4">
                        <div class="av-sticky space-y-5">

                            {{-- SESSION DETAILS --}}
                            <div style="margin-top:2rem;" class="av-card">
                                <div class="av-profile">
                                    <div class="flex items-start gap-3">
                                        <div class="av-avatar">
                                            {{ $initials ?: 'U' }}
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <div class="truncate text-sm font-black">
                                                    {{ $record->actor_label }}
                                                </div>

                                                <span class="av-status">
                                                    <span></span>
                                                    {{ $record->status_label }}
                                                </span>
                                            </div>

                                            <div
                                                class="mt-1 truncate text-xs font-semibold text-slate-500 dark:text-slate-400">
                                                {{ $record->user_email ?: 'No email captured' }}
                                            </div>

                                            <div
                                                class="mt-2 flex flex-wrap gap-2 text-[.68rem] font-bold text-slate-500 dark:text-slate-400">
                                                <span>{{ $record->logout_reason_label }}</span>
                                                <span>•</span>
                                                <span>{{ $record->duration_label }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="av-card-header">
                                    <div class="av-card-icon" style="background: {{ $accentColor }};">
                                        <x-heroicon-o-identification />
                                    </div>

                                    <div>
                                        <div class="av-card-title">Session Details</div>
                                        <div class="av-card-sub">Identity, timing and email status.</div>
                                    </div>
                                </div>

                                <div class="av-detail-grid">
                                    @foreach ($sessionDetails as [$label, $value, $icon])
                                        <div class="av-detail-row">
                                            <div class="av-detail-icon">
                                                <x-dynamic-component :component="$icon" />
                                            </div>

                                            <div class="min-w-0">
                                                <div class="av-detail-label">{{ $label }}</div>
                                                <div class="av-detail-value">{{ $value }}</div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- ACTIVITY BREAKDOWN --}}

                            <div style="padding:;margin-top:2rem;" class="av-card">
                                <div class="av-card-header">
                                    <div class="av-card-icon" style="background: #0ea5e9;">
                                        <x-heroicon-o-chart-bar-square />
                                    </div>

                                    <div>
                                        <div class="av-card-title">Activity Breakdown</div>
                                        <div class="av-card-sub">Grouped counts in this session.</div>
                                    </div>
                                </div>

                                <div class="av-breakdown-grid">
                                    @foreach ([['Created', $createdCount, '#16a34a'], ['Updated', $updatedCount, '#0ea5e9'], ['Deleted', $deletedCount, '#dc2626'], ['Printed', $printedCount, $primaryColor], ['Exported', $exportedCount, '#6366f1'], ['Risk', $riskEvents->count(), '#dc2626']] as [$label, $value, $color])
                                        <div class="av-mini-card">
                                            <div class="av-mini-value">{{ number_format($value) }}</div>
                                            <div class="av-mini-label" style="color: {{ $color }};">
                                                {{ $label }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- TECHNICAL TRACE --}}
                            <div style="padding:;margin-top:2rem" class="av-card">
                                <div class="av-card-header">
                                    <div class="av-card-icon" style="background: {{ $secondaryColor }};">
                                        <x-heroicon-o-computer-desktop />
                                    </div>

                                    <div>
                                        <div class="av-card-title">Technical Trace</div>
                                        <div class="av-card-sub">Network, route and browser footprint.</div>
                                    </div>
                                </div>

                                <div class="av-trace-stack">
                                    <div class="av-trace">
                                        <div class="av-trace-label">
                                            <x-heroicon-o-globe-alt />
                                            IP Address
                                        </div>

                                        <div class="av-trace-value font-mono">
                                            {{ $record->ip_address ?: '—' }}
                                        </div>
                                    </div>

                                    <div class="av-trace">
                                        <div class="av-trace-label">
                                            <x-heroicon-o-arrow-top-right-on-square />
                                            First URL
                                        </div>

                                        <div class="av-trace-value av-mini-scroll">
                                            {{ $record->first_url ?: '—' }}
                                        </div>
                                    </div>

                                    <div class="av-trace">
                                        <div class="av-trace-label">
                                            <x-heroicon-o-link />
                                            Last URL
                                        </div>

                                        <div class="av-trace-value av-mini-scroll">
                                            {{ $record->last_url ?: '—' }}
                                        </div>
                                    </div>

                                    <div class="av-trace">
                                        <div class="av-trace-label">
                                            <x-heroicon-o-computer-desktop />
                                            Device / Browser
                                        </div>

                                        <div class="av-trace-value av-mini-scroll">
                                            {{ $record->user_agent ?: '—' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- RISK EVENTS --}}
                            @if ($riskEvents->count() > 0)
                                <div class="av-card" style="border-color: rgba(220, 38, 38, .24);">
                                    <div class="av-card-header"
                                        style="background: linear-gradient(135deg, rgba(220,38,38,.09), transparent), var(--av-card);">
                                        <div class="av-card-icon" style="background: #dc2626;">
                                            <x-heroicon-o-shield-exclamation />
                                        </div>

                                        <div>
                                            <div class="av-card-title" style="color: #dc2626;">Risk Events</div>
                                            <div class="av-card-sub">{{ number_format($riskEvents->count()) }} flagged
                                                action(s).</div>
                                        </div>
                                    </div>

                                    <div class="av-risk-list">
                                        @foreach ($riskEvents->take(5) as $risk)
                                            <div class="av-risk-item">
                                                <span class="av-risk-dot"></span>

                                                <div class="min-w-0">
                                                    <div class="truncate text-sm font-black">
                                                        {{ $risk->event_label }}
                                                    </div>

                                                    <div class="text-xs text-slate-500 dark:text-slate-400">
                                                        {{ $risk->created_at?->format('d M, H:i') }}
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach

                                        @if ($riskEvents->count() > 5)
                                            <div
                                                class="pt-1 text-center text-xs font-bold text-slate-500 dark:text-slate-400">
                                                +{{ number_format($riskEvents->count() - 5) }} more in timeline
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-filament-panels::page>
