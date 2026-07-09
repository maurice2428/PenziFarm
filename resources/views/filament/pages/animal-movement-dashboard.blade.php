<div class="movement-dashboard">
    @php
        $primaryColor = trim(setting('theme.primary', '#008f00'));
        $secondaryColor = trim(setting('theme.secondary', '#111827'));
        $accentColor = trim(setting('theme.accent', '#f59e0b'));
    @endphp

    <style>
        .movement-dashboard {
            --movement-primary: {{ $primaryColor }};
            --movement-secondary: {{ $secondaryColor }};
            --movement-accent: {{ $accentColor }};
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
            padding-bottom: 24px;
        }

        .movement-dashboard * {
            box-sizing: border-box;
            min-width: 0;
        }

        .movement-hero {
            position: relative;
            overflow: hidden;
            border-radius: 30px;
            padding: 28px;
            color: #fff;
            background:
                radial-gradient(circle at top right, rgba(255,255,255,.20), transparent 30%),
                radial-gradient(circle at bottom left, rgba(0,0,0,.22), transparent 38%),
                linear-gradient(135deg, var(--movement-primary), var(--movement-secondary));
            box-shadow: 0 24px 60px rgba(15, 23, 42, .22);
        }

        .movement-eyebrow {
            display: inline-flex;
            align-items: center;
            border: 1px solid rgba(255,255,255,.25);
            border-radius: 999px;
            background: rgba(255,255,255,.14);
            padding: 7px 13px;
            font-size: 10px;
            font-weight: 950;
            letter-spacing: .18em;
            text-transform: uppercase;
            max-width: 100%;
            white-space: normal;
        }

        .movement-title {
            margin-top: 15px;
            font-size: clamp(25px, 7vw, 44px);
            font-weight: 950;
            letter-spacing: -.05em;
            line-height: 1.05;
            overflow-wrap: anywhere;
        }

        .movement-copy {
            margin-top: 10px;
            max-width: 900px;
            color: rgba(255,255,255,.84);
            line-height: 1.7;
            font-size: 14px;
        }

        .movement-grid {
            margin-top: 16px;
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 12px;
        }

        .movement-card,
        .movement-panel {
            border: 1px solid rgba(15,23,42,.12);
            border-radius: 18px;
            background: #fff;
            padding: 16px;
            box-shadow: 0 12px 30px rgba(15,23,42,.07);
            overflow: hidden;
        }

        .movement-card {
            position: relative;
        }

        .movement-card::after {
            content: "";
            position: absolute;
            right: -28px;
            top: -32px;
            width: 96px;
            height: 96px;
            border-radius: 999px;
            background: var(--movement-primary);
            opacity: .08;
        }

        .movement-card-label {
            color: #64748b;
            font-size: 10px;
            font-weight: 950;
            text-transform: uppercase;
            letter-spacing: .08em;
            overflow-wrap: anywhere;
        }

        .movement-card-value {
            margin-top: 6px;
            color: #0f172a;
            font-size: clamp(20px, 5vw, 24px);
            font-weight: 950;
            line-height: 1.1;
            overflow-wrap: anywhere;
        }

        .movement-card-note {
            margin-top: 6px;
            color: #64748b;
            font-size: 11px;
            line-height: 1.45;
        }

        .movement-layout {
            margin-top: 16px;
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(300px, 390px);
            gap: 16px;
            align-items: start;
        }

        .movement-section-title {
            color: #0f172a;
            font-size: 15px;
            font-weight: 950;
        }

        .movement-section-subtitle {
            margin-top: 4px;
            color: #64748b;
            font-size: 11px;
            line-height: 1.5;
        }

        .movement-scroll {
            margin-top: 10px;
            max-height: 520px;
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 4px;
            scrollbar-width: thin;
        }

        .movement-row {
            margin-top: 10px;
            border: 1px solid rgba(15,23,42,.08);
            border-radius: 14px;
            padding: 12px;
            background: linear-gradient(135deg, #ffffff, #f8fafc);
            overflow-wrap: anywhere;
        }

        .movement-row-title {
            font-weight: 950;
            color: #0f172a;
            font-size: 13px;
            line-height: 1.35;
        }

        .movement-row-meta {
            margin-top: 4px;
            color: #64748b;
            font-size: 11px;
            line-height: 1.5;
        }

        .movement-pill {
            display: inline-flex;
            margin-top: 7px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--movement-primary) 12%, #ffffff);
            color: var(--movement-primary);
            padding: 4px 8px;
            font-size: 10px;
            font-weight: 950;
            max-width: 100%;
            white-space: normal;
        }

        .movement-table-scroll {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        @media (max-width: 1300px) {
            .movement-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .movement-layout {
                grid-template-columns: 1fr;
            }

            .movement-scroll {
                max-height: none;
                overflow-y: visible;
                padding-right: 0;
            }
        }

        @media (max-width: 800px) {
            .movement-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .movement-hero {
                border-radius: 22px;
                padding: 20px 15px;
            }

            .movement-card,
            .movement-panel {
                border-radius: 15px;
                padding: 13px;
            }
        }

        @media (max-width: 520px) {
            .movement-grid {
                grid-template-columns: 1fr;
            }

            .movement-dashboard {
                overflow-x: hidden;
            }

            .movement-title {
                letter-spacing: -.035em;
            }
        }

        .dark .movement-card,
        .dark .movement-panel {
            background: #111827;
            border-color: rgba(148,163,184,.22);
        }

        .dark .movement-card-value,
        .dark .movement-section-title,
        .dark .movement-row-title {
            color: #f8fafc;
        }

        .dark .movement-card-label,
        .dark .movement-card-note,
        .dark .movement-section-subtitle,
        .dark .movement-row-meta {
            color: #cbd5e1;
        }

        .dark .movement-row {
            background: #020617;
            border-color: rgba(148,163,184,.22);
        }
    </style>

    <div class="movement-hero">
        <div class="movement-eyebrow">Animal Movement Control</div>

        <div class="movement-title">
            Animal Movement Intelligence
        </div>

        <div class="movement-copy">
            Track transfers, received animals, pending movement, dynamic groups, and current animal distribution
            across farm locations from one control dashboard.
        </div>
    </div>

    <div class="movement-grid">
        <div class="movement-card">
            <div class="movement-card-label">Active Animals</div>
            <div class="movement-card-value">{{ number_format($activeAnimals) }}</div>
            <div class="movement-card-note">Animals currently active and not archived.</div>
        </div>

        <div class="movement-card">
            <div class="movement-card-label">Pending Transfers</div>
            <div class="movement-card-value">{{ number_format($pendingTransfers) }}</div>
            <div class="movement-card-note">Transfers waiting to be received.</div>
        </div>

        <div class="movement-card">
            <div class="movement-card-label">Received Today</div>
            <div class="movement-card-value">{{ number_format($completedTransfersToday) }}</div>
            <div class="movement-card-note">Completed movement records today.</div>
        </div>

        <div class="movement-card">
            <div class="movement-card-label">Received This Month</div>
            <div class="movement-card-value">{{ number_format($completedTransfersThisMonth) }}</div>
            <div class="movement-card-note">Completed movement records this month.</div>
        </div>

        <div class="movement-card">
            <div class="movement-card-label">Active Groups</div>
            <div class="movement-card-value">{{ number_format($animalGroups) }}</div>
            <div class="movement-card-note">Manual and dynamic groups in use.</div>
        </div>

        <div class="movement-card">
            <div class="movement-card-label">Auto Groups</div>
            <div class="movement-card-value">{{ number_format($autoGroups) }}</div>
            <div class="movement-card-note">Groups that can auto-sync animals.</div>
        </div>
    </div>

    <div class="movement-layout">
        <div class="movement-panel">
            <div class="movement-section-title">Recent Transfers</div>
            <div class="movement-section-subtitle">
                Latest movement records showing route, status, and number of animals moved.
            </div>

            <div class="movement-scroll">
                @forelse ($recentTransfers as $transfer)
                    <div class="movement-row">
                        <div class="movement-row-title">
                            {{ $transfer->transfer_number }} — {{ $transfer->status_label }}
                        </div>

                        <div class="movement-row-meta">
                            From {{ $transfer->fromLocation?->name ?? 'Mixed / Current' }}
                            to {{ $transfer->toLocation?->name ?? '-' }}
                            · {{ $transfer->items->count() }} animal(s)
                            · {{ $transfer->transfer_date?->format('d M Y') }}
                        </div>

                        <div class="movement-pill">
                            {{ strtoupper($transfer->status) }}
                        </div>
                    </div>
                @empty
                    <div class="movement-row-meta mt-3">No transfers recorded yet.</div>
                @endforelse
            </div>
        </div>

        <div class="movement-panel">
            <div class="movement-section-title">Location Distribution</div>
            <div class="movement-section-subtitle">
                Active animal count per location. Useful for overcrowding and space planning.
            </div>

            <div class="movement-scroll">
                @forelse ($locationSummary as $location)
                    <div class="movement-row">
                        <div class="movement-row-title">{{ $location->name }}</div>
                        <div class="movement-row-meta">
                            {{ number_format($location->active_animals_count) }} active animal(s)
                        </div>

                        <div class="movement-pill">
                            {{ number_format($location->active_animals_count) }}
                        </div>
                    </div>
                @empty
                    <div class="movement-row-meta mt-3">No active locations found.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
