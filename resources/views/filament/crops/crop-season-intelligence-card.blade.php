@once
    <script type="module" src="https://unpkg.com/@google/model-viewer/dist/model-viewer.min.js"></script>

    <style>
        [x-cloak] {
            display: none !important;
        }

        .crop-season-card-premium {
            position: relative;
            isolation: isolate;
            overflow: hidden;
            border-radius: 1.35rem;
            background: #ffffff;
            color: #0f172a;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
        }

        .dark .crop-season-card-premium {
            background: #0f172a;
            color: #f8fafc;
            border-color: #334155;
            box-shadow: 0 14px 38px rgba(0, 0, 0, 0.35);
        }

        .crop-season-media {
            position: relative;
            min-height: 15rem;
            overflow: hidden;
            background: #f1f5f9;
        }

        .dark .crop-season-media {
            background: #020617;
        }

        @media (min-width: 1024px) {
            .crop-season-media {
                min-height: 100%;
            }
        }

        .crop-season-media img {
            transition: transform .6s ease;
        }

        @media (hover: hover) {
            .crop-season-card-premium:hover .crop-season-media img {
                transform: scale(1.05);
            }
        }

        .crop-glass-badge {
            display: inline-flex;
            align-items: center;
            gap: .375rem;
            border-radius: 9999px;
            padding: .38rem .65rem;
            font-size: .62rem;
            font-weight: 850;
            letter-spacing: .04em;
            text-transform: uppercase;
            white-space: nowrap;
            backdrop-filter: blur(10px);
            line-height: 1;
        }

        .crop-glass-badge-white {
            color: #0f172a;
            background: rgba(255, 255, 255, .94);
            border: 1px solid rgba(255, 255, 255, .65);
        }

        .crop-glass-badge-dark {
            color: #ffffff;
            background: rgba(15, 23, 42, .68);
            border: 1px solid rgba(255, 255, 255, .22);
        }

        .crop-3d-button {
            display: inline-flex;
            align-items: center;
            gap: .375rem;
            border-radius: 9999px;
            padding: .4rem .75rem;
            font-size: .62rem;
            font-weight: 850;
            color: #ffffff;
            background: linear-gradient(135deg, var(--crop-primary, #008f00), var(--crop-accent, #f59e0b));
            border: 1px solid rgba(255, 255, 255, .25);
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: .05em;
            line-height: 1;
            box-shadow: 0 10px 24px rgba(0, 0, 0, .20);
        }
        .fi-modal-content .fi-modal-footer-actions {
    display: none !important;
}

        .crop-mini-info-card,
        .crop-routine-card {
            border-radius: .85rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: .75rem;
        }

        .dark .crop-mini-info-card,
        .dark .crop-routine-card {
            background: #020617;
            border-color: #334155;
        }

        .crop-mini-icon {
            display: inline-flex;
            height: 1.75rem;
            width: 1.75rem;
            align-items: center;
            justify-content: center;
            border-radius: .625rem;
            color: var(--crop-primary, #008f00);
            background: rgba(var(--crop-primary-rgb, 0, 143, 0), .12);
            flex-shrink: 0;
        }

        .dark .crop-mini-icon {
            color: #bbf7d0;
            background: rgba(var(--crop-primary-rgb, 0, 143, 0), .20);
        }

        .crop-progress-box {
            margin-top: .75rem;
            border-radius: .9rem;
            padding: .75rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }

        .dark .crop-progress-box {
            background: #020617;
            border-color: #334155;
        }

        .crop-progress-track {
            height: .5rem;
            overflow: hidden;
            border-radius: 9999px;
            background: #e2e8f0;
        }

        .dark .crop-progress-track {
            background: #334155;
        }

        .crop-progress-fill {
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, var(--crop-primary, #008f00), var(--crop-accent, #f59e0b));
            transition: width .7s ease;
        }

        /* Compact centered 3D modal */
        .crop-3d-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 99998;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background:
                radial-gradient(circle at top, rgba(var(--crop-primary-rgb, 0, 143, 0), .18), transparent 35%),
                rgba(2, 6, 23, .76);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
        }

        .crop-3d-modal-panel {
            width: min(92vw, 540px);
            max-height: min(76vh, 540px);
            display: flex;
            flex-direction: column;
            border-radius: 1.25rem;
            background: #ffffff;
            color: #0f172a;
            border: 1px solid rgba(var(--crop-primary-rgb, 0, 143, 0), .24);
            overflow: hidden;
            box-shadow: 0 28px 90px rgba(0, 0, 0, .48);
        }

        .dark .crop-3d-modal-panel {
            background: #0f172a;
            color: #f8fafc;
            border-color: #334155;
        }

        .crop-3d-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .8rem;
            padding: .78rem .9rem;
            border-bottom: 1px solid #e2e8f0;
            background:
                linear-gradient(135deg, rgba(var(--crop-primary-rgb, 0, 143, 0), .10), rgba(var(--crop-accent-rgb, 245, 158, 11), .08)),
                #f8fafc;
            flex-shrink: 0;
        }

        .dark .crop-3d-modal-header {
            background:
                linear-gradient(135deg, rgba(var(--crop-primary-rgb, 0, 143, 0), .16), rgba(var(--crop-accent-rgb, 245, 158, 11), .08)),
                #111827;
            border-bottom-color: #334155;
        }

        .crop-3d-modal-title {
            font-size: .95rem;
            font-weight: 850;
            color: #0f172a;
            line-height: 1.25;
        }

        .dark .crop-3d-modal-title {
            color: #f8fafc;
        }

        .crop-3d-modal-subtitle {
            margin-top: .15rem;
            font-size: .72rem;
            color: #64748b;
            font-weight: 650;
        }

        .dark .crop-3d-modal-subtitle {
            color: #94a3b8;
        }

        .crop-3d-modal-body {
            padding: .65rem;
            flex: 1;
            overflow: hidden;
            display: grid;
            place-items: center;
            background:
                radial-gradient(circle at center, rgba(var(--crop-primary-rgb, 0, 143, 0), .08), transparent 42%),
                #f8fafc;
        }

        .dark .crop-3d-modal-body {
            background:
                radial-gradient(circle at center, rgba(var(--crop-primary-rgb, 0, 143, 0), .10), transparent 42%),
                #020617;
        }

        .crop-3d-viewer-frame {
            width: 100%;
            border-radius: 1rem;
            padding: .45rem;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .6);
        }

        .dark .crop-3d-viewer-frame {
            background: #111827;
            border-color: #334155;
            box-shadow: none;
        }

        .crop-3d-model-viewer {
            width: 100%;
            height: min(42vh, 320px);
            min-height: 230px;
            border-radius: .85rem;
            background: #f8fafc;
        }

        .dark .crop-3d-model-viewer {
            background: #020617;
        }

        .crop-3d-close-btn {
            display: none;
            height: 2rem;
            width: 2rem;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            background: rgba(var(--crop-primary-rgb, 0, 143, 0), .10);
            color: #0f172a;
            border: 1px solid rgba(var(--crop-primary-rgb, 0, 143, 0), .18);
            cursor: pointer;
            flex-shrink: 0;
            transition: transform .2s ease, background .2s ease;
        }

        .dark .crop-3d-close-btn {
            color: #f8fafc;
            background: #1e293b;
            border-color: #334155;
        }

        .crop-3d-close-btn:hover {
            transform: scale(1.06);
            background: rgba(var(--crop-primary-rgb, 0, 143, 0), .16);
        }

        @media (max-width: 640px) {
            .crop-season-media {
                min-height: 12rem;
            }

            .crop-glass-badge {
                padding: .32rem .5rem;
                font-size: .55rem;
            }

            .crop-3d-button {
                padding: .32rem .55rem;
                font-size: .55rem;
            }

            .crop-3d-modal-backdrop {
                padding: .75rem;
            }

            .crop-3d-modal-panel {
                width: 94vw;
                max-height: 72vh;
                border-radius: 1.05rem;
            }

            .crop-3d-modal-header {
                padding: .68rem .75rem;
            }

            .crop-3d-modal-title {
                font-size: .82rem;
            }

            .crop-3d-modal-subtitle {
                font-size: .66rem;
            }

            .crop-3d-modal-body {
                padding: .5rem;
            }

            .crop-3d-viewer-frame {
                padding: .35rem;
                border-radius: .85rem;
            }

            .crop-3d-model-viewer {
                height: 38vh;
                min-height: 220px;
                max-height: 290px;
                border-radius: .72rem;
            }
        }
    </style>
@endonce

@php
    /** @var \App\Models\CropSeason $record */

    $insight = $record->stage_insight;
    $compact = $compact ?? false;
    $urgency = $record->visual_urgency;

    $statusColor = match ($urgency) {
        'success' => '#16a34a',
        'warning' => '#f59e0b',
        'danger' => '#dc2626',
        'info' => '#0ea5e9',
        default => '#64748b',
    };

    $progress = max(0, min(100, (int) ($record->growth_progress_percent ?? 0)));

    $stageLabel = $insight['stage_label'] ?? str($record->growth_stage)->replace('_', ' ')->title();
    $urgencyLabel = $insight['urgency_label'] ?? 'Monitor';
    $locationLabel = $record->fieldPartition?->name ?? ($record->farmField?->name ?? 'No field assigned');
    $healthLabel = $record->health_status ? str($record->health_status)->title() : 'Good';

    $modelUrl = $record->has_stage_model ? ($record->stage_model_url ?? null) : null;
@endphp

<article x-data="{ open3dViewer: false }" class="crop-season-card-premium">
    <div class="grid grid-cols-1 lg:grid-cols-12">
        <div class="crop-season-media lg:col-span-5">
            <img
                src="{{ $record->stage_image_url }}"
                alt="{{ $record->name }}"
                class="absolute inset-0 h-full w-full object-cover"
                loading="lazy"
            >

            <div
                class="absolute inset-0"
                style="background: linear-gradient(to top, rgba(2, 6, 23, .85), rgba(2, 6, 23, .30), rgba(2, 6, 23, .05));"
            ></div>

            <div class="absolute left-3 right-3 top-3 flex flex-wrap items-center gap-1.5">
                <span class="crop-glass-badge crop-glass-badge-white">
                    <x-heroicon-o-sparkles class="h-3.5 w-3.5" />
                    {{ $stageLabel }}
                </span>

                <span class="crop-glass-badge crop-glass-badge-dark">
                    <x-heroicon-o-chart-bar-square class="h-3.5 w-3.5" />
                    {{ $progress }}%
                </span>

                @if ($record->has_stage_model && filled($modelUrl))
                    <button type="button" x-on:click="open3dViewer = true" class="crop-3d-button">
                        <x-heroicon-o-cube-transparent class="h-3.5 w-3.5" />
                        3D View
                    </button>
                @endif
            </div>

            <div class="absolute bottom-0 left-0 right-0 p-4 sm:p-5">
                <h2 class="text-xl font-bold leading-tight text-white sm:text-2xl">
                    {{ $record->name }}
                </h2>

                <p class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs font-semibold text-white/90 sm:text-sm">
                    <span>{{ $record->crop_name }}</span>
                    <span class="text-white/50">•</span>
                    <span>{{ $locationLabel }}</span>
                </p>
            </div>
        </div>

        <div class="p-3.5 sm:p-4 lg:col-span-7">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <div
                        class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wide"
                        style="background: rgba(var(--crop-primary-rgb, 0, 143, 0), .12); color: var(--crop-primary, #008f00); border: 1px solid rgba(var(--crop-primary-rgb, 0, 143, 0), .22);"
                    >
                        <x-heroicon-o-cpu-chip class="h-3.5 w-3.5" />
                        Crop Intelligence
                    </div>

                    <div class="mt-2 text-base font-bold text-slate-950 dark:text-white sm:text-lg">
                        {{ $urgencyLabel }}
                    </div>

                    <p class="mt-1 max-w-2xl text-xs leading-5 text-slate-500 dark:text-slate-400 sm:text-sm">
                        Stage guidance from growth, health, watering, root, shoot, and routine signals.
                    </p>
                </div>

                <span
                    class="inline-flex w-fit shrink-0 items-center gap-2 rounded-full px-3 py-1.5 text-xs font-bold"
                    style="background: {{ $statusColor }}15; color: {{ $statusColor }}; border: 1px solid {{ $statusColor }}40;"
                >
                    <span class="h-2 w-2 rounded-full" style="background: {{ $statusColor }};"></span>
                    {{ $healthLabel }}
                </span>
            </div>

            <div class="crop-progress-box">
                <div class="mb-2 flex items-center justify-between gap-4 text-xs">
                    <span class="font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        Growth Progress
                    </span>

                    <span
                        class="rounded-full px-2.5 py-1 text-xs font-bold"
                        style="background: rgba(var(--crop-primary-rgb, 0, 143, 0), .12); color: var(--crop-primary, #008f00);"
                    >
                        {{ $progress }}%
                    </span>
                </div>

                <div class="crop-progress-track">
                    <div class="crop-progress-fill" style="width: {{ $progress }}%;"></div>
                </div>
            </div>

            <div class="mt-3 grid grid-cols-2 gap-2.5">
                <div class="crop-mini-info-card">
                    <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        <span class="crop-mini-icon">
                            <x-heroicon-o-cloud class="h-4 w-4" />
                        </span>
                        Watering
                    </div>

                    <p class="mt-2 text-xs leading-5 text-slate-600 dark:text-slate-300">
                        {{ $record->watering_advice }}
                    </p>
                </div>

                <div class="crop-mini-info-card">
                    <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        <span class="crop-mini-icon">
                            <x-heroicon-o-arrow-trending-down class="h-4 w-4" />
                        </span>
                        Roots
                    </div>

                    <p class="mt-2 text-xs leading-5 text-slate-600 dark:text-slate-300">
                        {{ $record->root_status }}
                    </p>
                </div>

                <div class="crop-mini-info-card">
                    <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        <span class="crop-mini-icon">
                            <x-heroicon-o-arrow-trending-up class="h-4 w-4" />
                        </span>
                        Shoots
                    </div>

                    <p class="mt-2 text-xs leading-5 text-slate-600 dark:text-slate-300">
                        {{ $record->shoot_status }}
                    </p>
                </div>

                <div class="crop-mini-info-card">
                    <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        <span class="crop-mini-icon">
                            <x-heroicon-o-bolt class="h-4 w-4" />
                        </span>
                        Action
                    </div>

                    <p class="mt-2 text-xs font-semibold leading-5 text-slate-950 dark:text-white">
                        {{ $record->next_action_advice }}
                    </p>
                </div>
            </div>

            @unless ($compact)
                <div class="crop-routine-card mt-3">
                    <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        <span class="crop-mini-icon">
                            <x-heroicon-o-clipboard-document-check class="h-4 w-4" />
                        </span>
                        Care Routine
                    </div>

                    <p class="mt-2 text-xs leading-5 text-slate-600 dark:text-slate-300">
                        {{ $record->care_routine_advice }}
                    </p>
                </div>
            @endunless
        </div>
    </div>

    @if ($record->has_stage_model && filled($modelUrl))
        <template x-teleport="body">
            <div
                x-show="open3dViewer"
                x-cloak
                x-transition.opacity.duration.200ms
                class="crop-3d-modal-backdrop"
                x-on:keydown.escape.window="open3dViewer = false"
                x-on:click.self="open3dViewer = false"
            >
                <div
                    x-show="open3dViewer"
                    x-transition.scale.origin.center.duration.200ms
                    class="crop-3d-modal-panel"
                    x-on:click.stop
                >
                    <div class="crop-3d-modal-header">
                        <div class="min-w-0 flex-1">
                            <h3 class="crop-3d-modal-title truncate">
                                {{ $record->name }} - 3D Growth View
                            </h3>

                            <p class="crop-3d-modal-subtitle truncate">
                                {{ $record->crop_name }} • {{ $progress }}% Growth Progress
                            </p>
                        </div>

                        <button
                            type="button"
                            x-on:click="open3dViewer = false"
                            class="crop-3d-close-btn"
                            aria-label="Close 3D viewer"
                        >
                            <x-heroicon-o-x-mark class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="crop-3d-modal-body">
                        <div class="crop-3d-viewer-frame">
                            <model-viewer
                                src="{{ $modelUrl }}"
                                alt="{{ $record->name }} 3D model"
                                camera-controls
                                auto-rotate
                                shadow-intensity="1"
                                exposure="1"
                                loading="lazy"
                                class="crop-3d-model-viewer"
                            ></model-viewer>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    @endif
</article>
