<?php if (isset($component)) { $__componentOriginal166a02a7c5ef5a9331faf66fa665c256 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal166a02a7c5ef5a9331faf66fa665c256 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament-panels::components.page.index','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament-panels::page'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <?php
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
    ?>

    <style>
        @font-face {
            font-family: 'ChopinScriptDashboard';
            src: url('/fonts/ChopinScript.ttf?v=<?php echo e(time()); ?>') format('truetype');
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
                radial-gradient(circle at top right, color-mix(in srgb, <?php echo e($primaryColor); ?> 7%, transparent), transparent 30%),
                linear-gradient(180deg, rgba(255, 255, 255, .98), rgba(249, 250, 251, .94));
            box-shadow: 0 16px 40px rgba(2, 6, 23, .055);
        }

        .dark .farm-shell-card {
            background:
                radial-gradient(circle at top right, color-mix(in srgb, <?php echo e($primaryColor); ?> 18%, transparent), transparent 32%),
                linear-gradient(180deg, rgba(17, 24, 39, .96), rgba(15, 23, 42, .94));
            border-color: rgba(148, 163, 184, .14);
        }

        .farm-user-panel {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto auto;
            gap: .85rem;
            align-items: center;
            padding: .85rem .95rem;
            border-left: 4px solid <?php echo e($successColor); ?>;
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
                linear-gradient(135deg, <?php echo e($primaryColor); ?>, <?php echo e($secondaryColor); ?>);
            border: 2px solid rgba(255, 255, 255, .92);
            box-shadow: 0 10px 24px color-mix(in srgb, <?php echo e($primaryColor); ?> 28%, transparent);
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
            background: color-mix(in srgb, <?php echo e($primaryColor); ?> 7%, white);
            border: 1px solid color-mix(in srgb, <?php echo e($primaryColor); ?> 14%, white);
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
            color: <?php echo e($primaryColor); ?>;
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
            background: <?php echo e($successColor); ?>;
            box-shadow: 0 0 0 5px color-mix(in srgb, <?php echo e($successColor); ?> 18%, transparent);
            z-index: 3;
        }

        .signal-wave {
            position: absolute;
            border: 2px solid <?php echo e($successColor); ?>;
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
                radial-gradient(circle at bottom left, <?php echo e($accentColor); ?>40, transparent 25%),
                linear-gradient(135deg, <?php echo e($primaryColor); ?> 0%, <?php echo e($secondaryColor); ?> 55%, #052e16 100%);
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
            border-left: 4px solid <?php echo e($accentColor); ?>;
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
            background: <?php echo e($accentColor); ?>;
        }

        .clock-dot {
            position: absolute;
            width: 9px;
            height: 9px;
            background: <?php echo e($accentColor); ?>;
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
            color: <?php echo e($primaryColor); ?>;
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
            background: color-mix(in srgb, <?php echo e($primaryColor); ?> 8%, white);
            color: <?php echo e($primaryColor); ?>;
            border: 1px solid color-mix(in srgb, <?php echo e($primaryColor); ?> 18%, white);
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
            border-color: color-mix(in srgb, <?php echo e($primaryColor); ?> 22%, white) !important;
            box-shadow: 0 18px 45px rgba(2, 6, 23, .08) !important;
        }

        .farm-dashboard canvas {
            max-height: 330px !important;
        }

        .livestock-summary-wrap,
        .breed-snapshot-wrap,
        .farm-compact-block {
            border: 1px solid color-mix(in srgb, <?php echo e($primaryColor); ?> 14%, #e5e7eb);
            background:
                radial-gradient(circle at top right, color-mix(in srgb, <?php echo e($primaryColor); ?> 7%, transparent), transparent 28%),
                linear-gradient(180deg, rgba(255, 255, 255, .98), rgba(249, 250, 251, .94));
            box-shadow: 0 14px 36px rgba(2, 6, 23, .05);
            padding: .85rem;
            overflow: hidden;
        }

        .dark .livestock-summary-wrap,
        .dark .breed-snapshot-wrap,
        .dark .farm-compact-block {
            background:
                radial-gradient(circle at top right, color-mix(in srgb, <?php echo e($primaryColor); ?> 16%, transparent), transparent 30%),
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
            color: <?php echo e($primaryColor); ?>;
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
            background: color-mix(in srgb, <?php echo e($primaryColor); ?> 8%, white);
            color: <?php echo e($primaryColor); ?>;
            border: 1px solid color-mix(in srgb, <?php echo e($primaryColor); ?> 18%, white);
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
            border-left: 3px solid <?php echo e($primaryColor); ?>;
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
            background: color-mix(in srgb, <?php echo e($primaryColor); ?> 8%, transparent);
            pointer-events: none;
        }

        .breed-card-compact:hover {
            transform: translateY(-2px);
            border-color: color-mix(in srgb, <?php echo e($primaryColor); ?> 28%, #e5e7eb);
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
            background: linear-gradient(135deg, color-mix(in srgb, <?php echo e($primaryColor); ?> 10%, white), #f8fafc);
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
            color: <?php echo e($primaryColor); ?>;
            background: color-mix(in srgb, <?php echo e($primaryColor); ?> 9%, white);
            border: 1px solid color-mix(in srgb, <?php echo e($primaryColor); ?> 14%, white);
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
            background: <?php echo e($primaryColor); ?>;
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
            background: linear-gradient(135deg, <?php echo e($primaryColor); ?>, <?php echo e($secondaryColor); ?>);
            box-shadow: 0 10px 24px color-mix(in srgb, <?php echo e($primaryColor); ?> 22%, transparent);
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
            border-left: 3px solid <?php echo e($primaryColor); ?>;
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
            border: 1px solid color-mix(in srgb, <?php echo e($primaryColor); ?> 14%, #e5e7eb);
            background:
                radial-gradient(circle at top right, color-mix(in srgb, <?php echo e($primaryColor); ?> 7%, transparent), transparent 28%),
                linear-gradient(180deg, rgba(255, 255, 255, .98), rgba(249, 250, 251, .94));
            box-shadow: 0 14px 36px rgba(2, 6, 23, .05);
            padding: .85rem;
            overflow: hidden;
        }

        .dark .hr-command-wrap {
            background:
                radial-gradient(circle at top right, color-mix(in srgb, <?php echo e($primaryColor); ?> 16%, transparent), transparent 30%),
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
            border-left: 3px solid <?php echo e($primaryColor); ?>;
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
            border: 1px solid color-mix(in srgb, <?php echo e($primaryColor); ?> 14%, #e5e7eb);
            background:
                radial-gradient(circle at top right, color-mix(in srgb, <?php echo e($primaryColor); ?> 7%, transparent), transparent 28%),
                linear-gradient(180deg, rgba(255, 255, 255, .98), rgba(249, 250, 251, .94));
            box-shadow: 0 14px 36px rgba(2, 6, 23, .05);
            padding: .85rem;
            overflow: hidden;
        }

        .dark .hr-command-wrap,
        .dark .hr-payroll-wrap {
            background:
                radial-gradient(circle at top right, color-mix(in srgb, <?php echo e($primaryColor); ?> 16%, transparent), transparent 30%),
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
            color: <?php echo e($primaryColor); ?>;
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
            background: color-mix(in srgb, <?php echo e($primaryColor); ?> 8%, white);
            color: <?php echo e($primaryColor); ?>;
            border: 1px solid color-mix(in srgb, <?php echo e($primaryColor); ?> 18%, white);
            font-size: .66rem;
            font-weight: 950;
            white-space: nowrap;
        }

        .dark .hr-block-badge {
            background: color-mix(in srgb, <?php echo e($primaryColor); ?> 14%, #111827);
            border-color: color-mix(in srgb, <?php echo e($primaryColor); ?> 24%, #374151);
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
            border-left: 3px solid <?php echo e($primaryColor); ?>;
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
    </style>

    <div class="farm-dashboard">
        <section class="farm-user-panel farm-shell-card">
            <div class="farm-user-main">
                <div class="farm-user-avatar">
                    <?php echo e($userInitial); ?>

                </div>

                <div class="min-w-0">
                    <div class="farm-muted-label">Logged in as</div>
                    <div class="farm-user-name"><?php echo e($userName); ?></div>
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
                    <div class="farm-user-role"><?php echo e($userRoles); ?></div>
                </div>
            </div>

            <div class="farm-user-system-card">
                <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-shield-check'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'h-5 w-5','style' => 'color: '.e($primaryColor).'']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $attributes = $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $component = $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
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
                        <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-sparkles'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $attributes = $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $component = $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
                        Farm intelligence system
                    </div>

                    <h1 class="farm-brand"><?php echo e($farmName); ?></h1>

                    <div class="farm-title">
                        <?php echo e($farmTagline); ?>

                        <small>
                            Real-time livestock visibility, HR control, operational reporting, and executive decision
                            support.
                        </small>
                    </div>

                    <div class="farm-pills">
                        <div class="farm-pill"><?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-chart-bar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $attributes = $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $component = $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?> Breed performance</div>
                        <div class="farm-pill"><?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-archive-box'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $attributes = $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $component = $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?> Lifecycle tracking</div>
                        <div class="farm-pill"><?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-users'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $attributes = $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $component = $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?> Workforce intelligence</div>
                        <div class="farm-pill"><?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-bolt'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $attributes = $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $component = $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?> Executive control</div>
                    </div>
                </div>

                <div class="farm-hero-side">
                    <div class="farm-clock-card">
                        <div class="farm-hero-mini-label">Today</div>
                        <div class="farm-hero-mini-value"><?php echo e($today); ?></div>

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

        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('view animals')): ?>
            <section class="farm-section">
                <div class="farm-section-head">
                    <div>
                        <div class="farm-section-kicker">
                            <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-cube-transparent'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $attributes = $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $component = $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
                            Animal records
                        </div>

                        <div class="farm-section-title">Livestock Control Overview</div>

                        <div class="farm-section-subtitle">
                            Track active stock, lifecycle status, breed distribution, and breeding records from one
                            operational layer.
                        </div>
                    </div>

                    <div class="farm-section-badge">
                        <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-sparkles'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $attributes = $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $component = $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
                        Live livestock view
                    </div>
                </div>

                <?php
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
                ?>

                <div class="farm-widget-row">
                    <div class="livestock-summary-wrap">
                        <div class="livestock-summary-grid">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $livestockMetrics; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $metric): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <a href="<?php echo e($metric['url']); ?>"
                                    class="livestock-summary-card <?php echo e($metric['value'] === 0 ? 'livestock-summary-card-muted' : ''); ?>"
                                    style="--metric-color: <?php echo e($metric['color']); ?>;">
                                    <div class="livestock-summary-top">
                                        <div class="min-w-0">
                                            <div class="livestock-summary-title"><?php echo e($metric['title']); ?></div>
                                            <div class="livestock-summary-subtitle"><?php echo e($metric['subtitle']); ?></div>
                                        </div>

                                        <div class="livestock-summary-icon">
                                            <?php if (isset($component)) { $__componentOriginal511d4862ff04963c3c16115c05a86a9d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal511d4862ff04963c3c16115c05a86a9d = $attributes; } ?>
<?php $component = Illuminate\View\DynamicComponent::resolve(['component' => $metric['icon']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dynamic-component'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\DynamicComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal511d4862ff04963c3c16115c05a86a9d)): ?>
<?php $attributes = $__attributesOriginal511d4862ff04963c3c16115c05a86a9d; ?>
<?php unset($__attributesOriginal511d4862ff04963c3c16115c05a86a9d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal511d4862ff04963c3c16115c05a86a9d)): ?>
<?php $component = $__componentOriginal511d4862ff04963c3c16115c05a86a9d; ?>
<?php unset($__componentOriginal511d4862ff04963c3c16115c05a86a9d); ?>
<?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="livestock-summary-bottom">
                                        <div class="livestock-summary-value">
                                            <?php echo e(number_format($metric['value'])); ?>

                                        </div>

                                        <div class="livestock-summary-action">
                                            View
                                            <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-arrow-right'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'h-3 w-3']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $attributes = $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $component = $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php
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
                ?>

                <div class="farm-widget-row">
                    <div class="breed-snapshot-wrap">
                        <div class="breed-snapshot-head">
                            <div>
                                <div class="breed-snapshot-kicker">
                                    <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-squares-2x2'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $attributes = $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $component = $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
                                    Breed distribution
                                </div>

                                <div class="breed-snapshot-title">Breed Snapshot</div>

                                <div class="breed-snapshot-subtitle">
                                    Compact active livestock distribution by breed and species. Click any breed to open its
                                    record.
                                </div>
                            </div>

                            <div class="breed-snapshot-badge">
                                <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-chart-bar-square'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $attributes = $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $component = $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
                                <?php echo e(number_format($totalBreedAnimals)); ?> active
                            </div>
                        </div>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($breedCards->isNotEmpty()): ?>
                            <div class="breed-grid-compact">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $breedCards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $breed): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
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
                                    ?>

                                    <a href="<?php echo e($breedUrl); ?>" class="breed-card-compact"
                                        title="Open <?php echo e($breedName); ?>">
                                        <div class="breed-card-main">
                                            <div class="breed-avatar-compact">
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($avatar): ?>
                                                    <img src="<?php echo e($avatar); ?>" alt="<?php echo e($breedName); ?>">
                                                <?php else: ?>
                                                    <?php echo e($initials ?: ($breed->prefix ?: 'BR')); ?>

                                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            </div>

                                            <div class="breed-card-info">
                                                <div class="breed-card-name" title="<?php echo e($breedName); ?>">
                                                    <?php echo e($breedName); ?>

                                                </div>

                                                <div class="breed-card-species species-<?php echo e($speciesKey); ?>">
                                                    <span class="species-dot"></span>
                                                    <?php echo e($species); ?>

                                                </div>
                                            </div>
                                        </div>

                                        <div class="breed-card-bottom">
                                            <div class="breed-mini-pill">
                                                <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-arrow-top-right-on-square'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'h-3 w-3']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $attributes = $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $component = $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
                                                Open
                                            </div>

                                            <div class="breed-count-box">
                                                <div class="breed-count-number <?php echo e($count === 0 ? 'is-zero' : ''); ?>">
                                                    <?php echo e(number_format($count)); ?>

                                                </div>
                                                <div class="breed-count-label">Active</div>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="breed-empty-state">
                                No breed records found.
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>

                <div class="farm-widget-row">
                    <div class="farm-chart-grid">
                        <?php if (isset($component)) { $__componentOriginal7259e9ea993f43cfa75aaa166dfee38d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7259e9ea993f43cfa75aaa166dfee38d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament-widgets::components.widgets','data' => ['columns' => 1,'data' => [
                            ...property_exists($this, 'filters') ? ['filters' => $this->filters] : [],
                            ...$this->getWidgetData(),
                        ],'widgets' => [App\Filament\Widgets\Dashboard\AnimalBreedPieChartWidget::class]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament-widgets::widgets'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['columns' => 1,'data' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([
                            ...property_exists($this, 'filters') ? ['filters' => $this->filters] : [],
                            ...$this->getWidgetData(),
                        ]),'widgets' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([App\Filament\Widgets\Dashboard\AnimalBreedPieChartWidget::class])]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7259e9ea993f43cfa75aaa166dfee38d)): ?>
<?php $attributes = $__attributesOriginal7259e9ea993f43cfa75aaa166dfee38d; ?>
<?php unset($__attributesOriginal7259e9ea993f43cfa75aaa166dfee38d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7259e9ea993f43cfa75aaa166dfee38d)): ?>
<?php $component = $__componentOriginal7259e9ea993f43cfa75aaa166dfee38d; ?>
<?php unset($__componentOriginal7259e9ea993f43cfa75aaa166dfee38d); ?>
<?php endif; ?>

                        <?php if (isset($component)) { $__componentOriginal7259e9ea993f43cfa75aaa166dfee38d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7259e9ea993f43cfa75aaa166dfee38d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament-widgets::components.widgets','data' => ['columns' => 1,'data' => [
                            ...property_exists($this, 'filters') ? ['filters' => $this->filters] : [],
                            ...$this->getWidgetData(),
                        ],'widgets' => [App\Filament\Widgets\Dashboard\AnimalStatusChartWidget::class]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament-widgets::widgets'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['columns' => 1,'data' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([
                            ...property_exists($this, 'filters') ? ['filters' => $this->filters] : [],
                            ...$this->getWidgetData(),
                        ]),'widgets' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([App\Filament\Widgets\Dashboard\AnimalStatusChartWidget::class])]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7259e9ea993f43cfa75aaa166dfee38d)): ?>
