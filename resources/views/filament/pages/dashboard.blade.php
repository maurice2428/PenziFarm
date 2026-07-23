<x-filament-panels::page>
    @php
        $farmName = setting('farm.name', 'Lelekwe Farms Limited');
        $farmTagline = setting('farm.tagline', 'Nurturing Nature, Feeding The Future');

        $primaryColor = trim(setting('theme.primary', '#14532d'));
        $secondaryColor = trim(setting('theme.secondary', '#166534'));
        $accentColor = trim(setting('theme.accent', '#f59e0b'));
        $successColor = trim(setting('theme.success', '#16a34a'));
        $dangerColor = trim(setting('theme.danger', '#dc2626'));

        $today = now('Africa/Nairobi')->format('l, d M Y');

        $loggedInUser = auth()->user();
        $userName = $loggedInUser?->name ?? 'System User';
        $userInitial = strtoupper(substr(trim($userName), 0, 1));
        $userRoles = $loggedInUser?->getRoleNames()?->implode(' • ') ?: 'No Role Assigned';
    @endphp

    <style>
        @font-face {
            font-family: 'ChopinScriptDashboard';
            src: url('/fonts/ChopinScript.ttf?v={{ time() }}') format('truetype');
            font-weight: 400;
            font-style: normal;
            font-display: block;
        }

        .farm-dashboard {
            display: flex;
            flex-direction: column;
            gap: 1.15rem;
        }

        .farm-shell-card {
            border: 1px solid rgba(229, 231, 235, 1);
            background:
                radial-gradient(circle at top right, color-mix(in srgb, {{ $primaryColor }} 7%, transparent), transparent 30%),
                linear-gradient(180deg, rgba(255, 255, 255, .98), rgba(249, 250, 251, .94));
            box-shadow: 0 16px 40px rgba(2, 6, 23, .055);
        }

        .dark .farm-shell-card {
            background:
                radial-gradient(circle at top right, color-mix(in srgb, {{ $primaryColor }} 18%, transparent), transparent 32%),
                linear-gradient(180deg, rgba(17, 24, 39, .96), rgba(15, 23, 42, .94));
            border-color: rgba(148, 163, 184, .14);
        }

        .farm-user-panel {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto auto;
            gap: .85rem;
            align-items: center;
            padding: .85rem .95rem;
            border-left: 4px solid {{ $successColor }};
        }

        .farm-user-main,
        .farm-user-role-card,
        .farm-user-system-card {
            display: flex;
            align-items: center;
            gap: .75rem;
            min-width: 0;
        }

        .farm-user-avatar {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            color: #fff;
            font-size: .9rem;
            font-weight: 950;
            background:
                radial-gradient(circle at top left, rgba(255, 255, 255, .26), transparent 30%),
                linear-gradient(135deg, {{ $primaryColor }}, {{ $secondaryColor }});
            border: 2px solid rgba(255, 255, 255, .92);
            box-shadow: 0 10px 24px color-mix(in srgb, {{ $primaryColor }} 28%, transparent);
        }

        .farm-muted-label {
            font-size: .62rem;
            font-weight: 950;
            letter-spacing: .09em;
            color: #6b7280;
            text-transform: uppercase;
        }

        .dark .farm-muted-label {
            color: #9ca3af;
        }

        .farm-user-name {
            margin-top: .08rem;
            font-size: .92rem;
            font-weight: 950;
            color: #111827;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dark .farm-user-name {
            color: #f9fafb;
        }

        .farm-user-role-card,
        .farm-user-system-card {
            padding: .55rem .75rem;
            background: color-mix(in srgb, {{ $primaryColor }} 7%, white);
            border: 1px solid color-mix(in srgb, {{ $primaryColor }} 14%, white);
        }

        .dark .farm-user-role-card,
        .dark .farm-user-system-card {
            background: rgba(15, 23, 42, .72);
            border-color: rgba(148, 163, 184, .14);
        }

        .farm-user-role {
            margin-top: .08rem;
            font-size: .78rem;
            font-weight: 950;
            color: {{ $primaryColor }};
            white-space: nowrap;
        }

        .farm-live-signal {
            position: relative;
            width: 32px;
            height: 32px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
        }

        .signal-core {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            background: {{ $successColor }};
            box-shadow: 0 0 0 5px color-mix(in srgb, {{ $successColor }} 18%, transparent);
            z-index: 3;
        }

        .signal-wave {
            position: absolute;
            border: 2px solid {{ $successColor }};
            border-radius: 999px;
            opacity: 0;
            animation: farmSignalPulse 1.9s infinite ease-out;
        }

        .signal-wave-1 {
            width: 22px;
            height: 22px;
        }

        .signal-wave-2 {
            width: 32px;
            height: 32px;
            animation-delay: .45s;
        }

        @keyframes farmSignalPulse {
            0% {
                transform: scale(.55);
                opacity: .85;
            }

            70% {
                opacity: .12;
            }

            100% {
                transform: scale(1.25);
                opacity: 0;
            }
        }

        .farm-hero {
            position: relative;
            overflow: hidden;
            padding: 1.55rem;
            color: #fff;
            background:
                radial-gradient(circle at top right, rgba(255, 255, 255, .22), transparent 28%),
                radial-gradient(circle at bottom left, {{ $accentColor }}40, transparent 25%),
                linear-gradient(135deg, {{ $primaryColor }} 0%, {{ $secondaryColor }} 55%, #052e16 100%);
            box-shadow: 0 24px 70px rgba(2, 6, 23, .16);
        }

        .farm-hero::after {
            content: "";
            position: absolute;
            width: 260px;
            height: 260px;
            right: -90px;
            top: -90px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .11);
            pointer-events: none;
        }

        .farm-hero-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 235px;
            gap: 1rem;
            align-items: stretch;
            position: relative;
            z-index: 2;
        }

        .farm-kicker,
        .farm-section-kicker,
        .farm-hero-mini-label {
            text-transform: none;
        }

        .farm-kicker {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            font-size: .72rem;
            font-weight: 950;
            letter-spacing: .08em;
            opacity: .9;
        }

        .farm-brand {
            margin: .25rem 0 0;
            font-family: 'ChopinScriptDashboard', cursive !important;
            font-size: clamp(2.7rem, 5.2vw, 5rem);
            line-height: .95;
            font-weight: 400 !important;
            text-shadow: 0 14px 34px rgba(0, 0, 0, .20);
        }

        .farm-title {
            margin-top: .45rem;
            max-width: 900px;
            font-size: clamp(1rem, 1.6vw, 1.25rem);
            font-weight: 850;
            line-height: 1.32;
            color: rgba(255, 255, 255, .93);
        }

        .farm-title small {
            display: block;
            margin-top: .35rem;
            font-size: .82rem;
            line-height: 1.55;
            font-weight: 600;
            color: rgba(255, 255, 255, .80);
        }

        .farm-pills {
            display: flex;
            flex-wrap: wrap;
            gap: .55rem;
            margin-top: 1rem;
        }

        .farm-pill {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .5rem .75rem;
            border: 1px solid rgba(255, 255, 255, .20);
            background: rgba(255, 255, 255, .12);
            backdrop-filter: blur(14px);
            font-size: .68rem;
            font-weight: 850;
            color: #fff;
        }

        .farm-hero-side {
            border: 1px solid rgba(255, 255, 255, .18);
            background: rgba(255, 255, 255, .12);
            backdrop-filter: blur(16px);
            padding: .75rem;
            display: flex;
            align-items: stretch;
        }

        .farm-clock-card {
            width: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: .45rem;
            border-left: 4px solid {{ $accentColor }};
            padding: .8rem;
            background: rgba(255, 255, 255, .10);
            text-align: center;
        }

        .farm-hero-mini-label {
            font-size: .65rem;
            letter-spacing: .06em;
            opacity: .78;
            font-weight: 950;
        }

        .farm-hero-mini-value {
            font-size: .82rem;
            line-height: 1.35;
            font-weight: 950;
        }

        .analog-clock {
            position: relative;
            width: 78px;
            height: 78px;
            border: 3px solid rgba(255, 255, 255, .55);
            border-radius: 50%;
            background: rgba(255, 255, 255, .10);
            box-shadow: inset 0 0 18px rgba(0, 0, 0, .12);
        }

        .hand {
            position: absolute;
            left: 50%;
            bottom: 50%;
            transform-origin: bottom center;
            border-radius: 999px;
            transform: translateX(-50%) rotate(0deg);
        }

        .hour-hand {
            width: 4px;
            height: 22px;
            background: #fff;
        }

        .minute-hand {
            width: 3px;
            height: 29px;
            background: rgba(255, 255, 255, .9);
        }

        .second-hand {
            width: 2px;
            height: 33px;
            background: {{ $accentColor }};
        }

        .clock-dot {
            position: absolute;
            width: 9px;
            height: 9px;
            background: {{ $accentColor }};
            border: 2px solid #fff;
            border-radius: 50%;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
        }

        .clock-caption {
            font-size: .66rem;
            font-weight: 850;
            color: rgba(255, 255, 255, .78);
        }

        .clock-number {
            position: absolute;
            color: #fff;
            font-size: .56rem;
            font-weight: 950;
            line-height: 1;
            transform: translate(-50%, -50%);
        }

        .number-12 {
            left: 50%;
            top: 10%;
        }

        .number-1 {
            left: 70%;
            top: 15%;
        }

        .number-2 {
            left: 85%;
            top: 30%;
        }

        .number-3 {
            left: 90%;
            top: 50%;
        }

        .number-4 {
            left: 85%;
            top: 70%;
        }

        .number-5 {
            left: 70%;
            top: 85%;
        }

        .number-6 {
            left: 50%;
            top: 90%;
        }

        .number-7 {
            left: 30%;
            top: 85%;
        }

        .number-8 {
            left: 15%;
            top: 70%;
        }

        .number-9 {
            left: 10%;
            top: 50%;
        }

        .number-10 {
            left: 15%;
            top: 30%;
        }

        .number-11 {
            left: 30%;
            top: 15%;
        }

        .farm-section {
            border: 1px solid rgba(229, 231, 235, 1);
            box-shadow: 0 14px 40px rgba(2, 6, 23, .05);
            padding: 1rem;
        }

        .farm-section-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .9rem;
            flex-wrap: wrap;
            margin-bottom: .85rem;
        }

        .farm-section-kicker {
            display: inline-flex;
            align-items: center;
            gap: .42rem;
            color: {{ $primaryColor }};
            font-size: .7rem;
            font-weight: 950;
            letter-spacing: .06em;
        }

        .farm-section-title {
            margin-top: .25rem;
            font-size: 1.05rem;
            font-weight: 950;
            color: #111827;
            letter-spacing: -.025em;
        }

        .dark .farm-section-title {
            color: #f9fafb;
        }

        .farm-section-subtitle {
            margin-top: .22rem;
            max-width: 820px;
            color: #6b7280;
            font-size: .78rem;
            line-height: 1.55;
        }

        .dark .farm-section-subtitle {
            color: #9ca3af;
        }

        .farm-section-badge {
            display: inline-flex;
            align-items: center;
            gap: .42rem;
            padding: .45rem .7rem;
            background: color-mix(in srgb, {{ $primaryColor }} 8%, white);
            color: {{ $primaryColor }};
            border: 1px solid color-mix(in srgb, {{ $primaryColor }} 18%, white);
            font-size: .68rem;
            font-weight: 950;
            white-space: nowrap;
        }

        .farm-widget-row+.farm-widget-row {
            margin-top: .9rem;
        }

        .farm-chart-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: .9rem;
        }

        @media (min-width: 1024px) {
            .farm-chart-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .farm-dashboard>.farm-section,
        .farm-dashboard .fi-wi-widget,
        .farm-dashboard .fi-section {
            border-radius: 0 !important;
            border: 1px solid rgba(229, 231, 235, 1) !important;
            box-shadow: 0 10px 28px rgba(2, 6, 23, .045) !important;
        }

        .farm-dashboard .fi-wi-widget:hover,
        .farm-dashboard .fi-section:hover {
            border-color: color-mix(in srgb, {{ $primaryColor }} 22%, white) !important;
            box-shadow: 0 18px 45px rgba(2, 6, 23, .08) !important;
        }

        .farm-dashboard canvas {
            max-height: 330px !important;
        }

        .livestock-summary-wrap,
        .breed-snapshot-wrap,
        .farm-compact-block {
            border: 1px solid color-mix(in srgb, {{ $primaryColor }} 14%, #e5e7eb);
            background:
                radial-gradient(circle at top right, color-mix(in srgb, {{ $primaryColor }} 7%, transparent), transparent 28%),
                linear-gradient(180deg, rgba(255, 255, 255, .98), rgba(249, 250, 251, .94));
            box-shadow: 0 14px 36px rgba(2, 6, 23, .05);
            padding: .85rem;
            overflow: hidden;
        }

        .dark .livestock-summary-wrap,
        .dark .breed-snapshot-wrap,
        .dark .farm-compact-block {
            background:
                radial-gradient(circle at top right, color-mix(in srgb, {{ $primaryColor }} 16%, transparent), transparent 30%),
                linear-gradient(180deg, rgba(17, 24, 39, .96), rgba(15, 23, 42, .94));
            border-color: rgba(148, 163, 184, .14);
        }

        .livestock-summary-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .6rem;
        }

        @media (min-width: 900px) {
            .livestock-summary-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (min-width: 1500px) {
            .livestock-summary-grid {
                grid-template-columns: repeat(6, minmax(0, 1fr));
            }
        }

        .livestock-summary-card {
            position: relative;
            min-width: 0;
            min-height: 96px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: .5rem;
            padding: .68rem;
            border: 1px solid rgba(229, 231, 235, 1);
            background: rgba(255, 255, 255, .96);
            box-shadow: 0 8px 22px rgba(2, 6, 23, .04);
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }

        .dark .livestock-summary-card {
            background: rgba(31, 41, 55, .92);
            border-color: rgba(148, 163, 184, .14);
        }

        .livestock-summary-card::before {
            content: "";
            position: absolute;
            inset: 0;
            border-left: 3px solid var(--metric-color);
            pointer-events: none;
        }

        .livestock-summary-card::after {
            content: "";
            position: absolute;
            right: -34px;
            top: -38px;
            width: 92px;
            height: 92px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--metric-color) 10%, transparent);
            pointer-events: none;
        }

        .livestock-summary-card:hover {
            transform: translateY(-2px);
            border-color: color-mix(in srgb, var(--metric-color) 30%, #e5e7eb);
            box-shadow: 0 16px 36px rgba(2, 6, 23, .085);
        }

        .livestock-summary-top,
        .livestock-summary-bottom {
            position: relative;
            z-index: 2;
        }

        .livestock-summary-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .55rem;
        }

        .livestock-summary-title {
            font-size: .68rem;
            line-height: 1.2;
            font-weight: 950;
            color: #374151;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dark .livestock-summary-title {
            color: #e5e7eb;
        }

        .livestock-summary-subtitle {
            margin-top: .2rem;
            font-size: .55rem;
            line-height: 1.25;
            font-weight: 750;
            color: #9ca3af;
        }

        .livestock-summary-icon {
            flex-shrink: 0;
            width: 30px;
            height: 30px;
            display: grid;
            place-items: center;
            background: color-mix(in srgb, var(--metric-color) 13%, white);
            color: var(--metric-color);
            border: 1px solid color-mix(in srgb, var(--metric-color) 22%, white);
        }

        .livestock-summary-bottom {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: .65rem;
        }

        .livestock-summary-value {
            font-size: clamp(1.4rem, 1.8vw, 1.9rem);
            line-height: .95;
            font-weight: 950;
            color: #111827;
            letter-spacing: -.05em;
        }

        .dark .livestock-summary-value {
            color: #f9fafb;
        }

        .livestock-summary-action {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            padding: .28rem .44rem;
            background: color-mix(in srgb, var(--metric-color) 9%, white);
            color: var(--metric-color);
            border: 1px solid color-mix(in srgb, var(--metric-color) 14%, white);
            font-size: .55rem;
            font-weight: 950;
            line-height: 1;
            white-space: nowrap;
        }

        .livestock-summary-card-muted .livestock-summary-value {
            color: #9ca3af;
        }

        .breed-snapshot-head,
        .farm-mini-section-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: .75rem;
        }

        .breed-snapshot-kicker,
        .farm-mini-kicker {
            display: inline-flex;
            align-items: center;
            gap: .42rem;
            color: {{ $primaryColor }};
            font-size: .68rem;
            font-weight: 950;
            letter-spacing: .06em;
        }

        .breed-snapshot-title,
        .farm-mini-title {
            margin-top: .22rem;
            font-size: .95rem;
            font-weight: 950;
            color: #111827;
            letter-spacing: -.025em;
        }

        .dark .breed-snapshot-title,
        .dark .farm-mini-title {
            color: #f9fafb;
        }

        .breed-snapshot-subtitle,
        .farm-mini-subtitle {
            margin-top: .15rem;
            font-size: .72rem;
            line-height: 1.45;
            color: #6b7280;
            max-width: 760px;
        }

        .dark .breed-snapshot-subtitle,
        .dark .farm-mini-subtitle {
            color: #9ca3af;
        }

        .breed-snapshot-badge,
        .farm-mini-badge {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .42rem .65rem;
            background: color-mix(in srgb, {{ $primaryColor }} 8%, white);
            color: {{ $primaryColor }};
            border: 1px solid color-mix(in srgb, {{ $primaryColor }} 18%, white);
            font-size: .66rem;
            font-weight: 950;
            white-space: nowrap;
        }

        .breed-grid-compact {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .6rem;
        }

        @media (min-width: 760px) {
            .breed-grid-compact {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (min-width: 1180px) {
            .breed-grid-compact {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        @media (min-width: 1536px) {
            .breed-grid-compact {
                grid-template-columns: repeat(5, minmax(0, 1fr));
            }
        }

        .breed-card-compact {
            position: relative;
            display: block;
            min-width: 0;
            min-height: 82px;
            border: 1px solid rgba(229, 231, 235, 1);
            background: linear-gradient(180deg, rgba(255, 255, 255, .98), rgba(255, 255, 255, .94));
            box-shadow: 0 8px 20px rgba(2, 6, 23, .04);
            padding: .6rem;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            cursor: pointer;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }

        .dark .breed-card-compact {
            background: rgba(31, 41, 55, .92);
            border-color: rgba(148, 163, 184, .14);
        }

        .breed-card-compact::before {
            content: "";
            position: absolute;
            inset: 0;
            border-left: 3px solid {{ $primaryColor }};
            opacity: .8;
            pointer-events: none;
        }

        .breed-card-compact::after {
            content: "";
            position: absolute;
            right: -28px;
            top: -35px;
            width: 90px;
            height: 90px;
            border-radius: 999px;
            background: color-mix(in srgb, {{ $primaryColor }} 8%, transparent);
            pointer-events: none;
        }

        .breed-card-compact:hover {
            transform: translateY(-2px);
            border-color: color-mix(in srgb, {{ $primaryColor }} 28%, #e5e7eb);
            box-shadow: 0 14px 34px rgba(2, 6, 23, .085);
        }

        .breed-card-main {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            gap: .55rem;
            min-width: 0;
        }

        .breed-avatar-compact {
            flex: 0 0 36px;
            width: 36px;
            height: 36px;
            display: grid;
            place-items: center;
            border: 1px solid rgba(229, 231, 235, 1);
            background: linear-gradient(135deg, color-mix(in srgb, {{ $primaryColor }} 10%, white), #f8fafc);
            color: #14532d;
            font-size: .64rem;
            font-weight: 950;
            line-height: 1.05;
            overflow: hidden;
            text-align: center;
        }

        .dark .breed-avatar-compact {
            background: rgba(15, 23, 42, .92);
            border-color: rgba(148, 163, 184, .16);
            color: #d1fae5;
        }

        .breed-avatar-compact img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .breed-card-info {
            min-width: 0;
            flex: 1;
        }

        .breed-card-name {
            font-size: .78rem;
            line-height: 1.15;
            font-weight: 950;
            color: #111827;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dark .breed-card-name {
            color: #f9fafb;
        }

        .breed-card-species {
            margin-top: .16rem;
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            max-width: 100%;
            font-size: .61rem;
            font-weight: 850;
            color: #6b7280;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dark .breed-card-species {
            color: #9ca3af;
        }

        .breed-card-bottom {
            position: relative;
            z-index: 2;
            margin-top: .55rem;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: .5rem;
        }

        .breed-mini-pill {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            padding: .28rem .45rem;
            color: {{ $primaryColor }};
            background: color-mix(in srgb, {{ $primaryColor }} 9%, white);
            border: 1px solid color-mix(in srgb, {{ $primaryColor }} 14%, white);
            font-size: .56rem;
            font-weight: 950;
            line-height: 1;
            white-space: nowrap;
        }

        .breed-count-box {
            text-align: right;
            flex-shrink: 0;
        }

        .breed-count-number {
            font-size: 1.22rem;
            line-height: .9;
            font-weight: 950;
            color: #064e3b;
            letter-spacing: -.04em;
        }

        .dark .breed-count-number {
            color: #a7f3d0;
        }

        .breed-count-number.is-zero {
            color: #9ca3af;
        }

        .breed-count-label {
            margin-top: .08rem;
            font-size: .52rem;
            font-weight: 900;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .species-dot {
            width: .38rem;
            height: .38rem;
            border-radius: 999px;
            display: inline-block;
            background: {{ $primaryColor }};
            flex-shrink: 0;
        }

        .species-cattle .species-dot {
            background: #2563eb;
        }

        .species-goat .species-dot {
            background: #16a34a;
        }

        .species-sheep .species-dot {
            background: #f59e0b;
        }

        .breed-empty-state {
            padding: 1rem;
            border: 1px dashed rgba(148, 163, 184, .5);
            color: #6b7280;
            font-size: .82rem;
            text-align: center;
        }

        .farm-weight-actions {
            display: flex;
            align-items: center;
            gap: .5rem;
            flex-wrap: wrap;
        }

        .farm-pdf-btn {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .52rem .75rem;
            color: #fff;
            font-size: .72rem;
            font-weight: 900;
            background: linear-gradient(135deg, {{ $primaryColor }}, {{ $secondaryColor }});
            box-shadow: 0 10px 24px color-mix(in srgb, {{ $primaryColor }} 22%, transparent);
            text-decoration: none;
        }

        .farm-live-kpis {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .7rem;
        }

        .farm-live-kpi {
            border: 1px solid rgba(229, 231, 235, 1);
            background: rgba(255, 255, 255, .96);
            padding: .8rem;
            box-shadow: 0 10px 25px rgba(2, 6, 23, .045);
            border-left: 3px solid {{ $primaryColor }};
        }

        .farm-live-kpi-label {
            font-size: .64rem;
            font-weight: 950;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .farm-live-kpi-value {
            margin-top: .28rem;
            font-size: .84rem;
            line-height: 1.35;
            font-weight: 900;
            color: #111827;
        }

        .dark .farm-live-kpi {
            background: rgba(31, 41, 55, .92);
            border-color: rgba(148, 163, 184, .14);
        }

        .dark .farm-live-kpi-value {
            color: #f9fafb;
        }

        .farm-hr-widget-wrap {
            padding: .25rem;
        }

        .farm-hr-widget-wrap .fi-wi-stats-overview-stats-ctn {
            gap: .8rem !important;
        }

        .farm-hr-widget-wrap .fi-wi-stats-overview-stat {
            padding: .85rem !important;
            min-height: 108px;
            border-radius: 0 !important;
        }

        .farm-hr-widget-wrap .fi-wi-stats-overview-stat-value {
            font-size: 1rem !important;
            line-height: 1.2 !important;
            font-weight: 950 !important;
        }

        .farm-hr-widget-wrap .fi-wi-stats-overview-stat-label {
            font-size: .72rem !important;
            font-weight: 900 !important;
            color: #374151 !important;
        }

        .farm-hr-widget-wrap .fi-wi-stats-overview-stat-description {
            margin-top: .35rem !important;
            font-size: .7rem !important;
            line-height: 1.45 !important;
            color: #6b7280 !important;
        }

        /*
|--------------------------------------------------------------------------
| Premium HR Command Cards
|--------------------------------------------------------------------------
*/
        .hr-command-wrap {
            border: 1px solid color-mix(in srgb, {{ $primaryColor }} 14%, #e5e7eb);
            background:
                radial-gradient(circle at top right, color-mix(in srgb, {{ $primaryColor }} 7%, transparent), transparent 28%),
                linear-gradient(180deg, rgba(255, 255, 255, .98), rgba(249, 250, 251, .94));
            box-shadow: 0 14px 36px rgba(2, 6, 23, .05);
            padding: .85rem;
            overflow: hidden;
        }

        .dark .hr-command-wrap {
            background:
                radial-gradient(circle at top right, color-mix(in srgb, {{ $primaryColor }} 16%, transparent), transparent 30%),
                linear-gradient(180deg, rgba(17, 24, 39, .96), rgba(15, 23, 42, .94));
            border-color: rgba(148, 163, 184, .14);
        }

        .hr-command-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .6rem;
        }

        @media (min-width: 900px) {
            .hr-command-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        .hr-command-card {
            position: relative;
            min-width: 0;
            min-height: 104px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: .55rem;
            padding: .72rem;
            border: 1px solid rgba(229, 231, 235, 1);
            background: rgba(255, 255, 255, .96);
            box-shadow: 0 8px 22px rgba(2, 6, 23, .04);
            overflow: hidden;
            color: inherit;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }

        .dark .hr-command-card {
            background: rgba(31, 41, 55, .92);
            border-color: rgba(148, 163, 184, .14);
        }

        .hr-command-card::before {
            content: "";
            position: absolute;
            inset: 0;
            border-left: 3px solid var(--hr-color);
            pointer-events: none;
        }

        .hr-command-card::after {
            content: "";
            position: absolute;
            right: -34px;
            top: -38px;
            width: 92px;
            height: 92px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--hr-color) 10%, transparent);
            pointer-events: none;
        }

        .hr-command-card:hover {
            transform: translateY(-2px);
            border-color: color-mix(in srgb, var(--hr-color) 30%, #e5e7eb);
            box-shadow: 0 16px 36px rgba(2, 6, 23, .085);
        }

        .hr-command-top,
        .hr-command-bottom {
            position: relative;
            z-index: 2;
        }

        .hr-command-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .55rem;
        }

        .hr-command-title {
            font-size: .68rem;
            line-height: 1.2;
            font-weight: 950;
            color: #374151;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dark .hr-command-title {
            color: #e5e7eb;
        }

        .hr-command-subtitle {
            margin-top: .2rem;
            font-size: .55rem;
            line-height: 1.25;
            font-weight: 750;
            color: #9ca3af;
        }

        .hr-command-icon {
            flex-shrink: 0;
            width: 31px;
            height: 31px;
            display: grid;
            place-items: center;
            background: color-mix(in srgb, var(--hr-color) 13%, white);
            color: var(--hr-color);
            border: 1px solid color-mix(in srgb, var(--hr-color) 22%, white);
        }

        .dark .hr-command-icon {
            background: color-mix(in srgb, var(--hr-color) 18%, #111827);
            border-color: color-mix(in srgb, var(--hr-color) 28%, #374151);
        }

        .hr-command-bottom {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: .65rem;
        }

        .hr-command-value {
            font-size: clamp(1.45rem, 1.8vw, 2rem);
            line-height: .95;
            font-weight: 950;
            color: #111827;
            letter-spacing: -.05em;
        }

        .dark .hr-command-value {
            color: #f9fafb;
        }

        .hr-command-pill {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            padding: .28rem .44rem;
            background: color-mix(in srgb, var(--hr-color) 9%, white);
            color: var(--hr-color);
            border: 1px solid color-mix(in srgb, var(--hr-color) 14%, white);
            font-size: .54rem;
            font-weight: 950;
            line-height: 1;
            white-space: nowrap;
        }

        .dark .hr-command-pill {
            background: color-mix(in srgb, var(--hr-color) 14%, #111827);
            border-color: color-mix(in srgb, var(--hr-color) 22%, #374151);
        }

        .hr-command-card-muted .hr-command-value {
            color: #9ca3af;
        }

        .hr-mini-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .7rem;
            margin-bottom: .9rem;
        }

        @media (min-width: 900px) {
            .hr-mini-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        .hr-mini-card {
            border: 1px solid rgba(229, 231, 235, 1);
            background: rgba(255, 255, 255, .96);
            padding: .78rem;
            border-left: 3px solid {{ $primaryColor }};
            box-shadow: 0 10px 25px rgba(2, 6, 23, .045);
        }

        .dark .hr-mini-card {
            background: rgba(31, 41, 55, .92);
            border-color: rgba(148, 163, 184, .14);
        }

        .hr-mini-label {
            font-size: .62rem;
            font-weight: 950;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .dark .hr-mini-label {
            color: #9ca3af;
        }

        .hr-mini-value {
            margin-top: .28rem;
            font-size: .8rem;
            line-height: 1.35;
            font-weight: 900;
            color: #111827;
        }

        .dark .hr-mini-value {
            color: #f9fafb;
        }

        /*
|--------------------------------------------------------------------------
| Premium HR Command + Payroll Cards
|--------------------------------------------------------------------------
*/
        .hr-command-wrap,
        .hr-payroll-wrap {
            border: 1px solid color-mix(in srgb, {{ $primaryColor }} 14%, #e5e7eb);
            background:
                radial-gradient(circle at top right, color-mix(in srgb, {{ $primaryColor }} 7%, transparent), transparent 28%),
                linear-gradient(180deg, rgba(255, 255, 255, .98), rgba(249, 250, 251, .94));
            box-shadow: 0 14px 36px rgba(2, 6, 23, .05);
            padding: .85rem;
            overflow: hidden;
        }

        .dark .hr-command-wrap,
        .dark .hr-payroll-wrap {
            background:
                radial-gradient(circle at top right, color-mix(in srgb, {{ $primaryColor }} 16%, transparent), transparent 30%),
                linear-gradient(180deg, rgba(17, 24, 39, .96), rgba(15, 23, 42, .94));
            border-color: rgba(148, 163, 184, .14);
        }

        .hr-block-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: .75rem;
        }

        .hr-block-kicker {
            display: inline-flex;
            align-items: center;
            gap: .42rem;
            color: {{ $primaryColor }};
            font-size: .68rem;
            font-weight: 950;
            letter-spacing: .06em;
        }

        .hr-block-title {
            margin-top: .22rem;
            font-size: .95rem;
            font-weight: 950;
            color: #111827;
            letter-spacing: -.025em;
        }

        .dark .hr-block-title {
            color: #f9fafb;
        }

        .hr-block-subtitle {
            margin-top: .15rem;
            font-size: .72rem;
            line-height: 1.45;
            color: #6b7280;
            max-width: 760px;
        }

        .dark .hr-block-subtitle {
            color: #9ca3af;
        }

        .hr-block-badge {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .42rem .65rem;
            background: color-mix(in srgb, {{ $primaryColor }} 8%, white);
            color: {{ $primaryColor }};
            border: 1px solid color-mix(in srgb, {{ $primaryColor }} 18%, white);
            font-size: .66rem;
            font-weight: 950;
            white-space: nowrap;
        }

        .dark .hr-block-badge {
            background: color-mix(in srgb, {{ $primaryColor }} 14%, #111827);
            border-color: color-mix(in srgb, {{ $primaryColor }} 24%, #374151);
        }

        .hr-command-grid,
        .hr-payroll-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .6rem;
        }

        @media (min-width: 900px) {

            .hr-command-grid,
            .hr-payroll-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        .hr-command-card,
        .hr-payroll-card {
            position: relative;
            min-width: 0;
            min-height: 104px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: .55rem;
            padding: .72rem;
            border: 1px solid rgba(229, 231, 235, 1);
            background: rgba(255, 255, 255, .96);
            box-shadow: 0 8px 22px rgba(2, 6, 23, .04);
            overflow: hidden;
            color: inherit;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }

        .dark .hr-command-card,
        .dark .hr-payroll-card {
            background: rgba(31, 41, 55, .92);
            border-color: rgba(148, 163, 184, .14);
        }

        .hr-command-card::before,
        .hr-payroll-card::before {
            content: "";
            position: absolute;
            inset: 0;
            border-left: 3px solid var(--hr-color);
            pointer-events: none;
        }

        .hr-command-card::after,
        .hr-payroll-card::after {
            content: "";
            position: absolute;
            right: -34px;
            top: -38px;
            width: 92px;
            height: 92px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--hr-color) 10%, transparent);
            pointer-events: none;
        }

        .hr-command-card:hover,
        .hr-payroll-card:hover {
            transform: translateY(-2px);
            border-color: color-mix(in srgb, var(--hr-color) 30%, #e5e7eb);
            box-shadow: 0 16px 36px rgba(2, 6, 23, .085);
        }

        .hr-command-top,
        .hr-command-bottom,
        .hr-payroll-top,
        .hr-payroll-bottom {
            position: relative;
            z-index: 2;
        }

        .hr-command-top,
        .hr-payroll-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .55rem;
        }

        .hr-command-title,
        .hr-payroll-title {
            font-size: .68rem;
            line-height: 1.2;
            font-weight: 950;
            color: #374151;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dark .hr-command-title,
        .dark .hr-payroll-title {
            color: #e5e7eb;
        }

        .hr-command-subtitle,
        .hr-payroll-subtitle {
            margin-top: .2rem;
            font-size: .55rem;
            line-height: 1.25;
            font-weight: 750;
            color: #9ca3af;
        }

        .hr-command-icon,
        .hr-payroll-icon {
            flex-shrink: 0;
            width: 31px;
            height: 31px;
            display: grid;
            place-items: center;
            background: color-mix(in srgb, var(--hr-color) 13%, white);
            color: var(--hr-color);
            border: 1px solid color-mix(in srgb, var(--hr-color) 22%, white);
        }

        .dark .hr-command-icon,
        .dark .hr-payroll-icon {
            background: color-mix(in srgb, var(--hr-color) 18%, #111827);
            border-color: color-mix(in srgb, var(--hr-color) 28%, #374151);
        }

        .hr-command-bottom,
        .hr-payroll-bottom {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: .65rem;
        }

        .hr-command-value,
        .hr-payroll-value {
            font-size: clamp(1.35rem, 1.8vw, 1.9rem);
            line-height: .95;
            font-weight: 950;
            color: #111827;
            letter-spacing: -.05em;
        }

        .dark .hr-command-value,
        .dark .hr-payroll-value {
            color: #f9fafb;
        }

        .hr-payroll-money {
            font-size: clamp(1rem, 1.45vw, 1.35rem);
            letter-spacing: -.04em;
        }

        .hr-command-pill,
        .hr-payroll-pill {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            padding: .28rem .44rem;
            background: color-mix(in srgb, var(--hr-color) 9%, white);
            color: var(--hr-color);
            border: 1px solid color-mix(in srgb, var(--hr-color) 14%, white);
            font-size: .54rem;
            font-weight: 950;
            line-height: 1;
            white-space: nowrap;
        }

        .dark .hr-command-pill,
        .dark .hr-payroll-pill {
            background: color-mix(in srgb, var(--hr-color) 14%, #111827);
            border-color: color-mix(in srgb, var(--hr-color) 22%, #374151);
        }

        .hr-command-card-muted .hr-command-value,
        .hr-payroll-card-muted .hr-payroll-value {
            color: #9ca3af;
        }

        .hr-mini-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .7rem;
            margin-bottom: .9rem;
        }

        @media (min-width: 900px) {
            .hr-mini-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        .hr-mini-card {
            border: 1px solid rgba(229, 231, 235, 1);
            background: rgba(255, 255, 255, .96);
            padding: .78rem;
            border-left: 3px solid {{ $primaryColor }};
            box-shadow: 0 10px 25px rgba(2, 6, 23, .045);
        }

        .dark .hr-mini-card {
            background: rgba(31, 41, 55, .92);
            border-color: rgba(148, 163, 184, .14);
        }

        .hr-mini-label {
            font-size: .62rem;
            font-weight: 950;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .dark .hr-mini-label {
            color: #9ca3af;
        }

        .hr-mini-value {
            margin-top: .28rem;
            font-size: .8rem;
            line-height: 1.35;
            font-weight: 900;
            color: #111827;
        }

        .dark .hr-mini-value {
            color: #f9fafb;
        }

        @media (max-width: 640px) {

            .hr-command-wrap,
            .hr-payroll-wrap {
                padding: .68rem;
            }

            .hr-command-grid,
            .hr-payroll-grid {
                gap: .48rem;
            }

            .hr-command-card,
            .hr-payroll-card {
                min-height: 94px;
                padding: .58rem;
            }

            .hr-command-icon,
            .hr-payroll-icon {
                width: 28px;
                height: 28px;
            }

            .hr-command-title,
            .hr-payroll-title {
                font-size: .62rem;
            }

            .hr-command-subtitle,
            .hr-payroll-subtitle {
                font-size: .5rem;
            }

            .hr-command-value,
            .hr-payroll-value {
                font-size: 1.2rem;
            }

            .hr-payroll-money {
                font-size: .85rem;
            }

            .hr-command-pill,
            .hr-payroll-pill {
                font-size: .48rem;
                padding: .24rem .35rem;
            }

            .hr-mini-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .hr-command-wrap {
                padding: .68rem;
            }

            .hr-command-grid {
                gap: .48rem;
            }

            .hr-command-card {
                min-height: 94px;
                padding: .58rem;
            }

            .hr-command-icon {
                width: 28px;
                height: 28px;
            }

            .hr-command-title {
                font-size: .62rem;
            }

            .hr-command-subtitle {
                font-size: .5rem;
            }

            .hr-command-value {
                font-size: 1.25rem;
            }

            .hr-command-pill {
                font-size: .48rem;
                padding: .24rem .35rem;
            }

            .hr-mini-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 1100px) {
            .farm-live-kpis {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 900px) {
            .farm-user-panel {
                grid-template-columns: 1fr;
            }

            .farm-user-role-card,
            .farm-user-system-card {
                width: 100%;
            }

            .farm-hero-grid {
                grid-template-columns: 1fr;
            }

            .farm-hero {
                padding: 1.15rem;
            }

            .farm-brand {
                font-size: 3rem;
            }

            .farm-live-kpis {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .farm-section {
                padding: .8rem;
            }

            .livestock-summary-wrap,
            .breed-snapshot-wrap,
            .farm-compact-block {
                padding: .68rem;
            }

            .livestock-summary-grid,
            .breed-grid-compact {
                gap: .48rem;
            }

            .livestock-summary-card {
                min-height: 90px;
                padding: .58rem;
            }

            .livestock-summary-icon {
                width: 27px;
                height: 27px;
            }

            .livestock-summary-title {
                font-size: .62rem;
            }

            .livestock-summary-subtitle {
                font-size: .5rem;
            }

            .livestock-summary-value {
                font-size: 1.25rem;
            }

            .livestock-summary-action {
                font-size: .5rem;
                padding: .24rem .36rem;
            }

            .breed-card-compact {
                padding: .52rem;
                min-height: 78px;
            }

            .breed-avatar-compact {
                width: 32px;
                height: 32px;
                flex-basis: 32px;
                font-size: .58rem;
            }

            .breed-card-name {
                font-size: .68rem;
            }

            .breed-card-species {
                font-size: .54rem;
            }

            .breed-mini-pill {
                padding: .24rem .34rem;
                font-size: .5rem;
            }

            .breed-count-number {
                font-size: 1rem;
            }

            .breed-count-label {
                display: none;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Location Snapshot Cards
        |--------------------------------------------------------------------------
        | These cards deliberately reuse the Breed Snapshot grid and dimensions
        | so both dashboard blocks remain visually aligned and responsive.
        */
        .location-card-compact {
            --location-color: {{ $primaryColor }};
        }

        .location-card-compact::before {
            border-left-color: var(--location-color);
            opacity: .95;
        }

        .location-card-compact::after {
            background: color-mix(in srgb, var(--location-color) 10%, transparent);
        }

        .location-card-compact:hover {
            border-color: color-mix(in srgb, var(--location-color) 32%, #e5e7eb);
            box-shadow: 0 14px 34px color-mix(in srgb, var(--location-color) 12%, transparent);
        }

        .location-avatar-compact {
            color: var(--location-color);
            background:
                linear-gradient(
                    135deg,
                    color-mix(in srgb, var(--location-color) 13%, white),
                    #f8fafc
                );
            border-color: color-mix(in srgb, var(--location-color) 20%, #e5e7eb);
        }

        .dark .location-avatar-compact {
            color: color-mix(in srgb, var(--location-color) 55%, white);
            background: color-mix(in srgb, var(--location-color) 15%, #111827);
            border-color: color-mix(in srgb, var(--location-color) 25%, #374151);
        }

        .location-card-meta {
            margin-top: .16rem;
            display: flex;
            align-items: center;
            gap: .25rem;
            min-width: 0;
            color: #6b7280;
            font-size: .56rem;
            font-weight: 800;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dark .location-card-meta {
            color: #9ca3af;
        }

        .location-status-dot {
            width: .38rem;
            height: .38rem;
            flex-shrink: 0;
            border-radius: 999px;
            background: var(--location-color);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--location-color) 13%, transparent);
        }

        .location-mini-pill {
            color: var(--location-color);
            background: color-mix(in srgb, var(--location-color) 9%, white);
            border-color: color-mix(in srgb, var(--location-color) 16%, white);
        }

        .dark .location-mini-pill {
            background: color-mix(in srgb, var(--location-color) 14%, #111827);
            border-color: color-mix(in srgb, var(--location-color) 24%, #374151);
        }

        .location-count-number {
            color: var(--location-color);
        }

        .location-default-badge {
            display: inline-flex;
            align-items: center;
            gap: .2rem;
            margin-left: .3rem;
            padding: .16rem .3rem;
            color: var(--location-color);
            background: color-mix(in srgb, var(--location-color) 9%, white);
            border: 1px solid color-mix(in srgb, var(--location-color) 15%, white);
            font-size: .46rem;
            line-height: 1;
            font-weight: 950;
            vertical-align: middle;
            white-space: nowrap;
        }

        .dark .location-default-badge {
            background: color-mix(in srgb, var(--location-color) 14%, #111827);
            border-color: color-mix(in srgb, var(--location-color) 24%, #374151);
        }

        @media (max-width: 640px) {
            .location-card-meta {
                font-size: .49rem;
            }

            .location-default-badge {
                display: none;
            }
        }



        .location-subsection-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            margin-bottom: .65rem;
        }

        .location-subsection-title-wrap {
            display: flex;
            align-items: center;
            gap: .5rem;
            min-width: 0;
        }

        .location-subsection-icon {
            width: 30px;
            height: 30px;
            flex-shrink: 0;
            display: grid;
            place-items: center;
            color: var(--subsection-color);
            background: color-mix(in srgb, var(--subsection-color) 11%, white);
            border: 1px solid color-mix(in srgb, var(--subsection-color) 20%, white);
        }

        .dark .location-subsection-icon {
            background: color-mix(in srgb, var(--subsection-color) 16%, #111827);
            border-color: color-mix(in srgb, var(--subsection-color) 28%, #374151);
        }

        .location-subsection-title {
            font-size: .78rem;
            line-height: 1.15;
            font-weight: 950;
            color: #111827;
        }

        .dark .location-subsection-title {
            color: #f9fafb;
        }

        .location-subsection-note {
            margin-top: .12rem;
            font-size: .56rem;
            line-height: 1.35;
            color: #6b7280;
        }

        .dark .location-subsection-note {
            color: #9ca3af;
        }

        .location-subsection-count {
            flex-shrink: 0;
            padding: .3rem .48rem;
            color: var(--subsection-color);
            background: color-mix(in srgb, var(--subsection-color) 9%, white);
            border: 1px solid color-mix(in srgb, var(--subsection-color) 16%, white);
            font-size: .55rem;
            line-height: 1;
            font-weight: 950;
            white-space: nowrap;
        }

        .dark .location-subsection-count {
            background: color-mix(in srgb, var(--subsection-color) 14%, #111827);
            border-color: color-mix(in srgb, var(--subsection-color) 22%, #374151);
        }

        .location-group-divider {
            position: relative;
            height: 1px;
            margin: 1rem 0;
            border: 0;
            background: linear-gradient(
                90deg,
                transparent,
                color-mix(in srgb, {{ $primaryColor }} 30%, #d1d5db),
                transparent
            );
        }

        .location-group-divider::after {
            content: '';
            position: absolute;
            left: 50%;
            top: 50%;
            width: 7px;
            height: 7px;
            transform: translate(-50%, -50%) rotate(45deg);
            background: {{ $primaryColor }};
            border: 2px solid #fff;
            box-shadow: 0 0 0 1px color-mix(in srgb, {{ $primaryColor }} 30%, #d1d5db);
        }

        .dark .location-group-divider::after {
            border-color: #111827;
        }

        @media (max-width: 640px) {
            .location-subsection-head {
                align-items: flex-start;
            }

            .location-subsection-icon {
                width: 27px;
                height: 27px;
            }

            .location-subsection-title {
                font-size: .7rem;
            }

            .location-subsection-note {
                font-size: .5rem;
            }

            .location-subsection-count {
                font-size: .49rem;
                padding: .26rem .38rem;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Real Animal Group Cards
        |--------------------------------------------------------------------------
        */
        .animal-group-card {
            position: relative;
            min-width: 0;
            min-height: 112px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: .58rem;
            padding: .65rem;
            border: 1px solid rgba(229, 231, 235, 1);
            background:
                radial-gradient(
                    circle at top right,
                    color-mix(in srgb, var(--group-color) 10%, transparent),
                    transparent 36%
                ),
                linear-gradient(
                    180deg,
                    rgba(255, 255, 255, .99),
                    rgba(255, 255, 255, .94)
                );
            box-shadow: 0 8px 20px rgba(2, 6, 23, .04);
            overflow: hidden;
            color: inherit;
            transition:
                transform .18s ease,
                box-shadow .18s ease,
                border-color .18s ease;
        }

        .animal-group-card::before {
            content: "";
            position: absolute;
            inset: 0;
            border-left: 3px solid var(--group-color);
            pointer-events: none;
        }

        .animal-group-card::after {
            content: "";
            position: absolute;
            width: 96px;
            height: 96px;
            right: -36px;
            top: -42px;
            border-radius: 999px;
            background:
                color-mix(
                    in srgb,
                    var(--group-color) 10%,
                    transparent
                );
            pointer-events: none;
        }

        .animal-group-card:hover {
            transform: translateY(-2px);
            border-color:
                color-mix(
                    in srgb,
                    var(--group-color) 30%,
                    #e5e7eb
                );
            box-shadow: 0 15px 36px rgba(2, 6, 23, .085);
        }

        .dark .animal-group-card {
            background:
                radial-gradient(
                    circle at top right,
                    color-mix(in srgb, var(--group-color) 17%, transparent),
                    transparent 38%
                ),
                rgba(31, 41, 55, .94);
            border-color: rgba(148, 163, 184, .14);
        }

        .animal-group-main,
        .animal-group-footer {
            position: relative;
            z-index: 2;
        }

        .animal-group-main {
            display: flex;
            align-items: flex-start;
            gap: .58rem;
            min-width: 0;
        }

        .animal-group-icon {
            flex: 0 0 38px;
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            color: var(--group-color);
            background:
                color-mix(
                    in srgb,
                    var(--group-color) 12%,
                    white
                );
            border:
                1px solid
                color-mix(
                    in srgb,
                    var(--group-color) 22%,
                    white
                );
        }

        .dark .animal-group-icon {
            background:
                color-mix(
                    in srgb,
                    var(--group-color) 17%,
                    #111827
                );
            border-color:
                color-mix(
                    in srgb,
                    var(--group-color) 27%,
                    #374151
                );
        }

        .animal-group-info {
            min-width: 0;
            flex: 1;
        }

        .animal-group-name {
            font-size: .78rem;
            line-height: 1.18;
            font-weight: 950;
            color: #111827;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dark .animal-group-name {
            color: #f9fafb;
        }

        .animal-group-code {
            display: inline-flex;
            align-items: center;
            margin-top: .2rem;
            padding: .2rem .35rem;
            color: var(--group-color);
            background:
                color-mix(
                    in srgb,
                    var(--group-color) 9%,
                    white
                );
            border:
                1px solid
                color-mix(
                    in srgb,
                    var(--group-color) 15%,
                    white
                );
            font-size: .5rem;
            line-height: 1;
            font-weight: 950;
            white-space: nowrap;
        }

        .animal-group-scope {
            margin-top: .35rem;
            color: #6b7280;
            font-size: .55rem;
            line-height: 1.35;
            font-weight: 750;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            overflow: hidden;
        }

        .dark .animal-group-scope {
            color: #9ca3af;
        }

        .animal-group-count {
            flex-shrink: 0;
            text-align: right;
        }

        .animal-group-count-value {
            font-size: 1.28rem;
            line-height: .9;
            font-weight: 950;
            letter-spacing: -.04em;
            color: var(--group-color);
        }

        .animal-group-count-label {
            margin-top: .1rem;
            color: #9ca3af;
            font-size: .48rem;
            line-height: 1;
            font-weight: 900;
            letter-spacing: .05em;
            text-transform: uppercase;
        }

        .animal-group-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .45rem;
        }

        .animal-group-actions {
            display: flex;
            align-items: center;
            gap: .35rem;
            min-width: 0;
        }

        .animal-group-action {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            padding: .28rem .42rem;
            color: var(--group-color);
            background:
                color-mix(
                    in srgb,
                    var(--group-color) 9%,
                    white
                );
            border:
                1px solid
                color-mix(
                    in srgb,
                    var(--group-color) 16%,
                    white
                );
            font-size: .52rem;
            line-height: 1;
            font-weight: 950;
            text-decoration: none;
            white-space: nowrap;
        }

        .animal-group-action:hover {
            background:
                color-mix(
                    in srgb,
                    var(--group-color) 15%,
                    white
                );
        }

        .animal-group-action-secondary {
            color: #64748b;
            background: rgba(248, 250, 252, .95);
            border-color: rgba(203, 213, 225, .9);
        }

        .dark .animal-group-action {
            background:
                color-mix(
                    in srgb,
                    var(--group-color) 15%,
                    #111827
                );
            border-color:
                color-mix(
                    in srgb,
                    var(--group-color) 24%,
                    #374151
                );
        }

        .dark .animal-group-action-secondary {
            color: #cbd5e1;
            background: rgba(15, 23, 42, .8);
            border-color: rgba(148, 163, 184, .2);
        }

        .animal-group-type {
            display: inline-flex;
            align-items: center;
            gap: .22rem;
            max-width: 100%;
            margin-top: .28rem;
            padding: .22rem .34rem;
            color: var(--group-color);
            background:
                color-mix(
                    in srgb,
                    var(--group-color) 8%,
                    white
                );
            border:
                1px solid
                color-mix(
                    in srgb,
                    var(--group-color) 14%,
                    white
                );
            font-size: .49rem;
            line-height: 1;
            font-weight: 950;
            text-transform: uppercase;
            letter-spacing: .04em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .animal-group-type span {
            color: #64748b;
        }

        .dark .animal-group-type {
            color:
                color-mix(
                    in srgb,
                    var(--group-color) 62%,
                    white
                );
            background:
                color-mix(
                    in srgb,
                    var(--group-color) 14%,
                    #111827
                );
            border-color:
                color-mix(
                    in srgb,
                    var(--group-color) 22%,
                    #374151
                );
        }

        .dark .animal-group-type span {
            color: #cbd5e1;
        }

        @media (max-width: 640px) {
            .animal-group-card {
                min-height: 104px;
                padding: .56rem;
            }

            .animal-group-icon {
                width: 33px;
                height: 33px;
                flex-basis: 33px;
            }

            .animal-group-name {
                font-size: .68rem;
            }

            .animal-group-scope {
                font-size: .49rem;
            }

            .animal-group-count-value {
                font-size: 1.05rem;
            }

            .animal-group-action {
                padding: .24rem .34rem;
                font-size: .47rem;
            }

            .animal-group-type {
                max-width: 100%;
                margin-top: .24rem;
                padding: .2rem .3rem;
                font-size: .43rem;
            }
        }

    </style>

    <div class="farm-dashboard">
        <section class="farm-user-panel farm-shell-card">
            <div class="farm-user-main">
                <div class="farm-user-avatar">
                    {{ $userInitial }}
                </div>

                <div class="min-w-0">
                    <div class="farm-muted-label">Logged in as</div>
                    <div class="farm-user-name">{{ $userName }}</div>
                </div>
            </div>

            <div class="farm-user-role-card">
                <div class="farm-live-signal">
                    <span class="signal-core"></span>
                    <span class="signal-wave signal-wave-1"></span>
                    <span class="signal-wave signal-wave-2"></span>
                </div>

                <div>
                    <div class="farm-muted-label">Active ERP Role</div>
                    <div class="farm-user-role">{{ $userRoles }}</div>
                </div>
            </div>

            <div class="farm-user-system-card">
                <x-heroicon-o-shield-check class="h-5 w-5" style="color: {{ $primaryColor }}" />
                <div>
                    <div class="farm-muted-label">System Status</div>
                    <div class="farm-user-role">Live Control</div>
                </div>
            </div>
        </section>

        <section class="farm-hero">
            <div class="farm-hero-grid">
                <div>
                    <div class="farm-kicker">
                        <x-heroicon-o-sparkles class="h-4 w-4" />
                        Farm intelligence system
                    </div>

                    <h1 class="farm-brand">{{ $farmName }}</h1>

                    <div class="farm-title">
                        {{ $farmTagline }}
                        <small>
                            Real-time livestock visibility, HR control, operational reporting, and executive decision
                            support.
                        </small>
                    </div>

                    <div class="farm-pills">
                        <div class="farm-pill"><x-heroicon-o-chart-bar class="h-4 w-4" /> Breed performance</div>
                        <div class="farm-pill"><x-heroicon-o-archive-box class="h-4 w-4" /> Lifecycle tracking</div>
                        <div class="farm-pill"><x-heroicon-o-users class="h-4 w-4" /> Workforce intelligence</div>
                        <div class="farm-pill"><x-heroicon-o-bolt class="h-4 w-4" /> Executive control</div>
                    </div>
                </div>

                <div class="farm-hero-side">
                    <div class="farm-clock-card">
                        <div class="farm-hero-mini-label">Today</div>
                        <div class="farm-hero-mini-value">{{ $today }}</div>

                        <div class="analog-clock" id="farmAnalogClock">
                            <span class="clock-number number-12">12</span>
                            <span class="clock-number number-1">1</span>
                            <span class="clock-number number-2">2</span>
                            <span class="clock-number number-3">3</span>
                            <span class="clock-number number-4">4</span>
                            <span class="clock-number number-5">5</span>
                            <span class="clock-number number-6">6</span>
                            <span class="clock-number number-7">7</span>
                            <span class="clock-number number-8">8</span>
                            <span class="clock-number number-9">9</span>
                            <span class="clock-number number-10">10</span>
                            <span class="clock-number number-11">11</span>

                            <span class="hand hour-hand" id="farmHourHand"></span>
                            <span class="hand minute-hand" id="farmMinuteHand"></span>
                            <span class="hand second-hand" id="farmSecondHand"></span>
                            <span class="clock-dot"></span>
                        </div>

                        <div class="clock-caption">East Africa Time</div>
                    </div>
                </div>
            </div>
        </section>

        @can('view animals')
            <section class="farm-section">
                <div class="farm-section-head">
                    <div>
                        <div class="farm-section-kicker">
                            <x-heroicon-o-cube-transparent class="h-4 w-4" />
                            Animal records
                        </div>

                        <div class="farm-section-title">Livestock Control Overview</div>

                        <div class="farm-section-subtitle">
                            Track active stock, lifecycle status, breed distribution, and breeding records from one
                            operational layer.
                        </div>
                    </div>

                    <div class="farm-section-badge">
                        <x-heroicon-o-sparkles class="h-4 w-4" />
                        Live livestock view
                    </div>
                </div>

                @php
                    $animalBaseUrl = url('/admin/animals/current-animals');

                    $animalCounts = DB::table('animals')
                        ->selectRaw(
                            "
                            COALESCE(SUM(CASE WHEN status = 'Active' AND COALESCE(is_archived, 0) = 0 THEN 1 ELSE 0 END), 0) as active_count,
                            COALESCE(SUM(CASE WHEN status = 'Sold' THEN 1 ELSE 0 END), 0) as sold_count,
                            COALESCE(SUM(CASE WHEN status = 'Dead' THEN 1 ELSE 0 END), 0) as dead_count,
                            COALESCE(SUM(CASE WHEN status = 'Culled' THEN 1 ELSE 0 END), 0) as culled_count,
                            COALESCE(SUM(CASE WHEN COALESCE(is_archived, 0) = 1 THEN 1 ELSE 0 END), 0) as archived_count,
                            COALESCE(SUM(CASE WHEN COALESCE(is_breeder, 0) = 1 AND status = 'Active' AND COALESCE(is_archived, 0) = 0 THEN 1 ELSE 0 END), 0) as breeder_count
                        ",
                        )
                        ->first();

                    $livestockMetrics = [
                        [
                            'title' => 'Current',
                            'subtitle' => 'Active records',
                            'value' => (int) ($animalCounts->active_count ?? 0),
                            'icon' => 'heroicon-o-check-badge',
                            'color' => $successColor,
                            'url' => $animalBaseUrl,
                        ],
                        [
                            'title' => 'Sold',
                            'subtitle' => 'Sales disposal',
                            'value' => (int) ($animalCounts->sold_count ?? 0),
                            'icon' => 'heroicon-o-banknotes',
                            'color' => '#f59e0b',
                            'url' => $animalBaseUrl,
                        ],
                        [
                            'title' => 'Mortality',
                            'subtitle' => 'Dead records',
                            'value' => (int) ($animalCounts->dead_count ?? 0),
                            'icon' => 'heroicon-o-exclamation-triangle',
                            'color' => $dangerColor,
                            'url' => $animalBaseUrl,
                        ],
                        [
                            'title' => 'Culled',
                            'subtitle' => 'Removed stock',
                            'value' => (int) ($animalCounts->culled_count ?? 0),
                            'icon' => 'heroicon-o-no-symbol',
                            'color' => '#64748b',
                            'url' => $animalBaseUrl,
                        ],
                        [
                            'title' => 'Archived',
                            'subtitle' => 'Moved aside',
                            'value' => (int) ($animalCounts->archived_count ?? 0),
                            'icon' => 'heroicon-o-archive-box',
                            'color' => '#2563eb',
                            'url' => $animalBaseUrl,
                        ],
                        [
                            'title' => 'Breeders',
                            'subtitle' => 'Breeding pool',
                            'value' => (int) ($animalCounts->breeder_count ?? 0),
                            'icon' => 'heroicon-o-sparkles',
                            'color' => '#16a34a',
                            'url' => $animalBaseUrl,
                        ],
                    ];
                @endphp

                <div class="farm-widget-row">
                    <div class="livestock-summary-wrap">
                        <div class="livestock-summary-grid">
                            @foreach ($livestockMetrics as $metric)
                                <a href="{{ $metric['url'] }}"
                                    class="livestock-summary-card {{ $metric['value'] === 0 ? 'livestock-summary-card-muted' : '' }}"
                                    style="--metric-color: {{ $metric['color'] }};">
                                    <div class="livestock-summary-top">
                                        <div class="min-w-0">
                                            <div class="livestock-summary-title">{{ $metric['title'] }}</div>
                                            <div class="livestock-summary-subtitle">{{ $metric['subtitle'] }}</div>
                                        </div>

                                        <div class="livestock-summary-icon">
                                            <x-dynamic-component :component="$metric['icon']" class="h-4 w-4" />
                                        </div>
                                    </div>

                                    <div class="livestock-summary-bottom">
                                        <div class="livestock-summary-value">
                                            {{ number_format($metric['value']) }}
                                        </div>

                                        <div class="livestock-summary-action">
                                            View
                                            <x-heroicon-o-arrow-right class="h-3 w-3" />
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                @php
                    $breedCards = DB::table('breeds')
                        ->leftJoin('animals', 'animals.breed_id', '=', 'breeds.id')
                        ->select([
                            'breeds.id',
                            'breeds.breed_name',
                            'breeds.parent_category',
                            'breeds.avatar',
                            'breeds.prefix',
                            DB::raw("
                                COALESCE(SUM(
                                    CASE
                                        WHEN animals.id IS NOT NULL
                                        AND animals.status = 'Active'
                                        AND COALESCE(animals.is_archived, 0) = 0
                                        THEN 1
                                        ELSE 0
                                    END
                                ), 0) as active_animals_count
                            "),
                        ])
                        ->groupBy(
                            'breeds.id',
                            'breeds.breed_name',
                            'breeds.parent_category',
                            'breeds.avatar',
                            'breeds.prefix',
                        )
                        ->orderBy('breeds.parent_category')
                        ->orderBy('breeds.breed_name')
                        ->get();

                    $totalBreedAnimals = (int) $breedCards->sum('active_animals_count');
                @endphp

                <div class="farm-widget-row">
                    <div class="breed-snapshot-wrap">
                        <div class="breed-snapshot-head">
                            <div>
                                <div class="breed-snapshot-kicker">
                                    <x-heroicon-o-squares-2x2 class="h-4 w-4" />
                                    Breed distribution
                                </div>

                                <div class="breed-snapshot-title">Breed Snapshot</div>

                                <div class="breed-snapshot-subtitle">
                                    Compact active livestock distribution by breed and species. Click any breed to open its
                                    record.
                                </div>
                            </div>

                            <div class="breed-snapshot-badge">
                                <x-heroicon-o-chart-bar-square class="h-4 w-4" />
                                {{ number_format($totalBreedAnimals) }} active
                            </div>
                        </div>

                        @if ($breedCards->isNotEmpty())
                            <div class="breed-grid-compact">
                                @foreach ($breedCards as $breed)
                                    @php
                                        $breedName = $breed->breed_name ?? 'Unknown Breed';
                                        $species = $breed->parent_category ?? 'Livestock';
                                        $speciesKey = strtolower(str_replace([' ', '/', '\\'], '-', $species));
                                        $count = (int) $breed->active_animals_count;

                                        $initials = collect(explode(' ', trim($breedName)))
                                            ->filter()
                                            ->map(fn($word) => mb_strtoupper(mb_substr($word, 0, 1)))
                                            ->take(2)
                                            ->implode('');

                                        $avatar = null;

                                        if (!empty($breed->avatar)) {
                                            $avatarPath = ltrim($breed->avatar, '/');
                                            $avatarPath = preg_replace('#^storage/#', '', $avatarPath);
                                            $avatar = asset('storage/' . $avatarPath);
                                        }

                                        //  $breedUrl = url('/admin/breeds/' . $breed->id . '/edit');
                                        $breedUrl = \App\Filament\Resources\AnimalResource::getUrl('index', [
                                            'tableFilters' => [
                                                'breed_id' => [
                                                    'value' => (string) $breed->id,
                                                ],
                                            ],
                                        ]);
                                    @endphp

                                    <a href="{{ $breedUrl }}" class="breed-card-compact"
                                        title="Open {{ $breedName }}">
                                        <div class="breed-card-main">
                                            <div class="breed-avatar-compact">
                                                @if ($avatar)
                                                    <img src="{{ $avatar }}" alt="{{ $breedName }}">
                                                @else
                                                    {{ $initials ?: ($breed->prefix ?: 'BR') }}
                                                @endif
                                            </div>

                                            <div class="breed-card-info">
                                                <div class="breed-card-name" title="{{ $breedName }}">
                                                    {{ $breedName }}
                                                </div>

                                                <div class="breed-card-species species-{{ $speciesKey }}">
                                                    <span class="species-dot"></span>
                                                    {{ $species }}
                                                </div>
                                            </div>
                                        </div>

                                        <div class="breed-card-bottom">
                                            <div class="breed-mini-pill">
                                                <x-heroicon-o-arrow-top-right-on-square class="h-3 w-3" />
                                                Open
                                            </div>

                                            <div class="breed-count-box">
                                                <div class="breed-count-number {{ $count === 0 ? 'is-zero' : '' }}">
                                                    {{ number_format($count) }}
                                                </div>
                                                <div class="breed-count-label">Active</div>
                                            </div>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <div class="breed-empty-state">
                                No breed records found.
                            </div>
                        @endif
                    </div>
                </div>


                @php
                    /*
                     * Physical locations come from locations/current_location_id.
                     * Animal groups are independent records from animal_groups,
                     * with membership counted through activeMembers.
                     */
                    $locationCards = DB::table('locations')
                        ->leftJoin(
                            'animals',
                            'animals.current_location_id',
                            '=',
                            'locations.id'
                        )
                        ->where('locations.is_active', true)
                        ->select([
                            'locations.id',
                            'locations.name',
                            'locations.is_default',
                            DB::raw("
                                COALESCE(SUM(
                                    CASE
                                        WHEN animals.id IS NOT NULL
                                        AND animals.status = 'Active'
                                        AND COALESCE(animals.is_archived, 0) = 0
                                        THEN 1
                                        ELSE 0
                                    END
                                ), 0) as active_animals_count
                            "),
                            DB::raw("
                                COALESCE(SUM(
                                    CASE
                                        WHEN animals.id IS NOT NULL
                                        AND animals.status = 'Active'
                                        AND COALESCE(animals.is_archived, 0) = 0
                                        AND animals.sex = 'Male'
                                        THEN 1
                                        ELSE 0
                                    END
                                ), 0) as male_animals_count
                            "),
                            DB::raw("
                                COALESCE(SUM(
                                    CASE
                                        WHEN animals.id IS NOT NULL
                                        AND animals.status = 'Active'
                                        AND COALESCE(animals.is_archived, 0) = 0
                                        AND animals.sex = 'Female'
                                        THEN 1
                                        ELSE 0
                                    END
                                ), 0) as female_animals_count
                            "),
                        ])
                        ->groupBy(
                            'locations.id',
                            'locations.name',
                            'locations.is_default',
                        )
                        ->orderByDesc('locations.is_default')
                        ->orderByDesc('active_animals_count')
                        ->orderBy('locations.name')
                        ->get();

                    $groupCards = \App\Models\AnimalGroup::query()
                        ->with([
                            'location:id,name',
                            'breed:id,breed_name',
                        ])
                        ->withCount('activeMembers')
                        ->where('status', 'active')
                        ->orderByDesc('active_members_count')
                        ->orderBy('name')
                        ->get();

                    $totalLocationAnimals = (int) $locationCards
                        ->sum('active_animals_count');

                    $totalGroupAnimals = (int) $groupCards
                        ->sum('active_members_count');

                    $occupiedLocations = $locationCards
                        ->filter(
                            fn ($record): bool =>
                                (int) $record->active_animals_count > 0
                        )
                        ->count();

                    $occupiedGroups = $groupCards
                        ->filter(
                            fn ($record): bool =>
                                (int) $record->active_members_count > 0
                        )
                        ->count();

                    $locationColors = [
                        '#2563eb',
                        '#16a34a',
                        '#f59e0b',
                        '#7c3aed',
                        '#0891b2',
                        '#dc2626',
                        '#0f766e',
                        '#9333ea',
                    ];
                @endphp

                <div class="farm-widget-row">
                    <div class="breed-snapshot-wrap location-snapshot-wrap">
                        <div class="breed-snapshot-head">
                            <div>
                                <div class="breed-snapshot-kicker">
                                    <x-heroicon-o-map class="h-4 w-4" />
                                    Housing and management distribution
                                </div>

                                <div class="breed-snapshot-title">
                                    Location & Group Snapshot
                                </div>

                                <div class="breed-snapshot-subtitle">
                                    Physical locations and management groups are
                                    presented separately. Open a card to view the
                                    matching active animals.
                                </div>
                            </div>

                            <div class="breed-snapshot-badge">
                                <x-heroicon-o-chart-bar-square class="h-4 w-4" />
                                {{ number_format($totalLocationAnimals) }} located
                                · {{ number_format($totalGroupAnimals) }} grouped
                            </div>
                        </div>

                        <div
                            class="location-subsection-head"
                            style="--subsection-color: #2563eb;"
                        >
                            <div class="location-subsection-title-wrap">
                                <div class="location-subsection-icon">
                                    <x-heroicon-o-map-pin class="h-4 w-4" />
                                </div>

                                <div>
                                    <div class="location-subsection-title">
                                        Locations
                                    </div>

                                    <div class="location-subsection-note">
                                        Physical stations, sheds, blocks, paddocks,
                                        rooms, pens, and operational areas.
                                    </div>
                                </div>
                            </div>

                            <div class="location-subsection-count">
                                {{ number_format($occupiedLocations) }} occupied
                                · {{ number_format($totalLocationAnimals) }} animals
                            </div>
                        </div>

                        @if ($locationCards->isNotEmpty())
                            <div class="breed-grid-compact">
                                @foreach ($locationCards as $location)
                                    @php
                                        $locationName = $location->name
                                            ?: 'Unnamed Location';

                                        $locationCount = (int)
                                            $location->active_animals_count;

                                        $maleCount = (int)
                                            $location->male_animals_count;

                                        $femaleCount = (int)
                                            $location->female_animals_count;

                                        $locationColor = $locationColors[
                                            $loop->index
                                            % count($locationColors)
                                        ];

                                        $locationUrl =
                                            \App\Filament\Resources\AnimalResource::getUrl(
                                                'index',
                                                [
                                                    'tableFilters' => [
                                                        'current_location_id' => [
                                                            'value' => (string)
                                                                $location->id,
                                                        ],
                                                    ],
                                                ]
                                            );
                                    @endphp

                                    <a
                                        href="{{ $locationUrl }}"
                                        class="breed-card-compact location-card-compact"
                                        style="--location-color: {{ $locationColor }};"
                                        title="View animals at {{ $locationName }}"
                                    >
                                        <div class="breed-card-main">
                                            <div class="breed-avatar-compact location-avatar-compact">
                                                <x-heroicon-o-map-pin class="h-4 w-4" />
                                            </div>

                                            <div class="breed-card-info">
                                                <div
                                                    class="breed-card-name"
                                                    title="{{ $locationName }}"
                                                >
                                                    {{ $locationName }}

                                                    @if ((bool) $location->is_default)
                                                        <span class="location-default-badge">
                                                            Default
                                                        </span>
                                                    @endif
                                                </div>

                                                <div class="location-card-meta">
                                                    <span class="location-status-dot"></span>
                                                    <span>
                                                        {{ number_format($maleCount) }}
                                                        male ·
                                                        {{ number_format($femaleCount) }}
                                                        female
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="breed-card-bottom">
                                            <div class="breed-mini-pill location-mini-pill">
                                                <x-heroicon-o-arrow-top-right-on-square class="h-3 w-3" />
                                                View animals
                                            </div>

                                            <div class="breed-count-box">
                                                <div
                                                    class="breed-count-number location-count-number {{ $locationCount === 0 ? 'is-zero' : '' }}"
                                                >
                                                    {{ number_format($locationCount) }}
                                                </div>

                                                <div class="breed-count-label">
                                                    Animals
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <div class="breed-empty-state">
                                No active physical locations found.
                            </div>
                        @endif

                        <hr class="location-group-divider">

                        <div
                            class="location-subsection-head"
                            style="--subsection-color: #7c3aed;"
                        >
                            <div class="location-subsection-title-wrap">
                                <div class="location-subsection-icon">
                                    <x-heroicon-o-user-group class="h-4 w-4" />
                                </div>

                                <div>
                                    <div class="location-subsection-title">
                                        Animal Groups
                                    </div>

                                    <div class="location-subsection-note">
                                        Real manual and smart groups from the
                                        Animal Groups module, including breeding,
                                        feeding, sales, health, and location groups.
                                    </div>
                                </div>
                            </div>

                            <div class="location-subsection-count">
                                {{ number_format($occupiedGroups) }} occupied
                                · {{ number_format($totalGroupAnimals) }} memberships
                            </div>
                        </div>

                        @if ($groupCards->isNotEmpty())
                            <div class="breed-grid-compact">
                                @foreach ($groupCards as $group)
                                    @php
                                        $groupName = $group->name
                                            ?: 'Unnamed Animal Group';

                                        $groupCode = $group->group_code
                                            ?: 'GROUP-' . $group->id;

                                        $groupCount = (int)
                                            $group->active_members_count;

                                        $groupColor = $locationColors[
                                            ($loop->index + 3)
                                            % count($locationColors)
                                        ];

                                        $groupTypeLabel = match (
                                            $group->group_type
                                        ) {
                                            'manual' => 'Manual',
                                            'dynamic' => 'Smart Dynamic',
                                            'breeding' => 'Breeding',
                                            'sales' => 'Sales',
                                            'health' => 'Health',
                                            'feeding' => 'Feeding',
                                            'location' => 'Location',
                                            default => str($group->group_type)
                                                ->replace('_', ' ')
                                                ->title()
                                                ->toString(),
                                        };

                                        $scopeParts = collect([
                                            $group->location?->name
                                                ? 'Location: '
                                                    . $group->location->name
                                                : null,
                                            $group->breed?->breed_name
                                                ? 'Breed: '
                                                    . $group->breed->breed_name
                                                : null,
                                            $group->sex
                                                ? 'Sex: ' . $group->sex
                                                : null,
                                        ])->filter();

                                        $groupScope = $scopeParts->isNotEmpty()
                                            ? $scopeParts->implode(' · ')
                                            : (
                                                filled($group->purpose)
                                                    ? $group->purpose
                                                    : 'All active animals'
                                            );

                                        $groupAnimalsUrl =
                                            \App\Filament\Resources\AnimalResource::getUrl(
                                                'index',
                                                [
                                                    'tableFilters' => [
                                                        'animal_group_id' => [
                                                            'value' => (string)
                                                                $group->id,
                                                        ],
                                                    ],
                                                ]
                                            );

                                        $groupManageUrl =
                                            \App\Filament\Resources\AnimalGroupResource::getUrl(
                                                'edit',
                                                [
                                                    'record' => $group,
                                                ]
                                            );
                                    @endphp

                                    <div
                                        class="animal-group-card"
                                        style="--group-color: {{ $groupColor }};"
                                    >
                                        <div class="animal-group-main">
                                            <div class="animal-group-icon">
                                                @if ($group->auto_sync)
                                                    <x-heroicon-o-arrow-path class="h-4 w-4" />
                                                @else
                                                    <x-heroicon-o-user-group class="h-4 w-4" />
                                                @endif
                                            </div>

                                            <div class="animal-group-info">
                                                <div
                                                    class="animal-group-name"
                                                    title="{{ $groupName }}"
                                                >
                                                    {{ $groupName }}
                                                </div>

                                                <div class="animal-group-code">
                                                    {{ $groupCode }}
                                                </div>

                                                <div
                                                    class="animal-group-scope"
                                                    title="{{ $groupScope }}"
                                                >
                                                    {{ $groupScope }}
                                                </div>

                                                <div
                                                    class="animal-group-type"
                                                    title="{{ $groupTypeLabel }}"
                                                >
                                                    {{ $groupTypeLabel }}

                                                    @if ($group->auto_sync)
                                                        <span>· Auto</span>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="animal-group-count">
                                                <div class="animal-group-count-value">
                                                    {{ number_format($groupCount) }}
                                                </div>

                                                <div class="animal-group-count-label">
                                                    Animals
                                                </div>
                                            </div>
                                        </div>

                                        <div class="animal-group-footer">
                                            <div class="animal-group-actions">
                                                <a
                                                    href="{{ $groupAnimalsUrl }}"
                                                    class="animal-group-action"
                                                    title="View animals in {{ $groupName }}"
                                                >
                                                    <x-heroicon-o-eye class="h-3 w-3" />
                                                    View animals
                                                </a>

                                                @can('edit animal groups')
                                                    <a
                                                        href="{{ $groupManageUrl }}"
                                                        class="animal-group-action animal-group-action-secondary"
                                                        title="Manage {{ $groupName }}"
                                                    >
                                                        <x-heroicon-o-pencil-square class="h-3 w-3" />
                                                        Manage
                                                    </a>
                                                @endcan
                                            </div>

                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="breed-empty-state">
                                No active animal groups found. Create a group in
                                Livestock → Group(s), then add or auto-sync its
                                animal members.
                            </div>
                        @endif
                    </div>
                </div>

                <div class="farm-widget-row">
                    <x-breeding-performance-dashboard />
                </div>

                <div class="farm-widget-row">
                    <div class="farm-chart-grid">
                        <x-filament-widgets::widgets :columns="1" :data="[
                            ...property_exists($this, 'filters') ? ['filters' => $this->filters] : [],
                            ...$this->getWidgetData(),
                        ]" :widgets="[App\Filament\Widgets\Dashboard\AnimalBreedPieChartWidget::class]" />

                        <x-filament-widgets::widgets :columns="1" :data="[
                            ...property_exists($this, 'filters') ? ['filters' => $this->filters] : [],
                            ...$this->getWidgetData(),
                        ]" :widgets="[App\Filament\Widgets\Dashboard\AnimalStatusChartWidget::class]" />
                    </div>
                </div>
            </section>
        @endcan

        @can('view weight records')
            <section class="farm-section">
                <div class="farm-section-head">
                    <div>
                        <div class="farm-section-kicker">
                            <x-heroicon-o-scale class="h-4 w-4" />
                            Animal Weight
                        </div>

                        <div class="farm-section-title">Breed Weight Intelligence</div>

                        <div class="farm-section-subtitle">
                            Compare breed performance using weight trends and current averages.
                        </div>
                    </div>

                    <div class="farm-weight-actions">
                        <div class="farm-section-badge">
                            <x-heroicon-o-chart-bar class="h-4 w-4" />
                            Live Trends
                        </div>

                        @can('export weight records')
                            <a href="{{ route('reports.breed-weight-report') }}" target="_blank"
                                onclick="
                                    const icon = this.querySelector('[data-pdf-icon]');
                                    const spinner = this.querySelector('[data-pdf-spinner]');
                                    const text = this.querySelector('[data-pdf-text]');

                                    icon.classList.add('hidden');
                                    spinner.classList.remove('hidden');
                                    text.innerText = 'Generating...';

                                    setTimeout(() => {
                                        icon.classList.remove('hidden');
                                        spinner.classList.add('hidden');
                                        text.innerText = 'Print PDF';
                                    }, 6000);
                                "
                                class="farm-pdf-btn">
                                <x-heroicon-o-printer data-pdf-icon class="h-4 w-4" />

                                <svg data-pdf-spinner class="hidden h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z">
                                    </path>
                                </svg>

                                <span data-pdf-text>Print PDF</span>
                            </a>
                        @endcan
                    </div>
                </div>

                <div class="farm-widget-row farm-compact-block">
                    <div class="farm-mini-section-head">
                        <div>
                            <div class="farm-mini-kicker">
                                <x-heroicon-o-chart-bar-square class="h-4 w-4" />
                                Weight progression
                            </div>

                            <div class="farm-mini-title">Breed Weight Trends</div>
                            <div class="farm-mini-subtitle">Average weight progression per breed.</div>
                        </div>
                    </div>

                    <x-filament-widgets::widgets :columns="1" :widgets="[App\Filament\Widgets\BreedWeightTrendChartWidget::class]" />
                </div>

                <div class="farm-widget-row farm-compact-block">
                    <div class="farm-mini-section-head">
                        <div>
                            <div class="farm-mini-kicker">
                                <x-heroicon-o-scale class="h-4 w-4" />
                                Current averages
                            </div>

                            <div class="farm-mini-title">Current Breed Average Weight</div>
                            <div class="farm-mini-subtitle">Latest average weight per breed.</div>
                        </div>
                    </div>

                    <x-filament-widgets::widgets :columns="1" :widgets="[App\Filament\Widgets\BreedCurrentAverageWeightChartWidget::class]" />
                </div>
            </section>
        @endcan

        @can('view hr dashboard')
            <x-hr-dashboard-panel mode="compact" />
        @endcan
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const hourHand = document.getElementById('farmHourHand');
            const minuteHand = document.getElementById('farmMinuteHand');
            const secondHand = document.getElementById('farmSecondHand');

            if (!hourHand || !minuteHand || !secondHand) return;

            function getEastAfricaTime() {
                const formatter = new Intl.DateTimeFormat('en-GB', {
                    timeZone: 'Africa/Nairobi',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false,
                });

                const parts = formatter.formatToParts(new Date());

                return {
                    hours: Number(parts.find(part => part.type === 'hour').value),
                    minutes: Number(parts.find(part => part.type === 'minute').value),
                    seconds: Number(parts.find(part => part.type === 'second').value),
                };
            }

            function updateFarmAnalogClock() {
                const time = getEastAfricaTime();

                const secondDeg = time.seconds * 6;
                const minuteDeg = (time.minutes * 6) + (time.seconds * 0.1);
                const hourDeg = ((time.hours % 12) * 30) + (time.minutes * 0.5);

                secondHand.style.transform = `translateX(-50%) rotate(${secondDeg}deg)`;
                minuteHand.style.transform = `translateX(-50%) rotate(${minuteDeg}deg)`;
                hourHand.style.transform = `translateX(-50%) rotate(${hourDeg}deg)`;
            }

            updateFarmAnalogClock();
            setInterval(updateFarmAnalogClock, 1000);
        });
    </script>
</x-filament-panels::page>
