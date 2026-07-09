<x-filament-panels::page>
    @php
        $normalizeHex = function (?string $color, string $fallback): string {
            $color = trim((string) $color);

            if ($color === '') {
                return $fallback;
            }

            if (!str_starts_with($color, '#')) {
                $color = '#' . $color;
            }

            return preg_match('/^#[0-9A-Fa-f]{6}$/', $color) ? $color : $fallback;
        };

        $hexToRgb = function (string $hex): string {
            $hex = ltrim($hex, '#');

            return implode(',', [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))]);
        };

        $formatDateTime = function ($date, string $format = 'd M Y, H:i:s'): string {
            if (blank($date)) {
                return 'N/A';
            }

            try {
                return \Illuminate\Support\Carbon::parse($date)->timezone('Africa/Nairobi')->format($format);
            } catch (\Throwable $e) {
                return (string) $date;
            }
        };

        $plural = fn(string $word, int|float $count): string => \Illuminate\Support\Str::plural($word, (int) $count);

        $primaryColor = $normalizeHex(setting('theme.primary', '#008f00'), '#008f00');
        $secondaryColor = $normalizeHex(setting('theme.secondary', '#111827'), '#111827');
        $accentColor = $normalizeHex(setting('theme.accent', '#f0b429'), '#f0b429');

        $primaryRgb = $hexToRgb($primaryColor);
        $accentRgb = $hexToRgb($accentColor);

        $today = now('Africa/Nairobi')->format('l, d M Y');

        $stats = $stats ?? [];

        $latestLogs = collect($latestLogs ?? []);
        $activeSessions = collect($activeSessions ?? []);
        $topUsers = collect($topUsers ?? []);

        $activeSessionCount = (int) ($stats['activeSessions'] ?? $activeSessions->count());
        $closedToday = (int) ($stats['closedToday'] ?? 0);
        $failedLogins = (int) ($stats['failedLogins'] ?? 0);
        $deletedRecords = (int) ($stats['deletedRecords'] ?? 0);
        $printedReports = (int) ($stats['printedReports'] ?? 0);
        $exportedReports = (int) ($stats['exportedReports'] ?? 0);
        $totalToday = (int) ($stats['totalToday'] ?? $latestLogs->count());
        $highRiskToday = (int) ($stats['highRiskToday'] ?? $failedLogins + $deletedRecords);

        $summaryStats = [
            [
                'label' => 'Total Events',
                'value' => $totalToday,
                'icon' => 'heroicon-o-circle-stack',
                'color' => $primaryColor,
                'rgb' => $primaryRgb,
                'tag' => 'Today',
            ],
            [
                'label' => 'Risk Events',
                'value' => $highRiskToday,
                'icon' => 'heroicon-o-shield-exclamation',
                'color' => '#ef4444',
                'rgb' => '239,68,68',
                'tag' => 'Risk',
            ],
            [
                'label' => 'Active Sessions',
                'value' => $activeSessionCount,
                'icon' => 'heroicon-o-finger-print',
                'color' => '#22c55e',
                'rgb' => '34,197,94',
                'tag' => 'Live',
            ],
            [
                'label' => 'Closed Today',
                'value' => $closedToday,
                'icon' => 'heroicon-o-lock-closed',
                'color' => '#3b82f6',
                'rgb' => '59,130,246',
                'tag' => 'Closed',
            ],
        ];

        $kpiCards = [
            [
                'label' => 'Failed Logins',
                'value' => $failedLogins,
                'icon' => 'heroicon-o-exclamation-triangle',
                'color' => '#f59e0b',
                'gradient' => 'linear-gradient(135deg,#f59e0b,#f97316)',
                'glow' => '245,158,11',
                'description' => 'Login attempts that failed today.',
                'bar_pct' => $failedLogins > 0 ? min(100, max(8, $failedLogins * 12)) : 4,
            ],
            [
                'label' => 'Deleted Records',
                'value' => $deletedRecords,
                'icon' => 'heroicon-o-trash',
                'color' => '#ef4444',
                'gradient' => 'linear-gradient(135deg,#ef4444,#be123c)',
                'glow' => '239,68,68',
                'description' => 'Deleted and force-deleted records.',
                'bar_pct' => $deletedRecords > 0 ? min(100, max(8, $deletedRecords * 12)) : 4,
            ],
            [
                'label' => 'Printed Reports',
                'value' => $printedReports,
                'icon' => 'heroicon-o-printer',
                'color' => $primaryColor,
                'gradient' => "linear-gradient(135deg,{$primaryColor},#06b6d4)",
                'glow' => $primaryRgb,
                'description' => 'PDF reports and print actions today.',
                'bar_pct' => $printedReports > 0 ? min(100, max(8, $printedReports * 12)) : 4,
            ],
            [
                'label' => 'Exports',
                'value' => $exportedReports,
                'icon' => 'heroicon-o-arrow-down-tray',
                'color' => '#6366f1',
                'gradient' => 'linear-gradient(135deg,#6366f1,#8b5cf6)',
                'glow' => '99,102,241',
                'description' => 'Excel and data export activities today.',
                'bar_pct' => $exportedReports > 0 ? min(100, max(8, $exportedReports * 12)) : 4,
            ],
        ];

        $avatarGradients = [
            'linear-gradient(135deg,#00a362,#008f00)',
            'linear-gradient(135deg,#6366f1,#8b5cf6)',
            'linear-gradient(135deg,#f59e0b,#f97316)',
            'linear-gradient(135deg,#0ea5e9,#3b82f6)',
            'linear-gradient(135deg,#ef4444,#be123c)',
            'linear-gradient(135deg,#22c55e,#16a34a)',
        ];

        $rankGradients = [
            "linear-gradient(135deg,{$accentColor},#f97316)",
            "linear-gradient(135deg,{$primaryColor},#00a362)",
            'linear-gradient(135deg,#6366f1,#8b5cf6)',
            'linear-gradient(135deg,#0ea5e9,#3b82f6)',
            'linear-gradient(135deg,#94a3b8,#64748b)',
        ];

        $eventIcons = [
            'created' => [
                'icon' => 'heroicon-o-plus-circle',
                'grad' => "linear-gradient(135deg,{$primaryColor},#00a362)",
            ],
            'updated' => [
                'icon' => 'heroicon-o-pencil-square',
                'grad' => 'linear-gradient(135deg,#3b82f6,#0ea5e9)',
            ],
            'deleted' => [
                'icon' => 'heroicon-o-trash',
                'grad' => 'linear-gradient(135deg,#ef4444,#be123c)',
            ],
            'force_deleted' => [
                'icon' => 'heroicon-o-trash',
                'grad' => 'linear-gradient(135deg,#ef4444,#be123c)',
            ],
            'login' => [
                'icon' => 'heroicon-o-arrow-right-on-rectangle',
                'grad' => "linear-gradient(135deg,{$primaryColor},#00a362)",
            ],
            'logout' => [
                'icon' => 'heroicon-o-arrow-left-on-rectangle',
                'grad' => 'linear-gradient(135deg,#94a3b8,#64748b)',
            ],
            'failed_login' => [
                'icon' => 'heroicon-o-exclamation-triangle',
                'grad' => 'linear-gradient(135deg,#f59e0b,#f97316)',
            ],
            'printed' => [
                'icon' => 'heroicon-o-printer',
                'grad' => "linear-gradient(135deg,{$primaryColor},#06b6d4)",
            ],
            'exported' => [
                'icon' => 'heroicon-o-document-arrow-down',
                'grad' => 'linear-gradient(135deg,#6366f1,#8b5cf6)',
            ],
            'page_view' => [
                'icon' => 'heroicon-o-eye',
                'grad' => 'linear-gradient(135deg,#0ea5e9,#3b82f6)',
            ],
        ];

        $severityStyles = [
            'danger' => [
                'bg' => 'rgba(239,68,68,.12)',
                'color' => '#ef4444',
                'border' => 'rgba(239,68,68,.26)',
            ],
            'warning' => [
                'bg' => 'rgba(245,158,11,.13)',
                'color' => '#d97706',
                'border' => 'rgba(245,158,11,.28)',
            ],
            'success' => [
                'bg' => "rgba({$primaryRgb},.12)",
                'color' => $primaryColor,
                'border' => "rgba({$primaryRgb},.26)",
            ],
            'info' => [
                'bg' => 'rgba(59,130,246,.12)',
                'color' => '#2563eb',
                'border' => 'rgba(59,130,246,.26)',
            ],
            'default' => [
                'bg' => 'rgba(100,116,139,.10)',
                'color' => '#64748b',
                'border' => 'rgba(100,116,139,.22)',
            ],
        ];
    @endphp

    <style>
        .ac-wrap {
            --ac-primary: {{ $primaryColor }};
            --ac-primary-rgb: {{ $primaryRgb }};
            --ac-secondary: {{ $secondaryColor }};
            --ac-accent: {{ $accentColor }};
            --ac-accent-rgb: {{ $accentRgb }};

            --ac-page: #f5f7fb;
            --ac-page-soft: #eef3f8;
            --ac-card: rgba(255, 255, 255, .86);
            --ac-card-solid: #ffffff;
            --ac-card-soft: #f8fafc;
            --ac-card-muted: #f1f5f9;
            --ac-border: rgba(15, 23, 42, .08);
            --ac-border-strong: rgba(15, 23, 42, .12);
            --ac-text: #0f172a;
            --ac-heading: #020617;
            --ac-muted: #64748b;
            --ac-muted-2: #94a3b8;
            --ac-shadow: 0 18px 55px rgba(15, 23, 42, .08);
            --ac-shadow-strong: 0 24px 80px rgba(15, 23, 42, .14);
            --ac-ring: rgba(var(--ac-primary-rgb), .18);

            --ac-radius-xl: 28px;
            --ac-radius-lg: 22px;
            --ac-radius-md: 16px;
            --ac-radius-sm: 12px;

            --ac-font-body: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            --ac-font-head: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;

            position: relative;
            isolation: isolate;
            color: var(--ac-text);
            font-family: var(--ac-font-body);
            font-size: 14px;
            line-height: 1.55;

            width: 100%;
            max-width: 1280px;
            margin-left: auto;
            margin-right: auto;
        }

        .dark .ac-wrap,
        html.dark .ac-wrap {
            --ac-page: #070b14;
            --ac-page-soft: #0b1220;
            --ac-card: rgba(15, 23, 42, .78);
            --ac-card-solid: #0f172a;
            --ac-card-soft: #111827;
            --ac-card-muted: #1e293b;
            --ac-border: rgba(255, 255, 255, .08);
            --ac-border-strong: rgba(255, 255, 255, .14);
            --ac-text: #e5eefb;
            --ac-heading: #ffffff;
            --ac-muted: #94a3b8;
            --ac-muted-2: #64748b;
            --ac-shadow: 0 18px 55px rgba(0, 0, 0, .28);
            --ac-shadow-strong: 0 24px 90px rgba(0, 0, 0, .42);
            --ac-ring: rgba(var(--ac-primary-rgb), .24);
        }

        .ac-wrap *,
        .ac-wrap *::before,
        .ac-wrap *::after {
            box-sizing: border-box;
        }

        .ac-bg {
            position: absolute;
            inset: 0;
            z-index: -1;
            pointer-events: none;
            /*
background:
                radial-gradient(circle at 8% 6%, rgba(var(--ac-primary-rgb), .14), transparent 28%),
                radial-gradient(circle at 90% 10%, rgba(var(--ac-accent-rgb), .14), transparent 26%),
                linear-gradient(135deg, var(--ac-page), var(--ac-page-soft));*/
            border-radius: 34px;
        }

        .dark .ac-bg,
        html.dark .ac-bg {
            /*background:
                radial-gradient(circle at 8% 6%, rgba(var(--ac-primary-rgb), .18), transparent 30%),
                radial-gradient(circle at 90% 10%, rgba(var(--ac-accent-rgb), .14), transparent 28%),
                linear-gradient(135deg, var(--ac-page), var(--ac-page-soft));*/
        }

        .ac-icon {
            width: 1.15rem;
            height: 1.15rem;
            stroke-width: 2;
        }

        .ac-shell {
            padding: 2px 0 34px;
        }

        .ac-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
            padding: 14px 16px;
            margin-bottom: 16px;
            border: 1px solid var(--ac-border);
            border-radius: var(--ac-radius-lg);
            background:
                linear-gradient(135deg, rgba(255, 255, 255, .72), rgba(255, 255, 255, .48)),
                var(--ac-card);
            box-shadow: var(--ac-shadow);
            backdrop-filter: blur(18px);
        }

        .dark .ac-topbar,
        html.dark .ac-topbar {
            background:
                linear-gradient(135deg, rgba(15, 23, 42, .82), rgba(15, 23, 42, .54)),
                var(--ac-card);
        }

        .ac-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .ac-brand-icon {
            width: 44px;
            height: 44px;
            display: grid;
            place-items: center;
            border-radius: 16px;
            color: #fff;
            background:
                radial-gradient(circle at 32% 24%, rgba(255, 255, 255, .36), transparent 24%),
                linear-gradient(135deg, var(--ac-primary), #00a362);
            box-shadow: 0 15px 32px rgba(var(--ac-primary-rgb), .28);
            flex-shrink: 0;
        }

        .ac-brand-title {
            display: block;
            font-family: var(--ac-font-head);
            font-size: 15px;
            font-weight: 800;
            line-height: 1.1;
            color: var(--ac-heading);
            letter-spacing: -.02em;
        }

        .ac-brand-subtitle {
            display: block;
            margin-top: 3px;
            font-size: 11.5px;
            color: var(--ac-muted);
            font-weight: 500;
        }

        .ac-chip-row {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
        }

        .ac-chip {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            min-height: 34px;
            padding: 7px 12px;
            border-radius: 999px;
            background: var(--ac-card-solid);
            border: 1px solid var(--ac-border);
            color: var(--ac-muted);
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
            box-shadow: 0 8px 20px rgba(15, 23, 42, .04);
        }

        .dark .ac-chip,
        html.dark .ac-chip {
            background: rgba(15, 23, 42, .7);
            box-shadow: none;
        }

        .ac-chip-primary {
            color: var(--ac-primary);
            border-color: rgba(var(--ac-primary-rgb), .25);
            background: rgba(var(--ac-primary-rgb), .08);
        }

        .ac-chip-date {
            color: var(--ac-heading);
            background: var(--ac-card-muted);
        }

        .ac-live-dot {
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: var(--ac-primary);
            box-shadow: 0 0 0 4px rgba(var(--ac-primary-rgb), .14);
            animation: acPulse 1.8s infinite;
            flex-shrink: 0;
        }

        @keyframes acPulse {

            0%,
            100% {
                box-shadow: 0 0 0 4px rgba(var(--ac-primary-rgb), .14);
            }

            50% {
                box-shadow: 0 0 0 8px rgba(var(--ac-primary-rgb), 0);
            }
        }

        .ac-hero {
            position: relative;
            overflow: hidden;
            margin-bottom: 16px;
            border-radius: var(--ac-radius-xl);
            border: 1px solid var(--ac-border);
            background:
                radial-gradient(circle at 0% 0%, rgba(var(--ac-primary-rgb), .18), transparent 32%),
                radial-gradient(circle at 100% 0%, rgba(var(--ac-accent-rgb), .16), transparent 30%),
                linear-gradient(135deg, rgba(255, 255, 255, .92), rgba(248, 250, 252, .72));
            box-shadow: var(--ac-shadow-strong);
            backdrop-filter: blur(18px);
        }

        .dark .ac-hero,
        html.dark .ac-hero {
            background:
                radial-gradient(circle at 0% 0%, rgba(var(--ac-primary-rgb), .22), transparent 34%),
                radial-gradient(circle at 100% 0%, rgba(var(--ac-accent-rgb), .16), transparent 30%),
                linear-gradient(135deg, rgba(15, 23, 42, .92), rgba(17, 24, 39, .72));
        }

        .ac-hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(var(--ac-border) 1px, transparent 1px),
                linear-gradient(90deg, var(--ac-border) 1px, transparent 1px);
            background-size: 42px 42px;
            mask-image: radial-gradient(ellipse at 38% 18%, black 0%, transparent 68%);
            opacity: .7;
            pointer-events: none;
        }

        .ac-hero-inner {
            position: relative;
            z-index: 1;
            padding: 28px;
        }

        .ac-hero-top {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 22px;
            align-items: start;
            margin-bottom: 26px;
        }

        .ac-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
            padding: 7px 11px;
            border-radius: 999px;
            color: var(--ac-primary);
            background: rgba(var(--ac-primary-rgb), .09);
            border: 1px solid rgba(var(--ac-primary-rgb), .2);
            font-size: 10.5px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .ac-hero-title {
            max-width: 780px;
            font-family: var(--ac-font-head);
            font-size: clamp(26px, 3.6vw, 44px);
            line-height: 1.04;
            font-weight: 800;
            letter-spacing: -.055em;
            color: var(--ac-heading);
        }

        .ac-hero-title span {
            background: linear-gradient(90deg, var(--ac-primary), var(--ac-accent));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .ac-hero-desc {
            max-width: 820px;
            margin: 13px 0 0;
            color: var(--ac-muted);
            font-size: 13.5px;
            line-height: 1.75;
            font-weight: 500;
        }

        .ac-status-stack {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 8px;
            max-width: 430px;
        }

        .ac-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 10.5px;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
            white-space: nowrap;
            border: 1px solid var(--ac-border);
            background: var(--ac-card);
            color: var(--ac-muted);
        }

        .ac-status-success {
            color: var(--ac-primary);
            background: rgba(var(--ac-primary-rgb), .09);
            border-color: rgba(var(--ac-primary-rgb), .22);
        }

        .ac-status-warning {
            color: #d97706;
            background: rgba(245, 158, 11, .12);
            border-color: rgba(245, 158, 11, .25);
        }

        .ac-status-danger {
            color: #ef4444;
            background: rgba(239, 68, 68, .11);
            border-color: rgba(239, 68, 68, .25);
        }

        .ac-status-info {
            color: #2563eb;
            background: rgba(59, 130, 246, .11);
            border-color: rgba(59, 130, 246, .25);
        }

        .dark .ac-status-info,
        html.dark .ac-status-info {
            color: #60a5fa;
        }

        .ac-stat-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        /*
        .ac-stat-tile {
            position: relative;
            overflow: hidden;
            min-height: 150px;
            padding: 16px;
            border-radius: var(--ac-radius-lg);
            background:
                linear-gradient(145deg, rgba(255, 255, 255, .88), rgba(255, 255, 255, .58)),
                var(--ac-card);
            border: 1px solid var(--ac-border);
            box-shadow: 0 12px 32px rgba(15, 23, 42, .055);
            transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease;
        }*/

        .dark .ac-stat-tile,
        html.dark .ac-stat-tile {
            background:
                linear-gradient(145deg, rgba(30, 41, 59, .78), rgba(15, 23, 42, .54)),
                var(--ac-card);
            box-shadow: none;
        }

        .ac-stat-tile:hover {
            transform: translateY(-3px);
            border-color: rgba(var(--ac-primary-rgb), .22);
            box-shadow: 0 18px 45px rgba(15, 23, 42, .10);
        }

        .dark .ac-stat-tile:hover,
        html.dark .ac-stat-tile:hover {
            box-shadow: 0 18px 45px rgba(0, 0, 0, .26);
        }

        .ac-stat-tile::after {
            content: "";
            position: absolute;
            right: -42px;
            bottom: -52px;
            width: 140px;
            height: 140px;
            border-radius: 999px;
            background: currentColor;
            opacity: .07;
        }

        .ac-stat-icon {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            margin-bottom: 14px;
            border-radius: 15px;
        }

        .ac-stat-value {
            font-family: var(--ac-font-head);
            font-size: 34px;
            line-height: 1;
            font-weight: 800;
            letter-spacing: -.045em;
        }

        .ac-stat-label {
            margin-top: 7px;
            color: var(--ac-muted);
            font-size: 10.5px;
            font-weight: 800;
            letter-spacing: .09em;
            text-transform: uppercase;
        }

        .ac-stat-tag {
            position: absolute;
            top: 14px;
            right: 14px;
            padding: 4px 9px;
            border-radius: 999px;
            font-size: 9px;
            font-weight: 900;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .ac-kpi-row {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 16px;
        }

        /*.ac-kpi-card {
            position: relative;
            overflow: hidden;
            min-height: 218px;
            padding: 20px;
            border-radius: var(--ac-radius-lg);
            background:
                radial-gradient(circle at top right, rgba(var(--ac-primary-rgb), .07), transparent 34%),
                var(--ac-card);
            border: 1px solid var(--ac-border);
            box-shadow: var(--ac-shadow);
            backdrop-filter: blur(16px);
            transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease;
        }
            */

        .ac-stat-tile {
            position: relative;
            overflow: hidden;
            min-height: 112px;
            /* reduced from 150px */
            padding: 12px 14px;
            /* reduced from 16px */
            border-radius: var(--ac-radius-lg);
            background:
                linear-gradient(145deg, rgba(255, 255, 255, .88), rgba(255, 255, 255, .58)),
                var(--ac-card);
            border: 1px solid var(--ac-border);
            box-shadow: 0 12px 32px rgba(15, 23, 42, .055);
            transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease;
        }

        .ac-kpi-card {
            position: relative;
            overflow: hidden;
            min-height: 158px;
            /* reduced from 218px */
            padding: 14px 16px;
            /* reduced from 20px */
            border-radius: var(--ac-radius-lg);
            background:
                radial-gradient(circle at top right, rgba(var(--ac-primary-rgb), .07), transparent 34%),
                var(--ac-card);
            border: 1px solid var(--ac-border);
            box-shadow: var(--ac-shadow);
            backdrop-filter: blur(16px);
            transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease;
        }

        .ac-kpi-card:hover {
            transform: translateY(-3px);
            border-color: var(--ac-border-strong);
            box-shadow: var(--ac-shadow-strong);
        }

        .ac-kpi-accent {
            position: absolute;
            inset: 0 0 auto 0;
            height: 4px;
        }

        .ac-kpi-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 17px;
        }

        .ac-kpi-icon {
            width: 48px;
            height: 48px;
            display: grid;
            place-items: center;
            border-radius: 17px;
            color: #fff;
            flex-shrink: 0;
        }

        .ac-mini-label {
            padding: 5px 10px;
            border-radius: 999px;
            background: var(--ac-card-muted);
            border: 1px solid var(--ac-border);
            color: var(--ac-muted);
            font-size: 9.5px;
            font-weight: 900;
            letter-spacing: .09em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .ac-kpi-value {
            font-family: var(--ac-font-head);
            font-size: 38px;
            line-height: 1;
            font-weight: 800;
            letter-spacing: -.055em;
        }

        .ac-kpi-name {
            margin-top: 7px;
            font-size: 10px;
            color: var(--ac-muted);
            font-weight: 900;
            letter-spacing: .11em;
            text-transform: uppercase;
        }

        .ac-kpi-desc {
            margin: 10px 0 0;
            color: var(--ac-muted);
            font-size: 12px;
            line-height: 1.55;
            font-weight: 500;
        }

        .ac-progress-track {
            height: 5px;
            overflow: hidden;
            margin-top: 16px;
            border-radius: 999px;
            background: var(--ac-card-muted);
        }

        .ac-progress-fill {
            height: 100%;
            border-radius: inherit;
        }

        .ac-main-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 390px;
            align-items: start;
            gap: 16px;
        }

        .ac-sidebar {
            display: flex;
            flex-direction: column;
            gap: 16px;
            min-width: 0;
        }

        .ac-card {
            overflow: hidden;
            border-radius: var(--ac-radius-lg);
            background: var(--ac-card);
            border: 1px solid var(--ac-border);
            box-shadow: var(--ac-shadow);
            backdrop-filter: blur(16px);
        }

        .ac-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            padding: 18px 20px;
            background:
                linear-gradient(135deg, rgba(var(--ac-primary-rgb), .07), transparent),
                var(--ac-card-solid);
            border-bottom: 1px solid var(--ac-border);
        }

        .dark .ac-card-header,
        html.dark .ac-card-header {
            background:
                linear-gradient(135deg, rgba(var(--ac-primary-rgb), .11), transparent),
                rgba(15, 23, 42, .74);
        }

        .ac-card-title-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .ac-card-icon {
            width: 44px;
            height: 44px;
            display: grid;
            place-items: center;
            border-radius: 16px;
            color: #fff;
            flex-shrink: 0;
        }

        .ac-card-title {
            font-family: var(--ac-font-head);
            font-size: 15px;
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -.025em;
            color: var(--ac-heading);
        }

        .ac-card-subtitle {
            margin-top: 4px;
            color: var(--ac-muted);
            font-size: 11.5px;
            font-weight: 500;
        }

        .ac-card-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 6px 11px;
            border-radius: 999px;
            background: rgba(var(--ac-primary-rgb), .09);
            border: 1px solid rgba(var(--ac-primary-rgb), .22);
            color: var(--ac-primary);
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .07em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .ac-event-list,
        .ac-session-list,
        .ac-user-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 14px;
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: thin;
            scrollbar-color: rgba(var(--ac-primary-rgb), .45) transparent;
        }

        .ac-event-list {
            max-height: min(76vh, 760px);
            min-height: 320px;
        }

        .ac-session-list {
            max-height: 340px;
            min-height: 150px;
        }

        .ac-user-list {
            max-height: 410px;
            min-height: 180px;
        }

        .ac-event-list::-webkit-scrollbar,
        .ac-session-list::-webkit-scrollbar,
        .ac-user-list::-webkit-scrollbar {
            width: 7px;
        }

        .ac-event-list::-webkit-scrollbar-track,
        .ac-session-list::-webkit-scrollbar-track,
        .ac-user-list::-webkit-scrollbar-track {
            background: transparent;
        }

        .ac-event-list::-webkit-scrollbar-thumb,
        .ac-session-list::-webkit-scrollbar-thumb,
        .ac-user-list::-webkit-scrollbar-thumb {
            border-radius: 999px;
            background: linear-gradient(180deg, var(--ac-primary), var(--ac-accent));
            border: 2px solid transparent;
            background-clip: padding-box;
        }

        .ac-event-list::-webkit-scrollbar-thumb:hover,
        .ac-session-list::-webkit-scrollbar-thumb:hover,
        .ac-user-list::-webkit-scrollbar-thumb:hover {
            background: var(--ac-primary);
            border: 2px solid transparent;
            background-clip: padding-box;
        }

        .ac-event-row {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px;
            border-radius: var(--ac-radius-md);
            background: var(--ac-card-solid);
            border: 1px solid var(--ac-border);
            transition: transform .18s ease, border-color .18s ease, background .18s ease, box-shadow .18s ease;
        }

        .dark .ac-event-row,
        html.dark .ac-event-row {
            background: rgba(15, 23, 42, .72);
        }

        .ac-event-row:hover {
            transform: translateX(3px);
            border-color: rgba(var(--ac-primary-rgb), .2);
            box-shadow: 0 12px 32px rgba(15, 23, 42, .075);
        }

        .dark .ac-event-row:hover,
        html.dark .ac-event-row:hover {
            box-shadow: 0 12px 32px rgba(0, 0, 0, .22);
        }

        .ac-event-icon {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border-radius: 15px;
            color: #fff;
            flex-shrink: 0;
        }

        .ac-event-body {
            flex: 1;
            min-width: 0;
        }

        .ac-event-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 8px;
            flex-wrap: wrap;
        }

        .ac-event-title {
            font-family: var(--ac-font-head);
            color: var(--ac-heading);
            font-size: 13px;
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .ac-severity {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 9px;
            font-weight: 900;
            letter-spacing: .08em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .ac-event-desc {
            margin-top: 5px;
            color: var(--ac-muted);
            font-size: 11.5px;
            font-weight: 500;
            overflow-wrap: anywhere;
        }

        .ac-event-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .ac-event-meta span {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: var(--ac-muted-2);
            font-size: 11px;
            font-weight: 600;
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .ac-session-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 13px;
            border-radius: var(--ac-radius-md);
            background: var(--ac-card-solid);
            border: 1px solid var(--ac-border);
            transition: border-color .18s ease, transform .18s ease, background .18s ease;
        }

        .dark .ac-session-row,
        html.dark .ac-session-row {
            background: rgba(15, 23, 42, .72);
        }

        .ac-session-row:hover {
            transform: translateY(-2px);
            border-color: rgba(var(--ac-primary-rgb), .2);
        }

        .ac-session-left {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .ac-avatar {
            position: relative;
            width: 40px;
            height: 40px;
            display: grid;
            place-items: center;
            border-radius: 999px;
            color: #fff;
            font-family: var(--ac-font-head);
            font-size: 13px;
            font-weight: 800;
            flex-shrink: 0;
        }

        .ac-avatar-dot {
            position: absolute;
            right: 1px;
            bottom: 1px;
            width: 11px;
            height: 11px;
            border-radius: 999px;
            background: #22c55e;
            border: 2px solid var(--ac-card-solid);
        }

        .dark .ac-avatar-dot,
        html.dark .ac-avatar-dot {
            border-color: #0f172a;
        }

        .ac-name {
            color: var(--ac-heading);
            font-size: 13px;
            font-weight: 800;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 210px;
        }

        .ac-subtext {
            margin-top: 2px;
            color: var(--ac-muted);
            font-size: 11px;
            font-weight: 500;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 210px;
        }

        .ac-online-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 4px 9px;
            border-radius: 999px;
            background: rgba(34, 197, 94, .10);
            border: 1px solid rgba(34, 197, 94, .22);
            color: #16a34a;
            font-size: 9.5px;
            font-weight: 900;
            letter-spacing: .08em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .dark .ac-online-pill,
        html.dark .ac-online-pill {
            color: #4ade80;
        }

        .ac-time {
            margin-top: 5px;
            color: var(--ac-muted-2);
            font-size: 10.5px;
            font-weight: 600;
            text-align: right;
        }

        .ac-user-row {
            padding: 13px;
            border-radius: var(--ac-radius-md);
            background: var(--ac-card-solid);
            border: 1px solid var(--ac-border);
            transition: transform .18s ease, border-color .18s ease, background .18s ease;
        }

        .dark .ac-user-row,
        html.dark .ac-user-row {
            background: rgba(15, 23, 42, .72);
        }

        .ac-user-row:hover {
            transform: translateY(-2px);
            border-color: rgba(var(--ac-primary-rgb), .2);
        }

        .ac-user-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 10px;
        }

        .ac-user-left {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .ac-rank {
            width: 36px;
            height: 36px;
            display: grid;
            place-items: center;
            border-radius: 13px;
            color: #fff;
            font-family: var(--ac-font-head);
            font-size: 13px;
            font-weight: 900;
            flex-shrink: 0;
        }

        .ac-percent {
            padding: 4px 9px;
            border-radius: 999px;
            background: rgba(var(--ac-primary-rgb), .09);
            border: 1px solid rgba(var(--ac-primary-rgb), .18);
            color: var(--ac-primary);
            font-size: 11px;
            font-weight: 900;
            white-space: nowrap;
        }

        .ac-user-track {
            height: 5px;
            overflow: hidden;
            border-radius: 999px;
            background: var(--ac-card-muted);
        }

        .ac-user-fill {
            height: 100%;
            border-radius: inherit;
        }

        .ac-empty {
            padding: 42px 20px;
            text-align: center;
            color: var(--ac-muted);
        }

        .ac-empty-icon {
            width: 56px;
            height: 56px;
            display: grid;
            place-items: center;
            margin: 0 auto 14px;
            border-radius: 19px;
            color: #fff;
            background:
                radial-gradient(circle at 30% 24%, rgba(255, 255, 255, .34), transparent 24%),
                linear-gradient(135deg, var(--ac-primary), #00a362);
            box-shadow: 0 18px 38px rgba(var(--ac-primary-rgb), .24);
        }

        .ac-empty-title {
            color: var(--ac-heading);
            font-family: var(--ac-font-head);
            font-size: 14px;
            font-weight: 800;
        }

        .ac-empty-sub {
            max-width: 320px;
            margin: 5px auto 0;
            color: var(--ac-muted);
            font-size: 12px;
            font-weight: 500;
        }

        @media (max-width: 1280px) {
            .ac-main-grid {
                grid-template-columns: minmax(0, 1fr) 350px;
            }

            .ac-event-list {
                max-height: min(70vh, 680px);
            }
        }

        @media (max-width: 1100px) {
            .ac-main-grid {
                grid-template-columns: 1fr;
            }

            .ac-sidebar {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .ac-event-list {
                max-height: 620px;
            }

            .ac-session-list,
            .ac-user-list {
                max-height: 360px;
            }
        }

        @media (max-width: 920px) {
            .ac-hero-top {
                grid-template-columns: 1fr;
            }

            .ac-status-stack {
                justify-content: flex-start;
                max-width: 100%;
            }

            .ac-stat-grid,
            .ac-kpi-row {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .ac-hero-inner {
                padding: 22px;
            }
        }

        @media (max-width: 760px) {
            .ac-chip-row {
                justify-content: flex-start;
                width: 100%;
            }

            .ac-sidebar {
                grid-template-columns: 1fr;
            }

            .ac-card-header {
                align-items: flex-start;
            }
        }

        @media (max-width: 540px) {
            .ac-bg {
                inset: -14px;
                border-radius: 24px;
            }

            .ac-topbar,
            .ac-hero,
            .ac-card,
            .ac-kpi-card,
            .ac-stat-tile {
                border-radius: 18px;
            }

            .ac-hero-inner {
                padding: 18px;
            }

            .ac-hero-title {
                font-size: 27px;
            }

            .ac-stat-grid,
            .ac-kpi-row {
                grid-template-columns: 1fr;
            }

            .ac-event-row {
                flex-direction: column;
            }

            .ac-session-row {
                align-items: flex-start;
                flex-direction: column;
            }

            .ac-name,
            .ac-subtext {
                max-width: 100%;
            }

            .ac-time {
                text-align: left;
            }

            .ac-event-list,
            .ac-session-list,
            .ac-user-list {
                max-height: 520px;
            }
        }
    </style>

    <div class="ac-wrap">
        <div class="ac-bg"></div>

        <div class="ac-shell">
            <nav class="ac-topbar">
                <div class="ac-brand">
                    <div class="ac-brand-icon">
                        <x-filament::icon icon="heroicon-o-shield-check" class="ac-icon" />
                    </div>

                    <div>
                        <span class="ac-brand-title">ERP Audit Control Center</span>
                        <span class="ac-brand-subtitle">Real-time accountability, session tracking and system
                            control</span>
                    </div>
                </div>

                <div class="ac-chip-row">
                    <div class="ac-chip ac-chip-primary">
                        <span class="ac-live-dot"></span>
                        Live Feed
                    </div>

                    <div class="ac-chip ac-chip-date">
                        <x-filament::icon icon="heroicon-o-calendar-days" class="ac-icon" />
                        {{ $today }}
                    </div>

                    <div class="ac-chip">
                        <x-filament::icon icon="heroicon-o-bell-alert" class="ac-icon" />
                        Risk Alerts
                    </div>

                    <div class="ac-chip">
                        <x-filament::icon icon="heroicon-o-finger-print" class="ac-icon" />
                        Sessions
                    </div>
                </div>
            </nav>

            <section class="ac-hero">
                <div class="ac-hero-inner">
                    <div class="ac-hero-top">
                        <div>
                            <div class="ac-eyebrow">
                                <span class="ac-live-dot"></span>
                                Audit Intelligence
                            </div>

                            <div class="ac-hero-title">
                                Real-time <span>user accountability</span><br>
                                and system control.
                            </div>

                            <p class="ac-hero-desc">
                                Monitor logins, active sessions, page visits, risky actions, deleted records,
                                printed reports, exports and every important user action from one secure dashboard.
                            </p>
                        </div>

                        <div class="ac-status-stack">
                            <div class="ac-status-pill ac-status-success">
                                <x-filament::icon icon="heroicon-o-check-circle" class="ac-icon" />
                                System Healthy
                            </div>

                            @if ($highRiskToday > 0)
                                <div class="ac-status-pill ac-status-warning">
                                    <x-filament::icon icon="heroicon-o-exclamation-triangle" class="ac-icon" />
                                    {{ number_format($highRiskToday) }} Risk {{ $plural('Event', $highRiskToday) }}
                                </div>
                            @endif

                            @if ($failedLogins > 0)
                                <div class="ac-status-pill ac-status-danger">
                                    <x-filament::icon icon="heroicon-o-x-circle" class="ac-icon" />
                                    {{ number_format($failedLogins) }} Failed {{ $plural('Login', $failedLogins) }}
                                </div>
                            @endif

                            <div class="ac-status-pill ac-status-info">
                                <x-filament::icon icon="heroicon-o-finger-print" class="ac-icon" />
                                {{ number_format($activeSessionCount) }} Active
                                {{ $plural('Session', $activeSessionCount) }}
                            </div>
                        </div>
                    </div>

                    <div class="ac-stat-grid">
                        @foreach ($summaryStats as $st)
                            <div class="ac-stat-tile" style="color: {{ $st['color'] }}">
                                <div class="ac-stat-icon"
                                    style="background: rgba({{ $st['rgb'] }}, .12); color: {{ $st['color'] }};">
                                    <x-filament::icon :icon="$st['icon']" class="ac-icon" />
                                </div>

                                <div class="ac-stat-value" style="color: {{ $st['color'] }}">
                                    {{ number_format($st['value']) }}
                                </div>

                                <div class="ac-stat-label">
                                    {{ $st['label'] }}
                                </div>

                                <div class="ac-stat-tag"
                                    style="background: rgba({{ $st['rgb'] }}, .11); color: {{ $st['color'] }};">
                                    {{ $st['tag'] }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <div class="ac-kpi-row">
                @foreach ($kpiCards as $card)
                    <div class="ac-kpi-card">
                        <div class="ac-kpi-accent" style="background: {{ $card['gradient'] }}"></div>

                        <div class="ac-kpi-head">
                            <div class="ac-kpi-icon"
                                style="background: {{ $card['gradient'] }}; box-shadow: 0 14px 30px rgba({{ $card['glow'] }}, .24);">
                                <x-filament::icon :icon="$card['icon']" class="ac-icon" />
                            </div>

                            <span class="ac-mini-label">Today</span>
                        </div>

                        <div class="ac-kpi-value" style="color: {{ $card['color'] }}">
                            {{ number_format($card['value']) }}
                        </div>

                        <div class="ac-kpi-name">
                            {{ $card['label'] }}
                        </div>

                        <p class="ac-kpi-desc">
                            {{ $card['description'] }}
                        </p>

                        <div class="ac-progress-track">
                            <div class="ac-progress-fill"
                                style="width: {{ $card['bar_pct'] }}%; background: {{ $card['gradient'] }};"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="ac-main-grid">
                <div class="ac-card">
                    <div class="ac-card-header">
                        <div class="ac-card-title-wrap">
                            <div class="ac-card-icon"
                                style="background: linear-gradient(135deg, {{ $primaryColor }}, {{ $accentColor }}); box-shadow: 0 14px 30px rgba({{ $primaryRgb }}, .22);">
                                <x-filament::icon icon="heroicon-o-clock" class="ac-icon" />
                            </div>

                            <div>
                                <div class="ac-card-title">Latest Audit Events</div>
                                <div class="ac-card-subtitle">Recent activity across users, modules, pages, reports and
                                    records</div>
                            </div>
                        </div>

                        <span class="ac-card-badge">
                            <span class="ac-live-dot"></span>
                            {{ number_format($latestLogs->count()) }} Events
                        </span>
                    </div>

                    <div class="ac-event-list">
                        @forelse ($latestLogs as $log)
                            @php
                                $event = (string) (data_get($log, 'event') ?? 'login');
                                $evKey = $eventIcons[$event] ?? $eventIcons['login'];

                                $sevKey = (string) (data_get($log, 'severity') ?? 'default');
                                $sev = $severityStyles[$sevKey] ?? $severityStyles['default'];

                                $eventLabel =
                                    data_get($log, 'event_label') ?? \Illuminate\Support\Str::headline($event);
                                $moduleLabel = data_get($log, 'module') ?: 'System';

                                $description =
                                    data_get($log, 'description') ?:
                                    (data_get($log, 'record_label') ?:
                                    'No description captured.');

                                $actorLabel = data_get($log, 'actor_label') ?: 'System';
                                $severityLabel =
                                    data_get($log, 'severity_label') ?? \Illuminate\Support\Str::headline($sevKey);
                                $ipAddress = data_get($log, 'ip_address');
                            @endphp

                            <div class="ac-event-row">
                                <div class="ac-event-icon" style="background: {{ $evKey['grad'] }}">
                                    <x-filament::icon :icon="$evKey['icon']" class="ac-icon" />
                                </div>

                                <div class="ac-event-body">
                                    <div class="ac-event-head">
                                        <span class="ac-event-title">
                                            {{ $eventLabel }} — {{ $moduleLabel }}
                                        </span>

                                        <span class="ac-severity"
                                            style="background: {{ $sev['bg'] }}; color: {{ $sev['color'] }}; border: 1px solid {{ $sev['border'] }};">
                                            {{ $severityLabel }}
                                        </span>
                                    </div>

                                    <div class="ac-event-desc">
                                        {{ $description }}
                                    </div>

                                    <div class="ac-event-meta">
                                        <span>
                                            <x-filament::icon icon="heroicon-o-user" class="ac-icon" />
                                            {{ $actorLabel }}
                                        </span>

                                        <span>
                                            <x-filament::icon icon="heroicon-o-calendar-days" class="ac-icon" />
                                            {{ $formatDateTime(data_get($log, 'created_at')) }}
                                        </span>

                                        @if (!blank($ipAddress))
                                            <span>
                                                <x-filament::icon icon="heroicon-o-globe-alt" class="ac-icon" />
                                                {{ $ipAddress }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="ac-empty">
                                <div class="ac-empty-icon">
                                    <x-filament::icon icon="heroicon-o-shield-check" class="ac-icon" />
                                </div>

                                <div class="ac-empty-title">No audit events yet</div>

                                <p class="ac-empty-sub">
                                    User actions will appear here after logins, navigation, edits, prints or exports.
                                </p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="ac-sidebar">
                    <div class="ac-card">
                        <div class="ac-card-header">
                            <div class="ac-card-title-wrap">
                                <div class="ac-card-icon"
                                    style="background: linear-gradient(135deg, #22c55e, #16a34a); box-shadow: 0 14px 30px rgba(34, 197, 94, .22);">
                                    <x-filament::icon icon="heroicon-o-finger-print" class="ac-icon" />
                                </div>

                                <div>
                                    <div class="ac-card-title">Active Sessions</div>
                                    <div class="ac-card-subtitle">Currently active or not yet expired</div>
                                </div>
                            </div>

                            <span class="ac-card-badge">
                                {{ number_format($activeSessionCount) }} Active
                            </span>
                        </div>

                        <div class="ac-session-list">
                            @forelse ($activeSessions as $idx => $session)
                                @php
                                    $grad = $avatarGradients[$idx % count($avatarGradients)];

                                    $actorLabel = trim(
                                        (string) (data_get($session, 'actor_label') ??
                                            (data_get($session, 'actor') ??
                                                (data_get($session, 'user.name') ?? 'User'))),
                                    );

                                    $initials = collect(preg_split('/\s+/', $actorLabel, -1, PREG_SPLIT_NO_EMPTY))
                                        ->take(2)
                                        ->map(fn($word) => mb_strtoupper(mb_substr($word, 0, 1)))
                                        ->implode('');

                                    $initials = $initials !== '' ? $initials : 'U';

                                    $email =
                                        data_get($session, 'user_email') ?? (data_get($session, 'user.email') ?? 'N/A');
                                @endphp

                                <div class="ac-session-row">
                                    <div class="ac-session-left">
                                        <div class="ac-avatar" style="background: {{ $grad }}">
                                            {{ $initials }}
                                            <span class="ac-avatar-dot"></span>
                                        </div>

                                        <div style="min-width: 0;">
                                            <div class="ac-name">
                                                {{ $actorLabel }}
                                            </div>

                                            <div class="ac-subtext">
                                                {{ $email }}
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <div class="ac-online-pill">Online</div>

                                        <div class="ac-time">
                                            {{ $formatDateTime(data_get($session, 'last_seen_at'), 'H:i A') }}
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="ac-empty">
                                    <div class="ac-empty-icon">
                                        <x-filament::icon icon="heroicon-o-lock-closed" class="ac-icon" />
                                    </div>

                                    <div class="ac-empty-title">No active sessions</div>

                                    <p class="ac-empty-sub">
                                        Active login sessions will appear here.
                                    </p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="ac-card">
                        <div class="ac-card-header">
                            <div class="ac-card-title-wrap">
                                <div class="ac-card-icon"
                                    style="background: linear-gradient(135deg, {{ $accentColor }}, #f97316); box-shadow: 0 14px 30px rgba({{ $accentRgb }}, .22);">
                                    <x-filament::icon icon="heroicon-o-chart-bar" class="ac-icon" />
                                </div>

                                <div>
                                    <div class="ac-card-title">Most Active Users Today</div>
                                    <div class="ac-card-subtitle">Highest number of recorded actions</div>
                                </div>
                            </div>

                            <span class="ac-card-badge">
                                {{ number_format($topUsers->count()) }} Users
                            </span>
                        </div>

                        <div class="ac-user-list">
                            @forelse ($topUsers as $index => $user)
                                @php
                                    $userTotal = (int) (data_get($user, 'total') ?? 0);

                                    $percentage =
                                        $totalToday > 0 ? min(100, (int) round(($userTotal / $totalToday) * 100)) : 0;

                                    $rankGrad = $rankGradients[$index] ?? $rankGradients[4];

                                    $actor =
                                        data_get($user, 'actor') ??
                                        (data_get($user, 'actor_label') ?? (data_get($user, 'name') ?? 'Unknown User'));
                                @endphp

                                <div class="ac-user-row">
                                    <div class="ac-user-top">
                                        <div class="ac-user-left">
                                            <div class="ac-rank" style="background: {{ $rankGrad }}">
                                                {{ $index + 1 }}
                                            </div>

                                            <div style="min-width: 0;">
                                                <div class="ac-name">
                                                    {{ $actor }}
                                                </div>

                                                <div class="ac-subtext">
                                                    {{ number_format($userTotal) }} actions today
                                                </div>
                                            </div>
                                        </div>

                                        <span class="ac-percent">
                                            {{ $percentage }}%
                                        </span>
                                    </div>

                                    <div class="ac-user-track">
                                        <div class="ac-user-fill"
                                            style="width: {{ max(4, $percentage) }}%; background: linear-gradient(90deg, {{ $primaryColor }}, {{ $accentColor }});">
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="ac-empty">
                                    <div class="ac-empty-icon"
                                        style="background: linear-gradient(135deg, {{ $accentColor }}, #f97316);">
                                        <x-filament::icon icon="heroicon-o-chart-bar" class="ac-icon" />
                                    </div>

                                    <div class="ac-empty-title">No activity today</div>

                                    <p class="ac-empty-sub">
                                        User activity ranking will appear here.
                                    </p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