<?php $attributes = $__attributesOriginal7259e9ea993f43cfa75aaa166dfee38d; ?>
<?php unset($__attributesOriginal7259e9ea993f43cfa75aaa166dfee38d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7259e9ea993f43cfa75aaa166dfee38d)): ?>
<?php $component = $__componentOriginal7259e9ea993f43cfa75aaa166dfee38d; ?>
<?php unset($__componentOriginal7259e9ea993f43cfa75aaa166dfee38d); ?>
<?php endif; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('view weight records')): ?>
            <section class="farm-section">
                <div class="farm-section-head">
                    <div>
                        <div class="farm-section-kicker">
                            <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-scale'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $attributes = $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $component = $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
                            Animal Weight
                        </div>

                        <div class="farm-section-title">Breed Weight Intelligence</div>

                        <div class="farm-section-subtitle">
                            Compare breed performance using weight trends and current averages.
                        </div>
                    </div>

                    <div class="farm-weight-actions">
                        <div class="farm-section-badge">
                            <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-chart-bar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $attributes = $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $component = $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
                            Live Trends
                        </div>

                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('export weight records')): ?>
                            <a href="<?php echo e(route('reports.breed-weight-report')); ?>" target="_blank"
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
                                <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-printer'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['data-pdf-icon' => true,'class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $attributes = $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $component = $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>

                                <svg data-pdf-spinner class="hidden h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z">
                                    </path>
                                </svg>

                                <span data-pdf-text>Print PDF</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="farm-widget-row farm-compact-block">
                    <div class="farm-mini-section-head">
                        <div>
                            <div class="farm-mini-kicker">
                                <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-chart-bar-square'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $attributes = $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $component = $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
                                Weight progression
                            </div>

                            <div class="farm-mini-title">Breed Weight Trends</div>
                            <div class="farm-mini-subtitle">Average weight progression per breed.</div>
                        </div>
                    </div>

                    <?php if (isset($component)) { $__componentOriginal7259e9ea993f43cfa75aaa166dfee38d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7259e9ea993f43cfa75aaa166dfee38d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament-widgets::components.widgets','data' => ['columns' => 1,'widgets' => [App\Filament\Widgets\BreedWeightTrendChartWidget::class]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament-widgets::widgets'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['columns' => 1,'widgets' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([App\Filament\Widgets\BreedWeightTrendChartWidget::class])]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7259e9ea993f43cfa75aaa166dfee38d)): ?>
