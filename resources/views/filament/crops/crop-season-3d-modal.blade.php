@php
    /** @var \App\Models\CropSeason $record */

    $insight = $record->stage_insight ?? [];
    $progress = max(0, min(100, (int) ($record->growth_progress_percent ?? 0)));

    $primaryColor = trim(setting('theme.primary', '#008f00'));
    $secondaryColor = trim(setting('theme.secondary', '#111827'));
    $accentColor = trim(setting('theme.accent', '#f59e0b'));

    $normalizeHex = function (?string $color, string $fallback): string {
        $color = trim((string) $color);

        if (!str_starts_with($color, '#')) {
            $color = '#' . $color;
        }

        return preg_match('/^#[0-9A-Fa-f]{6}$/', $color) ? $color : $fallback;
    };

    $hexToRgb = function (string $hex): array {
        $hex = ltrim($hex, '#');

        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    };

    $primaryColor = $normalizeHex($primaryColor, '#008f00');
    $secondaryColor = $normalizeHex($secondaryColor, '#111827');
    $accentColor = $normalizeHex($accentColor, '#f59e0b');

    $primaryRgb = implode(',', $hexToRgb($primaryColor));
    $secondaryRgb = implode(',', $hexToRgb($secondaryColor));
    $accentRgb = implode(',', $hexToRgb($accentColor));

    $modelUrl = $record->has_stage_model ? $record->stage_model_url ?? null : null;
@endphp

