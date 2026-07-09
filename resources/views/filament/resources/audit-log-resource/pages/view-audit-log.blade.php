<x-filament-panels::page>
    @php
        /** @var \App\Models\AuditLog $record */
        $record = $this->getRecord();

        $primaryColor = trim(setting('theme.primary', '#014a12'));
        $secondaryColor = trim(setting('theme.secondary', '#111827'));
        $accentColor = trim(setting('theme.accent', '#f59e0b'));

        $prettyJson = function (mixed $value): string {
            if (blank($value)) {
                return '{}';
            }

            if (is_string($value)) {
                $decoded = json_decode($value, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?:
                        '{}';
                }

                return $value;
            }

            if (is_array($value) || is_object($value)) {
                return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
            }

            return (string) $value;
        };

        $severityColor = match ($record->severity) {
            'success' => '#16a34a',
            'warning' => '#d97706',
            'danger' => '#dc2626',
            'info' => '#0ea5e9',
            default => '#64748b',
        };

        $eventIcon = match ($record->event) {
            'created' => 'heroicon-o-plus-circle',
            'updated' => 'heroicon-o-pencil-square',
            'deleted', 'force_deleted' => 'heroicon-o-trash',
            'login' => 'heroicon-o-arrow-right-on-rectangle',
            'logout' => 'heroicon-o-arrow-left-on-rectangle',
            'failed_login' => 'heroicon-o-exclamation-triangle',
            'printed' => 'heroicon-o-printer',
            'exported' => 'heroicon-o-arrow-down-tray',
            'page_view' => 'heroicon-o-eye',
            'failed_request' => 'heroicon-o-x-circle',
            'manual_test' => 'heroicon-o-beaker',
            default => 'heroicon-o-shield-check',
        };

        $eventDate = $record->created_at
            ? $record->created_at->timezone('Africa/Nairobi')->format('d M Y, H:i:s') . ' EAT'
            : 'N/A';

        $actorInitials = collect(explode(' ', $record->actor_label))
            ->filter()
            ->map(fn($part) => mb_substr($part, 0, 1))
            ->take(2)
            ->implode('');

        $auditSession = $record->auditSession;

        $oldValues = $record->old_values_display ?? $prettyJson($record->old_values);
        $newValues = $record->new_values_display ?? $prettyJson($record->new_values);
        $metadata = $record->metadata_display ?? $prettyJson($record->metadata);

        $hasChangedData = filled($record->old_values) || filled($record->new_values) || filled($record->metadata);

        $kpis = [
            [
                'label' => 'Event Type',
                'value' => $record->event_label,
                'badge' => 'Action',
                'icon' => $eventIcon,
                'color' => $severityColor,
            ],
            [
                'label' => 'Module',
                'value' => $record->module ?: 'System',
                'badge' => 'Context',
                'icon' => 'heroicon-o-squares-2x2',
                'color' => $primaryColor,
            ],
            [
                'label' => 'Severity',
                'value' => $record->severity_label,
                'badge' => 'Risk',
                'icon' => 'heroicon-o-shield-exclamation',
                'color' => $severityColor,
            ],
            [
                'label' => 'HTTP Status',
                'value' => $record->response_status ?: 'N/A',
                'badge' => $record->http_method ?: 'Trace',
                'icon' => 'heroicon-o-command-line',
                'color' => $accentColor,
            ],
        ];

        $eventDetails = [
            ['Event', $record->event_label, 'heroicon-o-bolt'],
            ['Module', $record->module ?: 'System', 'heroicon-o-squares-2x2'],
            ['Severity', $record->severity_label, 'heroicon-o-shield-exclamation'],
            ['Created At', $eventDate, 'heroicon-o-calendar-days'],
            ['Record', $record->record_display, 'heroicon-o-document-text'],
            ['Model', $record->auditable_type ? class_basename($record->auditable_type) : 'N/A', 'heroicon-o-cube'],
        ];

        $userDetails = [
            ['User', $record->actor_label, 'heroicon-o-user-circle'],
            ['Email', $record->user_email ?: 'N/A', 'heroicon-o-envelope'],
            ['Guard', $record->guard ?: 'web', 'heroicon-o-shield-check'],
            [
                'Session',
                $record->audit_session_uuid ?: ($auditSession?->uuid ?: ($record->audit_session_id ?: 'N/A')),
                'heroicon-o-finger-print',
            ],
        ];

        $requestDetails = [
            ['IP Address', $record->ip_address ?: 'N/A', 'heroicon-o-globe-alt'],
            ['HTTP Method', $record->http_method ?: 'N/A', 'heroicon-o-command-line'],
            ['Response', $record->response_status ?: 'N/A', 'heroicon-o-signal'],
            ['Route', $record->route_name ?: 'N/A', 'heroicon-o-map-pin'],
            ['URL', $record->url ?: 'N/A', 'heroicon-o-link'],
            ['Device / Browser', $record->user_agent ?: 'N/A', 'heroicon-o-computer-desktop'],
        ];

        $timelineItems = [
            [
                'title' => $record->event_label . ' — ' . ($record->module ?: 'System'),
                'description' => $record->description ?: 'No description captured for this audit event.',
                'icon' => $eventIcon,
                'color' => $severityColor,
                'meta' => [$eventDate, $record->actor_label, $record->ip_address ?: null],
            ],
            [
                'title' => 'Record Context',
                'description' => $record->record_display ?: 'No record reference captured.',
                'icon' => 'heroicon-o-document-text',
                'color' => $primaryColor,
                'meta' => [
                    $record->auditable_type ? class_basename($record->auditable_type) : 'No model',
                    $record->auditable_id ? 'ID: ' . $record->auditable_id : null,
                ],
            ],
            [
                'title' => 'Request Trace',
                'description' => $record->url ?: 'No URL captured.',
                'icon' => 'heroicon-o-globe-alt',
                'color' => $secondaryColor,
                'meta' => [
                    $record->route_name ?: null,
                    $record->http_method ?: null,
                    $record->response_status ? 'HTTP ' . $record->response_status : null,
                ],
            ],
        ];
    @endphp

    <style>
        .av-wrap {
            width: 100%;
            max-width: 1280px;
            margin: 0 auto;
        }

        .audit-view {
            --av-primary: {{ $primaryColor }};
            --av-secondary: {{ $secondaryColor }};
            --av-accent: {{ $accentColor }};
            --av-severity: {{ $severityColor }};
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
            min-height: auto;
            border-radius: 2rem;
            padding: 1.15rem;
            background:
                radial-gradient(circle at 0% 0%, color-mix(in srgb, var(--av-primary) 14%, transparent), transparent 34%),
                radial-gradient(circle at 100% 16%, color-mix(in srgb, var(--av-accent) 10%, transparent), transparent 30%),
                var(--av-bg);
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
            background: var(--av-severity);
            box-shadow: 0 0 0 5px color-mix(in srgb, var(--av-severity) 18%, transparent);
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
            word-break: break-word;
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
            word-break: break-word;
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
            min-width: 0;
            word-break: break-word;
        }

        .av-meta svg {
            width: .76rem;
            height: .76rem;
            flex-shrink: 0;
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
            max-height: 260px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-break: break-word;
            border-radius: .75rem;
            background: #020617;
            padding: .7rem .78rem;
            color: #86efac;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: .64rem;
            line-height: 1.55;
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
            color: var(--av-severity);
            background: color-mix(in srgb, var(--av-severity) 10%, transparent);
            border: 1px solid color-mix(in srgb, var(--av-severity) 24%, transparent);
        }

        .av-status span {
            width: .38rem;
            height: .38rem;
            border-radius: 999px;
            background: var(--av-severity);
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--av-severity) 11%, transparent);
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

                <section class="av-hero">
                    <div class="av-hero-inner">
                        <div class="grid grid-cols-1 gap-5 xl:grid-cols-12 xl:items-center">
                            <div class="xl:col-span-8">
                                <div class="flex flex-wrap gap-2">
                                    <span class="av-chip">
                                        <x-heroicon-o-clipboard-document-list />
                                        Audit Log
                                    </span>

                                    <span class="av-chip">
                                        <x-heroicon-o-squares-2x2 />
                                        {{ $record->module ?: 'System' }}
                                    </span>

                                    <span class="av-chip">
                                        <x-heroicon-o-user-circle />
                                        {{ $record->actor_label }}
                                    </span>
                                </div>

                                <h5
                                    class="mt-4 text-xl font-black leading-tight tracking-tight sm:text-2xl lg:text-3xl">
                                    {{ $record->event_label }} Audit Event
                                </h5>

                                <p class="mt-2 max-w-4xl text-sm leading-6 text-white/80">
                                    A complete forensic review of this audit event, including the user involved,
                                    record context, changed values, metadata, IP address, route, browser details,
                                    and system trace data.
                                </p>

                                <div class="mt-4 flex flex-wrap gap-3 text-xs font-bold text-white/72">
                                    <span class="inline-flex items-center gap-1.5">
                                        <x-heroicon-o-calendar-days class="h-4 w-4" />
                                        {{ $eventDate }}
                                    </span>

                                    @if ($record->ip_address)
                                        <span class="inline-flex items-center gap-1.5">
                                            <x-heroicon-o-globe-alt class="h-4 w-4" />
                                            {{ $record->ip_address }}
                                        </span>
                                    @endif

                                    @if ($record->route_name)
                                        <span class="inline-flex items-center gap-1.5">
                                            <x-heroicon-o-map-pin class="h-4 w-4" />
                                            {{ $record->route_name }}
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
                                                Audit Log Reference
                                            </div>

                                            <div class="av-uuid-value font-mono">
                                                {{ $record->uuid ?: 'AUDIT-LOG-' . $record->id }}
                                            </div>
                                        </div>

                                        <div
                                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white/15 text-white ring-1 ring-white/20">
                                            <x-dynamic-component :component="$eventIcon" class="h-5 w-5" />
                                        </div>
                                    </div>

                                    <div class="av-status-line">
                                        <span class="av-status-dot"></span>
                                        {{ $record->severity_label }} / {{ $record->event_label }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="grid grid-cols-1 gap-3 py-4 sm:grid-cols-2 xl:grid-cols-4">
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

                <section class="grid grid-cols-1 gap-5 xl:grid-cols-12">
                    <div class="space-y-5 xl:col-span-8">
                        <div style="padding:;margin-top:2rem;" class="av-card">
                            <div class="av-card-header">
                                <div class="av-card-icon" style="background: {{ $primaryColor }};">
                                    <x-heroicon-o-clock />
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="av-card-title">Audit Event Timeline</div>
                                    <div class="av-card-sub">
                                        A compact explanation of what happened during this audited action.
                                    </div>
                                </div>

                                <span class="av-count">
                                    {{ count($timelineItems) }} items
                                </span>
                            </div>

                            <div class="av-scroll">
                                @foreach ($timelineItems as $item)
                                    <article class="av-log">
                                        <div class="flex items-start gap-3">
                                            <div class="av-log-icon" style="background: {{ $item['color'] }};">
                                                <x-dynamic-component :component="$item['icon']" />
                                            </div>

                                            <div class="min-w-0 flex-1">
                                                <div
                                                    class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                                                    <div class="av-log-title">
                                                        {{ $item['title'] }}
                                                    </div>

                                                    <span class="av-severity"
                                                        style="background: color-mix(in srgb, {{ $item['color'] }} 10%, transparent); color: {{ $item['color'] }}; border: 1px solid color-mix(in srgb, {{ $item['color'] }} 28%, transparent);">
                                                        {{ $record->severity_label }}
                                                    </span>
                                                </div>

                                                <div class="av-log-desc">
                                                    {{ $item['description'] }}
                                                </div>

                                                <div class="av-meta">
                                                    @foreach (array_filter($item['meta']) as $meta)
                                                        <span>
                                                            <x-heroicon-o-check-circle />
                                                            {{ $meta }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </div>

                        <div style="padding:;margin-top:2rem;" class="av-card">
                            <div class="av-card-header">
                                <div class="av-card-icon" style="background: {{ $severityColor }};">
                                    <x-heroicon-o-document-magnifying-glass />
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="av-card-title">Changed Data / Metadata</div>
                                    <div class="av-card-sub">
                                        Old values, new values and metadata captured for this audit event.
                                    </div>
                                </div>

                                <span class="av-count">
                                    {{ $hasChangedData ? 'Captured' : 'Empty' }}
                                </span>
                            </div>

                            <div class="av-json-grid">
                                <div>
                                    <div class="av-json-label">Old Values</div>
                                    <pre class="av-json">{{ $oldValues }}</pre>
                                </div>

                                <div>
                                    <div class="av-json-label">New Values</div>
                                    <pre class="av-json" style="color:#93c5fd;">{{ $newValues }}</pre>
                                </div>

                                <div>
                                    <div class="av-json-label">Metadata</div>
                                    <pre class="av-json" style="color:#fcd34d;">{{ $metadata }}</pre>
                                </div>
                            </div>
                        </div>

                        <div style="padding:;margin-top:2rem;" class="av-card">
                            <div class="av-card-header">
                                <div class="av-card-icon" style="background: {{ $accentColor }};">
                                    <x-heroicon-o-information-circle />
                                </div>

                                <div>
                                    <div class="av-card-title">Audit Event Details</div>
                                    <div class="av-card-sub">
                                        Core event information, module context, record reference and timestamp.
                                    </div>
                                </div>
                            </div>

                            <div class="av-detail-grid">
                                @foreach ($eventDetails as [$label, $value, $icon])
                                    <div class="av-detail-row">
                                        <div class="av-detail-icon">
                                            <x-dynamic-component :component="$icon" />
                                        </div>

                                        <div class="min-w-0">
                                            <div class="av-detail-label">{{ $label }}</div>
                                            <div class="av-detail-value">{{ $value ?: 'N/A' }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="xl:col-span-4">
                        <div class="av-sticky space-y-5">

                            <div style="padding:;margin-top:2rem;" class="av-card">
                                <div class="av-profile">
                                    <div class="flex items-start gap-3">
                                        <div class="av-avatar">
                                            {{ $actorInitials ?: 'S' }}
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <div class="truncate text-sm font-black">
                                                    {{ $record->actor_label }}
                                                </div>

                                                <span class="av-status">
                                                    <span></span>
                                                    {{ $record->severity_label }}
                                                </span>
                                            </div>

                                            <div
                                                class="mt-1 truncate text-xs font-semibold text-slate-500 dark:text-slate-400">
                                                {{ $record->user_email ?: 'No email captured' }}
                                            </div>

                                            <div
                                                class="mt-2 flex flex-wrap gap-2 text-[.68rem] font-bold text-slate-500 dark:text-slate-400">
                                                <span>{{ $record->module ?: 'System' }}</span>
                                                <span>•</span>
                                                <span>{{ $record->event_label }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="av-card-header">
                                    <div class="av-card-icon" style="background: {{ $accentColor }};">
                                        <x-heroicon-o-user-circle />
                                    </div>

                                    <div>
                                        <div class="av-card-title">User Context</div>
                                        <div class="av-card-sub">Account identity connected to this audit event.</div>
                                    </div>
                                </div>

                                <div class="av-detail-grid">
                                    @foreach ($userDetails as [$label, $value, $icon])
                                        <div class="av-detail-row">
                                            <div class="av-detail-icon">
                                                <x-dynamic-component :component="$icon" />
                                            </div>

                                            <div class="min-w-0">
                                                <div class="av-detail-label">{{ $label }}</div>
                                                <div class="av-detail-value">{{ $value ?: 'N/A' }}</div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div style="padding:;margin-top:2rem;" class="av-card">
                                <div class="av-card-header">
                                    <div class="av-card-icon" style="background: {{ $secondaryColor }};">
                                        <x-heroicon-o-globe-alt />
                                    </div>

                                    <div>
                                        <div class="av-card-title">Technical Trace</div>
                                        <div class="av-card-sub">Network, route, URL and browser footprint.</div>
                                    </div>
                                </div>

                                <div class="av-trace-stack">
                                    @foreach ($requestDetails as [$label, $value, $icon])
                                        <div class="av-trace">
                                            <div class="av-trace-label">
                                                <x-dynamic-component :component="$icon" />
                                                {{ $label }}
                                            </div>

                                            <div class="av-trace-value av-mini-scroll">
                                                {{ $value ?: 'N/A' }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            @if ($auditSession)
                                <div style="padding:;margin-top:2rem;" class="av-card">
                                    <div class="av-card-header">
                                        <div class="av-card-icon" style="background: {{ $primaryColor }};">
                                            <x-heroicon-o-finger-print />
                                        </div>

                                        <div>
                                            <div class="av-card-title">Linked Audit Session</div>
                                            <div class="av-card-sub">Session that produced this audit event.</div>
                                        </div>
                                    </div>

                                    <div class="av-detail-grid">
                                        <div class="av-detail-row">
                                            <div class="av-detail-icon">
                                                <x-heroicon-o-key />
                                            </div>

                                            <div>
                                                <div class="av-detail-label">Session UUID</div>
                                                <div class="av-detail-value">{{ $auditSession->uuid ?: 'N/A' }}</div>
                                            </div>
                                        </div>

                                        <div class="av-detail-row">
                                            <div class="av-detail-icon">
                                                <x-heroicon-o-clock />
                                            </div>

                                            <div>
                                                <div class="av-detail-label">Duration</div>
                                                <div class="av-detail-value">{{ $auditSession->duration_label }}</div>
                                            </div>
                                        </div>

                                        <div class="av-detail-row">
                                            <div class="av-detail-icon">
                                                <x-heroicon-o-shield-check />
                                            </div>

                                            <div>
                                                <div class="av-detail-label">Status</div>
                                                <div class="av-detail-value">{{ $auditSession->status_label }}</div>
                                            </div>
                                        </div>
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