<?php $attributes = $__attributesOriginal7259e9ea993f43cfa75aaa166dfee38d; ?>
<?php unset($__attributesOriginal7259e9ea993f43cfa75aaa166dfee38d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7259e9ea993f43cfa75aaa166dfee38d)): ?>
<?php $component = $__componentOriginal7259e9ea993f43cfa75aaa166dfee38d; ?>
<?php unset($__componentOriginal7259e9ea993f43cfa75aaa166dfee38d); ?>
<?php endif; ?>
                </div>

                <div class="farm-widget-row farm-compact-block">
                    <div class="farm-mini-section-head">
                        <div>
                            <div class="farm-mini-kicker">
                                <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-scale'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $attributes = $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $component = $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
                                Current averages
                            </div>

                            <div class="farm-mini-title">Current Breed Average Weight</div>
                            <div class="farm-mini-subtitle">Latest average weight per breed.</div>
                        </div>
                    </div>

                    <?php if (isset($component)) { $__componentOriginal7259e9ea993f43cfa75aaa166dfee38d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7259e9ea993f43cfa75aaa166dfee38d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament-widgets::components.widgets','data' => ['columns' => 1,'widgets' => [App\Filament\Widgets\BreedCurrentAverageWeightChartWidget::class]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament-widgets::widgets'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['columns' => 1,'widgets' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([App\Filament\Widgets\BreedCurrentAverageWeightChartWidget::class])]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7259e9ea993f43cfa75aaa166dfee38d)): ?>