@once
    <script type="module" src="https://unpkg.com/@google/model-viewer/dist/model-viewer.min.js"></script>

    <style>
        .crop-3d-modal-shell {
            --crop-primary: {{ $primaryColor }};
            --crop-primary-rgb: {{ $primaryRgb }};
            --crop-secondary: {{ $secondaryColor }};
            --crop-secondary-rgb: {{ $secondaryRgb }};
            --crop-accent: {{ $accentColor }};
            --crop-accent-rgb: {{ $accentRgb }};

            --crop-card: #ffffff;
            --crop-soft: #f8fafc;
            --crop-muted-card: #f1f5f9;
            --crop-border: #e2e8f0;
            --crop-text: #0f172a;
            --crop-muted: #475569;
            --crop-soft-text: #64748b;

            width: 100%;
            max-width: 720px;
            margin-inline: auto;
            color: var(--crop-text);
        }

        .dark .crop-3d-modal-shell {
            --crop-card: #0f172a;
            --crop-soft: #020617;
            --crop-muted-card: #111827;
            --crop-border: #334155;
            --crop-text: #f8fafc;
            --crop-muted: #cbd5e1;
            --crop-soft-text: #94a3b8;
        }

        .crop-3d-modal-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 230px;
            gap: .85rem;
            align-items: stretch;
        }

        .crop-3d-view-card,
        .crop-3d-info-card,
        .crop-3d-stat-card {
            background:
                linear-gradient(145deg, rgba(var(--crop-primary-rgb), .045), transparent 42%),
                var(--crop-card);
            border: 1px solid var(--crop-border);
            color: var(--crop-text);
            box-shadow: 0 12px 34px rgba(15, 23, 42, .08);
        }

        .dark .crop-3d-view-card,
        .dark .crop-3d-info-card,
        .dark .crop-3d-stat-card {
            box-shadow: 0 14px 44px rgba(0, 0, 0, .28);
        }

        .crop-3d-view-card {
            overflow: hidden;
            border-radius: 1.25rem;
            min-width: 0;
        }

        .crop-3d-view-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            padding: .75rem .85rem;
            border-bottom: 1px solid var(--crop-border);
            background:
                linear-gradient(135deg, rgba(var(--crop-primary-rgb), .10), rgba(var(--crop-accent-rgb), .07)),
                var(--crop-muted-card);
        }

        .crop-3d-title {
            font-size: .88rem;
            line-height: 1.2;
            font-weight: 900;
            color: var(--crop-text);
        }

        .crop-3d-subtitle {
            margin-top: .15rem;
            font-size: .68rem;
            line-height: 1.35;
            font-weight: 700;
            color: var(--crop-soft-text);
        }

        .crop-3d-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .35rem;
            min-height: 1.8rem;
            padding: .38rem .6rem;
            border-radius: 999px;
            font-size: .58rem;
            font-weight: 950;
            line-height: 1;
            letter-spacing: .06em;
            text-transform: uppercase;
            white-space: nowrap;
            color: var(--crop-primary);
            background: rgba(var(--crop-primary-rgb), .12);
            border: 1px solid rgba(var(--crop-primary-rgb), .24);
        }

        .dark .crop-3d-badge {
            color: #bbf7d0;
            background: rgba(var(--crop-primary-rgb), .20);
            border-color: rgba(var(--crop-primary-rgb), .34);
        }

        .crop-3d-view-body {
            padding: .65rem;
            background:
                radial-gradient(circle at top, rgba(var(--crop-primary-rgb), .08), transparent 40%),
                var(--crop-soft);
        }

        .crop-3d-viewer-frame {
            width: 100%;
            overflow: hidden;
            border-radius: 1rem;
            border: 1px solid var(--crop-border);
            background: var(--crop-card);
        }

        .crop-3d-model-viewer {
            width: 100%;
            height: min(38vh, 320px);
            min-height: 230px;
            max-height: 320px;
            background:
                radial-gradient(circle at top, rgba(var(--crop-primary-rgb), .12), transparent 35%),
                var(--crop-soft);
        }

        .crop-3d-fallback {
            position: relative;
            height: min(38vh, 320px);
            min-height: 230px;
            max-height: 320px;
            overflow: hidden;
            border-radius: 1rem;
            background: var(--crop-soft);
        }

        .crop-3d-fallback img {
            height: 100%;
            width: 100%;
            object-fit: cover;
        }

        .crop-3d-fallback-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(2, 6, 23, .82), rgba(2, 6, 23, .20), transparent);
        }

        .crop-3d-fallback-message {
            position: absolute;
            left: .75rem;
            right: .75rem;
            bottom: .75rem;
            border-radius: .9rem;
            background: rgba(2, 6, 23, .72);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, .15);
            padding: .8rem;
            backdrop-filter: blur(12px);
        }

        .crop-3d-info-card {
            border-radius: 1.25rem;
            padding: .85rem;
            min-width: 0;
        }

        .crop-3d-info-header {
            display: flex;
            align-items: center;
            gap: .65rem;
            margin-bottom: .75rem;
        }

        .crop-3d-icon {
            display: inline-flex;
            height: 2.25rem;
            width: 2.25rem;
            align-items: center;
            justify-content: center;
            border-radius: .85rem;
            background: rgba(var(--crop-primary-rgb), .12);
            color: var(--crop-primary);
            border: 1px solid rgba(var(--crop-primary-rgb), .22);
            flex-shrink: 0;
        }

        .dark .crop-3d-icon {
            color: #bbf7d0;
            background: rgba(var(--crop-primary-rgb), .20);
        }

        .crop-3d-progress-track {
            height: .48rem;
            border-radius: 999px;
            overflow: hidden;
            background: var(--crop-muted-card);
            border: 1px solid var(--crop-border);
        }

        .crop-3d-progress-fill {
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, var(--crop-primary), var(--crop-accent));
        }

        .crop-3d-stat-grid {
            margin-top: .75rem;
            display: grid;
            grid-template-columns: 1fr;
            gap: .55rem;
        }

        .crop-3d-stat-card {
            border-radius: .85rem;
            padding: .65rem;
        }

        .crop-3d-stat-label {
            font-size: .6rem;
            line-height: 1;
            font-weight: 900;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--crop-soft-text);
        }

        .crop-3d-stat-value {
            margin-top: .35rem;
            font-size: .78rem;
            line-height: 1.3;
            font-weight: 900;
            color: var(--crop-text);
        }

        .crop-3d-path-box {
            margin-top: .75rem;
            border-radius: .85rem;
            padding: .65rem;
            background: var(--crop-soft);
            border: 1px dashed var(--crop-border);
            color: var(--crop-muted);
            font-size: .68rem;
            line-height: 1.45;
            word-break: break-word;
        }

        @media (max-width: 768px) {
            .crop-3d-modal-shell {
                max-width: 100%;
            }

            .crop-3d-modal-grid {
                grid-template-columns: 1fr;
            }

            .crop-3d-model-viewer,
            .crop-3d-fallback {
                height: 34vh;
                min-height: 220px;
                max-height: 280px;
            }

            .crop-3d-info-card {
                padding: .75rem;
            }

            .crop-3d-stat-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .crop-3d-modal-shell .fi-modal-footer-actions {
                display: none !important;
            }
        }

        @media (max-width: 480px) {
            .crop-3d-view-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .crop-3d-badge {
                min-height: 1.65rem;
                padding: .34rem .5rem;
                font-size: .52rem;
            }

            .crop-3d-model-viewer,
            .crop-3d-fallback {
                height: 32vh;
                min-height: 200px;
                max-height: 250px;
            }

            .crop-3d-stat-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endonce

<div class="crop-3d-modal-shell">
    <div class="crop-3d-modal-grid">
        <div class="crop-3d-view-card">
            <div class="crop-3d-view-header">
                <div class="min-w-0">
                    <div class="crop-3d-title truncate">
                        {{ $record->name }}
                    </div>

                    <div class="crop-3d-subtitle truncate">
                        {{ $record->crop_name }} • {{ str($record->growth_stage)->replace('_', ' ')->title() }}
                    </div>
                </div>

                <span class="crop-3d-badge">
                    <x-heroicon-o-cube-transparent style="width: .78rem; height: .78rem;" />
                    3D Viewer
                </span>
            </div>

            <div class="crop-3d-view-body">
                <div class="crop-3d-viewer-frame">
                    @if ($record->has_stage_model && filled($modelUrl))
                        <model-viewer src="{{ $modelUrl }}" alt="{{ $record->name }}" auto-rotate camera-controls
                            shadow-intensity="1" exposure="1" loading="lazy"
                            class="crop-3d-model-viewer"></model-viewer>
                    @else
                        <div class="crop-3d-fallback">
                            <img src="{{ $record->stage_image_url }}" alt="{{ $record->name }}" loading="lazy">

                            <div class="crop-3d-fallback-overlay"></div>

                            <div class="crop-3d-fallback-message">
                                <div style="font-size: .8rem; font-weight: 900;">
                                    3D model not uploaded yet
                                </div>

                                <div style="margin-top: .25rem; font-size: .68rem; color: rgba(255,255,255,.78);">
                                    Upload the GLB model to enable the interactive viewer.
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <aside class="crop-3d-info-card">
            <div class="crop-3d-info-header">
                <div class="crop-3d-icon">
                    <x-heroicon-o-cpu-chip style="width: 1.15rem; height: 1.15rem;" />
                </div>

                <div class="min-w-0">
                    <div class="crop-3d-title truncate">
                        Crop Intelligence
                    </div>

                    <div class="crop-3d-subtitle truncate">
                        {{ $progress }}% growth progress
                    </div>
                </div>
            </div>

            <div class="crop-3d-progress-track">
                <div class="crop-3d-progress-fill" style="width: {{ $progress }}%;"></div>
            </div>

            <div class="crop-3d-stat-grid">
                <div class="crop-3d-stat-card">
                    <div class="crop-3d-stat-label">Planting Date</div>
                    <div class="crop-3d-stat-value">
                        {{ $record->planting_date?->format('d M Y') ?? 'N/A' }}
                    </div>
                </div>

                <div class="crop-3d-stat-card">
                    <div class="crop-3d-stat-label">Age</div>
                    <div class="crop-3d-stat-value">
                        {{ number_format((int) ($record->days_since_planting ?? 0)) }} days
                    </div>
                </div>

                <div class="crop-3d-stat-card">
                    <div class="crop-3d-stat-label">Harvest Status</div>
                    <div class="crop-3d-stat-value">
                        {{ $record->harvest_status ?? 'N/A' }}
                    </div>
                </div>

                <div class="crop-3d-stat-card">
                    <div class="crop-3d-stat-label">Expected Harvest</div>
                    <div class="crop-3d-stat-value">
                        {{ $record->expected_harvest_from?->format('d M Y') ?? 'N/A' }}
                    </div>
                </div>
            </div>

            @unless ($record->has_stage_model && filled($modelUrl))
                <div class="crop-3d-path-box">
                    <strong>Expected path:</strong><br>
                    public/models/crops/{{ $insight['crop_slug'] ?? 'crop' }}/{{ $record->growth_stage }}.glb
                </div>
            @endunless
        </aside>
    </div>
</div>
