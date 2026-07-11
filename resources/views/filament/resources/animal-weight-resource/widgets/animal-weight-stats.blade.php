<div
    class="weight-overview-shell"
    style="
        --weight-primary: {{ $primaryColor }};
        --weight-secondary: {{ $secondaryColor }};
        --weight-accent: {{ $accentColor }};
        --weight-success: {{ $successColor }};
        --weight-danger: {{ $dangerColor }};
    "
>
    <style>
        .weight-overview-shell {
            width: 100%;
            color: #0f172a;
        }

        .dark .weight-overview-shell {
            color: #f8fafc;
        }

        .weight-overview-panel {
            overflow: hidden;
            border: 1px solid color-mix(
                in srgb,
                var(--weight-primary) 24%,
                #dbe4dc
            );
            border-radius: .85rem;
            background:
                radial-gradient(
                    circle at 92% 8%,
                    color-mix(
                        in srgb,
                        var(--weight-primary) 7%,
                        transparent
                    ),
                    transparent 28%
                ),
                #ffffff;
            box-shadow: 0 9px 26px rgba(15, 23, 42, .055);
        }

        .dark .weight-overview-panel {
            border-color: #334155;
            background:
                radial-gradient(
                    circle at 92% 8%,
                    color-mix(
                        in srgb,
                        var(--weight-primary) 14%,
                        transparent
                    ),
                    transparent 28%
                ),
                #0f172a;
            box-shadow: 0 12px 30px rgba(0, 0, 0, .24);
        }

        .weight-overview-heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 1rem .75rem;
            flex-wrap: wrap;
        }

        .weight-overview-eyebrow {
            display: flex;
            align-items: center;
            gap: .42rem;
            color: var(--weight-primary);
            font-size: .68rem;
            font-weight: 900;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .weight-overview-eyebrow svg {
            width: .9rem;
            height: .9rem;
        }

        .weight-overview-title {
            margin-top: .38rem;
            color: #111827;
            font-size: 1rem;
            font-weight: 850;
            letter-spacing: -.015em;
        }

        .dark .weight-overview-title {
            color: #f8fafc;
        }

        .weight-overview-description {
            margin-top: .3rem;
            max-width: 760px;
            color: #64748b;
            font-size: .72rem;
            line-height: 1.5;
        }

        .dark .weight-overview-description {
            color: #94a3b8;
        }

        .weight-overview-live {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .42rem .62rem;
            border: 1px solid color-mix(
                in srgb,
                var(--weight-primary) 25%,
                #dbe4dc
            );
            color: var(--weight-primary);
            background: color-mix(
                in srgb,
                var(--weight-primary) 7%,
                white
            );
            font-size: .64rem;
            font-weight: 850;
            white-space: nowrap;
        }

        .dark .weight-overview-live {
            border-color: color-mix(
                in srgb,
                var(--weight-primary) 55%,
                #334155
            );
            color: #dcfce7;
            background: color-mix(
                in srgb,
                var(--weight-primary) 26%,
                #0f172a
            );
        }

        .weight-overview-live svg {
            width: .85rem;
            height: .85rem;
        }

        .weight-overview-grid-wrap {
            padding: 0 1rem 1rem;
        }

        .weight-overview-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .58rem;
            width: 100%;
        }

        .weight-overview-card {
            --card-line: var(--weight-primary);
            --card-soft: color-mix(
                in srgb,
                var(--card-line) 8%,
                white
            );
            --card-icon-bg: color-mix(
                in srgb,
                var(--card-line) 10%,
                white
            );
            --card-icon-border: color-mix(
                in srgb,
                var(--card-line) 20%,
                #e2e8f0
            );

            position: relative;
            min-width: 0;
            min-height: 116px;
            overflow: hidden;
            border: 1px solid #dfe5eb;
            border-left: 4px solid var(--card-line);
            border-radius: .18rem;
            padding: .72rem .72rem .62rem;
            background: #ffffff;
            box-shadow: 0 4px 14px rgba(15, 23, 42, .045);
            transition:
                transform .17s ease,
                box-shadow .17s ease,
                border-color .17s ease;
        }

        .weight-overview-card:hover {
            transform: translateY(-1px);
            border-color: color-mix(
                in srgb,
                var(--card-line) 30%,
                #dfe5eb
            );
            box-shadow: 0 8px 20px rgba(15, 23, 42, .08);
        }

        .weight-overview-card::after {
            content: "";
            position: absolute;
            top: -36px;
            right: -36px;
            width: 96px;
            height: 96px;
            border-radius: 999px;
            background: var(--card-soft);
            pointer-events: none;
        }

        .dark .weight-overview-card {
            --card-soft: color-mix(
                in srgb,
                var(--card-line) 14%,
                #0f172a
            );
            --card-icon-bg: color-mix(
                in srgb,
                var(--card-line) 18%,
                #0f172a
            );
            --card-icon-border: color-mix(
                in srgb,
                var(--card-line) 44%,
                #334155
            );

            border-color: #334155;
            border-left-color: var(--card-line);
            background: #111827;
            box-shadow: 0 5px 16px rgba(0, 0, 0, .18);
        }

        .weight-overview-card[data-tone="primary"] {
            --card-line: var(--weight-primary);
        }

        .weight-overview-card[data-tone="average"] {
            --card-line: #2563eb;
        }

        .weight-overview-card[data-tone="success"] {
            --card-line: var(--weight-success);
        }

        .weight-overview-card[data-tone="danger"] {
            --card-line: var(--weight-danger);
        }

        .weight-overview-card[data-tone="warning"] {
            --card-line: var(--weight-accent);
        }

        .weight-overview-card[data-tone="recent"] {
            --card-line: #7c3aed;
        }

        .weight-overview-card-top {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .5rem;
        }

        .weight-overview-label {
            color: #1f2937;
            font-size: .7rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .dark .weight-overview-label {
            color: #f8fafc;
        }

        .weight-overview-description-small {
            margin-top: .15rem;
            color: #94a3b8;
            font-size: .54rem;
            line-height: 1.25;
        }

        .dark .weight-overview-description-small {
            color: #94a3b8;
        }

        .weight-overview-icon {
            position: relative;
            z-index: 2;
            display: grid;
            flex: 0 0 auto;
            width: 2rem;
            height: 2rem;
            place-items: center;
            border: 1px solid var(--card-icon-border);
            color: var(--card-line);
            background: var(--card-icon-bg);
        }

        .weight-overview-icon svg {
            width: .95rem;
            height: .95rem;
        }

        .weight-overview-bottom {
            position: absolute;
            z-index: 2;
            right: .72rem;
            bottom: .62rem;
            left: .72rem;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: .5rem;
        }

        .weight-overview-value {
            min-width: 0;
            overflow: hidden;
            color: #0f172a;
            font-size: clamp(1.35rem, 3.4vw, 1.75rem);
            font-weight: 900;
            letter-spacing: -.04em;
            line-height: 1;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dark .weight-overview-value {
            color: #f8fafc;
        }

        .weight-overview-meta {
            max-width: 48%;
            overflow: hidden;
            color: #64748b;
            font-size: .53rem;
            font-weight: 750;
            line-height: 1.2;
            text-align: right;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dark .weight-overview-meta {
            color: #94a3b8;
        }

        @media (min-width: 760px) {
            .weight-overview-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: .68rem;
            }

            .weight-overview-card {
                min-height: 124px;
                padding: .78rem .78rem .66rem;
            }

            .weight-overview-bottom {
                right: .78rem;
                bottom: .66rem;
                left: .78rem;
            }
        }

        @media (min-width: 1280px) {
            .weight-overview-grid {
                grid-template-columns: repeat(6, minmax(0, 1fr));
                gap: .62rem;
            }

            .weight-overview-card {
                min-height: 122px;
            }

            .weight-overview-value {
                font-size: clamp(1.45rem, 1.65vw, 1.85rem);
            }
        }

        @media (max-width: 420px) {
            .weight-overview-heading {
                padding: .85rem .85rem .65rem;
            }

            .weight-overview-grid-wrap {
                padding: 0 .85rem .85rem;
            }

            .weight-overview-grid {
                gap: .48rem;
            }

            .weight-overview-card {
                min-height: 112px;
                padding: .62rem .6rem .55rem;
            }

            .weight-overview-bottom {
                right: .6rem;
                bottom: .55rem;
                left: .6rem;
            }

            .weight-overview-label {
                font-size: .64rem;
            }

            .weight-overview-description-small {
                font-size: .49rem;
            }

            .weight-overview-icon {
                width: 1.75rem;
                height: 1.75rem;
            }

            .weight-overview-value {
                font-size: 1.25rem;
            }

            .weight-overview-meta {
                font-size: .48rem;
            }
        }
    </style>

    <section class="weight-overview-panel">
        <div class="weight-overview-heading">
            <div>
                <div class="weight-overview-eyebrow">
                    <x-filament::icon
                        icon="heroicon-o-scale"
                        class="h-4 w-4"
                    />

                    Weight records
                </div>

                <div class="weight-overview-title">
                    Animal Weight Intelligence
                </div>

                <div class="weight-overview-description">
                    Track the latest live weight, growth direction,
                    monitoring alerts, and recent weighing activity
                    from one operational view.
                </div>
            </div>

            <div class="weight-overview-live">
                <x-filament::icon
                    icon="heroicon-o-signal"
                    class="h-4 w-4"
                />

                Live weight view
            </div>
        </div>

        <div class="weight-overview-grid-wrap">
            <div class="weight-overview-grid">
                @foreach ($cards as $card)
                    <article
                        class="weight-overview-card"
                        data-tone="{{ $card['tone'] }}"
                    >
                        <div class="weight-overview-card-top">
                            <div>
                                <div class="weight-overview-label">
                                    {{ $card['label'] }}
                                </div>

                                <div
                                    class="weight-overview-description-small"
                                    title="{{ $card['description'] }}"
                                >
                                    {{ $card['description'] }}
                                </div>
                            </div>

                            <div class="weight-overview-icon">
                                <x-filament::icon
                                    :icon="$card['icon']"
                                    class="h-4 w-4"
                                />
                            </div>
                        </div>

                        <div class="weight-overview-bottom">
                            <div class="weight-overview-value">
                                {{ $card['value'] }}
                            </div>

                            <div
                                class="weight-overview-meta"
                                title="{{ $card['meta'] }}"
                            >
                                {{ $card['meta'] }}
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
</div>