<?php $attributes = $__attributesOriginal7259e9ea993f43cfa75aaa166dfee38d; ?>
<?php unset($__attributesOriginal7259e9ea993f43cfa75aaa166dfee38d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7259e9ea993f43cfa75aaa166dfee38d)): ?>
<?php $component = $__componentOriginal7259e9ea993f43cfa75aaa166dfee38d; ?>
<?php unset($__componentOriginal7259e9ea993f43cfa75aaa166dfee38d); ?>
<?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('view hr dashboard')): ?>
            <section class="farm-section">
                <div class="farm-section-head">
                    <div>
                        <div class="farm-section-kicker">
                            <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-users'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $attributes = $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $component = $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
                            HR command center
                        </div>

                        <div class="farm-section-title">Workforce Operations Overview</div>

                        <div class="farm-section-subtitle">
                            Track attendance, leave approvals, salary advances, payroll totals, statutory deductions, staff
                            movements, and employee records.
                        </div>
                    </div>

                    <div class="farm-section-badge">
                        <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-shield-check'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $attributes = $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $component = $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
                        HR control layer
                    </div>
                </div>

                <?php
                    $schema = \Illuminate\Support\Facades\Schema::class;
                    $db = \Illuminate\Support\Facades\DB::class;

                    $todayDate = now('Africa/Nairobi')->toDateString();
                    $monthStart = now('Africa/Nairobi')->startOfMonth();
                    $monthEnd = now('Africa/Nairobi')->endOfMonth();

                    $money = fn($amount): string => 'KES ' . number_format((float) $amount, 2);

                    $tableExists = fn(string $table): bool => $schema::hasTable($table);

                    $hasColumn = fn(string $table, string $column): bool => $tableExists($table) &&
                        $schema::hasColumn($table, $column);

                    $firstExistingTable = function (array $tables) use ($tableExists): ?string {
                        foreach ($tables as $table) {
                            if ($tableExists($table)) {
                                return $table;
                            }
                        }

                        return null;
                    };

                    $applySoftDeletes = function ($query, string $table) use ($hasColumn) {
                        if ($hasColumn($table, 'deleted_at')) {
                            $query->whereNull('deleted_at');
                        }

                        return $query;
                    };

                    $applyCurrentMonth = function ($query, string $table) use ($hasColumn, $monthStart, $monthEnd) {
                        foreach (
                            [
                                'payroll_date',
                                'payment_date',
                                'period_start',
                                'processed_at',
                                'approved_at',
                                'created_at',
                            ]
                            as $dateColumn
                        ) {
                            if ($hasColumn($table, $dateColumn)) {
                                return $query->whereBetween($dateColumn, [
                                    $monthStart->toDateTimeString(),
                                    $monthEnd->toDateTimeString(),
                                ]);
                            }
                        }

                        if ($hasColumn($table, 'month') && $hasColumn($table, 'year')) {
                            return $query
                                ->where('month', now('Africa/Nairobi')->month)
                                ->where('year', now('Africa/Nairobi')->year);
                        }

                        if ($hasColumn($table, 'payroll_month')) {
                            return $query->where(function ($subQuery) {
                                $subQuery
                                    ->where('payroll_month', now('Africa/Nairobi')->format('Y-m'))
                                    ->orWhere('payroll_month', now('Africa/Nairobi')->format('F'))
                                    ->orWhere('payroll_month', now('Africa/Nairobi')->format('m'));
                            });
                        }

                        return $query;
                    };

                    $sumFirstColumn = function (array $tables, array $columns, bool $currentMonth = true) use (
                        $tableExists,
                        $hasColumn,
                        $db,
                        $applySoftDeletes,
                        $applyCurrentMonth,
                    ): float {
                        foreach ($tables as $table) {
                            if (!$tableExists($table)) {
                                continue;
                            }

                            foreach ($columns as $column) {
                                if (!$hasColumn($table, $column)) {
                                    continue;
                                }

                                $query = $db::table($table);
                                $query = $applySoftDeletes($query, $table);

                                if ($currentMonth) {
                                    $query = $applyCurrentMonth($query, $table);
                                }

                                return (float) $query->sum($column);
                            }
                        }

                        return 0.0;
                    };

                    $employeesTable = $firstExistingTable(['employees']);
                    $attendanceTable = $firstExistingTable(['attendances', 'employee_attendances', 'hr_attendances']);
                    $leaveTable = $firstExistingTable(['leave_requests', 'employee_leaves', 'leaves']);
                    $advanceTable = $firstExistingTable([
                        'salary_advances',
                        'employee_salary_advances',
                        'payroll_salary_advances',
                    ]);

                    $payrollTables = [
                        'payrolls',
                        'employee_payrolls',
                        'payroll_records',
                        'payroll_runs',
                        'payslips',
                        'salary_payments',
                    ];

                    $totalEmployees = 0;
                    $activeStaff = 0;
                    $exitedStaff = 0;

                    if ($employeesTable) {
                        $totalQuery = $applySoftDeletes($db::table($employeesTable), $employeesTable);
                        $totalEmployees = (int) $totalQuery->count();

                        $activeQuery = $applySoftDeletes($db::table($employeesTable), $employeesTable);
                        $exitedQuery = $applySoftDeletes($db::table($employeesTable), $employeesTable);

                        if ($hasColumn($employeesTable, 'employment_status')) {
                            $activeStaff = (int) $activeQuery
                                ->whereIn($db::raw('LOWER(employment_status)'), ['active', 'currently active'])
                                ->count();

                            $exitedStaff = (int) $exitedQuery
                                ->whereIn($db::raw('LOWER(employment_status)'), [
                                    'exited',
                                    'inactive',
                                    'terminated',
                                    'resigned',
                                    'dismissed',
                                ])
                                ->count();
                        } elseif ($hasColumn($employeesTable, 'status')) {
                            $activeStaff = (int) $activeQuery
                                ->whereIn($db::raw('LOWER(status)'), ['active', 'currently active'])
                                ->count();

                            $exitedStaff = (int) $exitedQuery
                                ->whereIn($db::raw('LOWER(status)'), [
                                    'exited',
                                    'inactive',
                                    'terminated',
                                    'resigned',
                                    'dismissed',
                                ])
                                ->count();
                        } elseif ($hasColumn($employeesTable, 'is_active')) {
                            $activeStaff = (int) $activeQuery->where('is_active', true)->count();
                            $exitedStaff = (int) $exitedQuery->where('is_active', false)->count();
                        } else {
                            $activeStaff = $totalEmployees;
                            $exitedStaff = 0;
                        }
                    }

                    $presentToday = 0;
                    $absentToday = 0;

                    if ($attendanceTable) {
                        $dateColumn = $hasColumn($attendanceTable, 'attendance_date')
                            ? 'attendance_date'
                            : ($hasColumn($attendanceTable, 'date')
                                ? 'date'
                                : null);

                        if ($dateColumn && $hasColumn($attendanceTable, 'status')) {
                            $presentQuery = $applySoftDeletes($db::table($attendanceTable), $attendanceTable)
                                ->whereDate($dateColumn, $todayDate)
                                ->whereIn($db::raw('LOWER(status)'), ['present', 'clocked_in', 'checked_in']);

                            $absentQuery = $applySoftDeletes($db::table($attendanceTable), $attendanceTable)
                                ->whereDate($dateColumn, $todayDate)
                                ->whereIn($db::raw('LOWER(status)'), ['absent', 'missed', 'no_show']);

                            $presentToday = (int) $presentQuery->count();
                            $absentToday = (int) $absentQuery->count();
                        }
                    }

                    $pendingLeave = 0;
                    $onLeaveToday = 0;

                    if ($leaveTable) {
                        $pendingLeaveQuery = $applySoftDeletes($db::table($leaveTable), $leaveTable);
                        $onLeaveQuery = $applySoftDeletes($db::table($leaveTable), $leaveTable);

                        if ($hasColumn($leaveTable, 'status')) {
                            $pendingLeaveQuery->whereIn($db::raw('LOWER(status)'), [
                                'pending',
                                'submitted',
                                'awaiting approval',
                            ]);
                            $onLeaveQuery->whereIn($db::raw('LOWER(status)'), ['approved', 'active']);
                        }

                        $pendingLeave = (int) $pendingLeaveQuery->count();

                        $startColumn = $hasColumn($leaveTable, 'start_date')
                            ? 'start_date'
                            : ($hasColumn($leaveTable, 'from_date')
                                ? 'from_date'
                                : null);

                        $endColumn = $hasColumn($leaveTable, 'end_date')
                            ? 'end_date'
                            : ($hasColumn($leaveTable, 'to_date')
                                ? 'to_date'
                                : null);

                        if ($startColumn && $endColumn) {
                            $onLeaveToday = (int) $onLeaveQuery
                                ->whereDate($startColumn, '<=', $todayDate)
                                ->whereDate($endColumn, '>=', $todayDate)
                                ->count();
                        }
                    }

                    $pendingAdvances = 0;

                    if ($advanceTable) {
                        $advanceQuery = $applySoftDeletes($db::table($advanceTable), $advanceTable);

                        if ($hasColumn($advanceTable, 'status')) {
                            $advanceQuery->whereIn($db::raw('LOWER(status)'), [
                                'pending',
                                'submitted',
                                'awaiting approval',
                            ]);
                        }

                        $pendingAdvances = (int) $advanceQuery->count();
                    }

                    $grossPayroll = $sumFirstColumn($payrollTables, [
                        'gross_salary',
                        'gross_pay',
                        'gross_amount',
                        'total_gross',
                        'gross_total',
                        'salary_gross',
                    ]);

                    $allowances = $sumFirstColumn($payrollTables, [
                        'allowances',
                        'total_allowances',
                        'allowance_amount',
                        'allowance_total',
                    ]);

                    $nssf = $sumFirstColumn($payrollTables, ['nssf', 'nssf_amount', 'nssf_deduction', 'employee_nssf']);

                    $sha = $sumFirstColumn($payrollTables, [
                        'sha',
                        'sha_amount',
                        'sha_deduction',
                        'shif',
                        'shif_amount',
                        'nhif',
                        'nhif_amount',
                    ]);

                    $housingLevy = $sumFirstColumn($payrollTables, [
                        'housing_levy',
                        'housing_levy_amount',
                        'housing_levy_deduction',
                    ]);

                    $paye = $sumFirstColumn($payrollTables, ['paye', 'paye_amount', 'paye_tax', 'paye_deduction']);

                    $nita = $sumFirstColumn($payrollTables, ['nita', 'nita_levy', 'nita_amount', 'nita_deduction']);

                    if ($grossPayroll <= 0 && $employeesTable) {
                        $grossPayroll = $sumFirstColumn(
                            [$employeesTable],
                            ['gross_salary', 'monthly_salary', 'basic_salary', 'salary'],
                            false,
                        );
                    }

                    if ($allowances <= 0 && $employeesTable) {
                        $allowances = $sumFirstColumn(
                            [$employeesTable],
                            ['allowances', 'total_allowances', 'allowance_amount'],
                            false,
                        );
                    }

                    $hrCards = [
                        [
                            'title' => 'Present Today',
                            'subtitle' => 'Checked in staff',
                            'value' => $presentToday,
                            'icon' => 'heroicon-o-check-circle',
                            'color' => $successColor,
                            'pill' => 'Today',
                        ],
                        [
                            'title' => 'Absent Today',
                            'subtitle' => 'Missing attendance',
                            'value' => $absentToday,
                            'icon' => 'heroicon-o-x-circle',
                            'color' => $dangerColor,
                            'pill' => 'Today',
                        ],
                        [
                            'title' => 'On Leave Today',
                            'subtitle' => 'Approved leave',
                            'value' => $onLeaveToday,
                            'icon' => 'heroicon-o-calendar-days',
                            'color' => '#2563eb',
                            'pill' => 'Leave',
                        ],
                        [
                            'title' => 'Pending Leave',
                            'subtitle' => 'Awaiting approval',
                            'value' => $pendingLeave,
                            'icon' => 'heroicon-o-clock',
                            'color' => '#f59e0b',
                            'pill' => 'Review',
                        ],
                        [
                            'title' => 'Pending Advances',
                            'subtitle' => 'Salary requests',
                            'value' => $pendingAdvances,
                            'icon' => 'heroicon-o-banknotes',
                            'color' => '#9333ea',
                            'pill' => 'Finance',
                        ],
                        [
                            'title' => 'Active Staff',
                            'subtitle' => 'Currently employed',
                            'value' => $activeStaff,
                            'icon' => 'heroicon-o-user-group',
                            'color' => $successColor,
                            'pill' => 'Active',
                        ],
                        [
                            'title' => 'Exited Staff',
                            'subtitle' => 'Exited employees',
                            'value' => $exitedStaff,
                            'icon' => 'heroicon-o-arrow-right-on-rectangle',
                            'color' => '#64748b',
                            'pill' => 'Exited',
                        ],
                        [
                            'title' => 'Total Employees',
                            'subtitle' => 'All employee records',
                            'value' => $totalEmployees,
                            'icon' => 'heroicon-o-identification',
                            'color' => $primaryColor,
                            'pill' => 'Total',
                        ],
                    ];

                    $payrollCards = [
                        [
                            'title' => 'Staff',
                            'subtitle' => 'Total employees in HR',
                            'value' => number_format($totalEmployees),
                            'is_money' => false,
                            'icon' => 'heroicon-o-users',
                            'color' => $primaryColor,
                            'pill' => 'HR',
                        ],
                        [
                            'title' => 'Salaries',
                            'subtitle' => 'Current month gross payroll',
                            'value' => $money($grossPayroll),
                            'is_money' => true,
                            'icon' => 'heroicon-o-banknotes',
                            'color' => '#16a34a',
                            'pill' => 'Gross',
                        ],
                        [
                            'title' => 'Allowances',
                            'subtitle' => 'Current month allowances',
                            'value' => $money($allowances),
                            'is_money' => true,
                            'icon' => 'heroicon-o-plus-circle',
                            'color' => '#2563eb',
                            'pill' => 'Benefits',
                        ],
                        [
                            'title' => 'NSSF',
                            'subtitle' => 'NSSF payable this month',
                            'value' => $money($nssf),
                            'is_money' => true,
                            'icon' => 'heroicon-o-building-library',
                            'color' => '#0f766e',
                            'pill' => 'Statutory',
                        ],
                        [
                            'title' => 'SHA',
                            'subtitle' => 'SHA payable this month',
                            'value' => $money($sha),
                            'is_money' => true,
                            'icon' => 'heroicon-o-heart',
                            'color' => '#dc2626',
                            'pill' => 'Health',
                        ],
                        [
                            'title' => 'Housing Levy',
                            'subtitle' => 'Housing levy payable',
                            'value' => $money($housingLevy),
                            'is_money' => true,
                            'icon' => 'heroicon-o-home-modern',
                            'color' => '#7c3aed',
                            'pill' => 'Levy',
                        ],
                        [
                            'title' => 'PAYE',
                            'subtitle' => 'PAYE payable this month',
                            'value' => $money($paye),
                            'is_money' => true,
                            'icon' => 'heroicon-o-receipt-percent',
                            'color' => '#f59e0b',
                            'pill' => 'Tax',
                        ],
                        [
                            'title' => 'NITA Levy',
                            'subtitle' => 'NITA payable this month',
                            'value' => $money($nita),
                            'is_money' => true,
                            'icon' => 'heroicon-o-academic-cap',
                            'color' => '#64748b',
                            'pill' => 'Training',
                        ],
                    ];
                ?>

                <div class="hr-mini-grid">
                    <div class="hr-mini-card">
                        <div class="hr-mini-label">Operations</div>
                        <div class="hr-mini-value">Attendance, leave, and approvals</div>
                    </div>

                    <div class="hr-mini-card">
                        <div class="hr-mini-label">Payroll</div>
                        <div class="hr-mini-value">Salaries, allowances, and advances</div>
                    </div>

                    <div class="hr-mini-card">
                        <div class="hr-mini-label">Compliance</div>
                        <div class="hr-mini-value">PAYE, NSSF, SHA, housing, NITA</div>
                    </div>

                    <div class="hr-mini-card">
                        <div class="hr-mini-label">Workforce</div>
                        <div class="hr-mini-value">Active, exited, and total staff</div>
                    </div>
                </div>

                <div class="farm-widget-row">
                    <div class="hr-command-wrap">
                        <div class="hr-block-head">
                            <div>
                                <div class="hr-block-kicker">
                                    <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-calendar-days'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $attributes = $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $component = $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
                                    Daily workforce control
                                </div>

                                <div class="hr-block-title">Attendance & Staff Movement</div>

                                <div class="hr-block-subtitle">
                                    Live view of attendance, leave, advances, active staff, exited staff, and total employee
                                    records.
                                </div>
                            </div>

                            <div class="hr-block-badge">
                                <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-bolt'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $attributes = $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $component = $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
                                Today
                            </div>
                        </div>

                        <div class="hr-command-grid">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $hrCards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $card): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="hr-command-card <?php echo e($card['value'] === 0 ? 'hr-command-card-muted' : ''); ?>"
                                    style="--hr-color: <?php echo e($card['color']); ?>;">
                                    <div class="hr-command-top">
                                        <div class="min-w-0">
                                            <div class="hr-command-title"><?php echo e($card['title']); ?></div>
                                            <div class="hr-command-subtitle"><?php echo e($card['subtitle']); ?></div>
                                        </div>

                                        <div class="hr-command-icon">
                                            <?php if (isset($component)) { $__componentOriginal511d4862ff04963c3c16115c05a86a9d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal511d4862ff04963c3c16115c05a86a9d = $attributes; } ?>
<?php $component = Illuminate\View\DynamicComponent::resolve(['component' => $card['icon']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dynamic-component'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\DynamicComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal511d4862ff04963c3c16115c05a86a9d)): ?>
<?php $attributes = $__attributesOriginal511d4862ff04963c3c16115c05a86a9d; ?>
<?php unset($__attributesOriginal511d4862ff04963c3c16115c05a86a9d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal511d4862ff04963c3c16115c05a86a9d)): ?>
<?php $component = $__componentOriginal511d4862ff04963c3c16115c05a86a9d; ?>
<?php unset($__componentOriginal511d4862ff04963c3c16115c05a86a9d); ?>
<?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="hr-command-bottom">
                                        <div class="hr-command-value"><?php echo e(number_format($card['value'])); ?></div>
                                        <div class="hr-command-pill"><?php echo e($card['pill']); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="farm-widget-row">
                    <div class="hr-payroll-wrap">
                        <div class="hr-block-head">
                            <div>
                                <div class="hr-block-kicker">
                                    <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-banknotes'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $attributes = $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $component = $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
                                    Payroll & statutory position
                                </div>

                                <div class="hr-block-title">Monthly Payroll Control</div>

                                <div class="hr-block-subtitle">
                                    Gross salaries, allowances, statutory deductions, and payroll compliance exposure for
                                    the current month.
                                </div>
                            </div>

                            <div class="hr-block-badge">
                                <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-calendar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $attributes = $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $component = $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
                                <?php echo e(now('Africa/Nairobi')->format('M Y')); ?>

                            </div>
                        </div>

                        <div class="hr-payroll-grid">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $payrollCards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $card): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="hr-payroll-card <?php echo e($card['value'] === 'KES 0.00' || $card['value'] === '0' ? 'hr-payroll-card-muted' : ''); ?>"
                                    style="--hr-color: <?php echo e($card['color']); ?>;">
                                    <div class="hr-payroll-top">
                                        <div class="min-w-0">
                                            <div class="hr-payroll-title"><?php echo e($card['title']); ?></div>
                                            <div class="hr-payroll-subtitle"><?php echo e($card['subtitle']); ?></div>
                                        </div>

                                        <div class="hr-payroll-icon">
                                            <?php if (isset($component)) { $__componentOriginal511d4862ff04963c3c16115c05a86a9d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal511d4862ff04963c3c16115c05a86a9d = $attributes; } ?>
<?php $component = Illuminate\View\DynamicComponent::resolve(['component' => $card['icon']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dynamic-component'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\DynamicComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal511d4862ff04963c3c16115c05a86a9d)): ?>
<?php $attributes = $__attributesOriginal511d4862ff04963c3c16115c05a86a9d; ?>
<?php unset($__attributesOriginal511d4862ff04963c3c16115c05a86a9d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal511d4862ff04963c3c16115c05a86a9d)): ?>
<?php $component = $__componentOriginal511d4862ff04963c3c16115c05a86a9d; ?>
<?php unset($__componentOriginal511d4862ff04963c3c16115c05a86a9d); ?>
<?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="hr-payroll-bottom">
                                        <div class="hr-payroll-value <?php echo e($card['is_money'] ? 'hr-payroll-money' : ''); ?>">
                                            <?php echo e($card['value']); ?>

                                        </div>

                                        <div class="hr-payroll-pill">
                                            <?php echo e($card['pill']); ?>

                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>
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
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal166a02a7c5ef5a9331faf66fa665c256)): ?>
<?php $attributes = $__attributesOriginal166a02a7c5ef5a9331faf66fa665c256; ?>
<?php unset($__attributesOriginal166a02a7c5ef5a9331faf66fa665c256); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal166a02a7c5ef5a9331faf66fa665c256)): ?>
<?php $component = $__componentOriginal166a02a7c5ef5a9331faf66fa665c256; ?>
<?php unset($__componentOriginal166a02a7c5ef5a9331faf66fa665c256); ?>
<?php endif; ?>
<?php /**PATH /home/maurice/LocalDev/Penzi/resources/views/filament/pages/dashboard.blade.php ENDPATH**/ ?>