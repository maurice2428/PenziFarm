<x-filament-panels::page>
    @php
        $primaryColor = trim(setting('theme.primary', '#008f00'));
        $secondaryColor = trim(setting('theme.secondary', '#111827'));
        $accentColor = trim(setting('theme.accent', '#f59e0b'));
    @endphp

    <style>
        .audit-settings-page {
            --audit-primary: {{ $primaryColor }};
            --audit-secondary: {{ $secondaryColor }};
            --audit-accent: {{ $accentColor }};

            --audit-card: #ffffff;
            --audit-soft: #f8fafc;
            --audit-border: #dbe3ea;
            --audit-text: #0f172a;
            --audit-muted: #475569;
            --audit-shadow: 0 20px 55px rgba(15, 23, 42, .10);
        }

        .dark .audit-settings-page {
            --audit-card: #0f172a;
            --audit-soft: #111827;
            --audit-border: #334155;
            --audit-text: #f8fafc;
            --audit-muted: #cbd5e1;
            --audit-shadow: 0 20px 55px rgba(0, 0, 0, .35);
        }

        .audit-settings-card {
            background: var(--audit-card);
            border: 1px solid var(--audit-border);
            border-radius: 2rem;
            box-shadow: var(--audit-shadow);
            color: var(--audit-text);
            overflow: hidden;
        }

        .audit-settings-hero {
            background:
                radial-gradient(circle at top left, color-mix(in srgb, var(--audit-accent) 35%, transparent), transparent 30%),
                linear-gradient(135deg, var(--audit-primary), var(--audit-secondary));
            color: #ffffff;
        }

        .audit-settings-icon {
            display: flex;
            height: 4.25rem;
            width: 4.25rem;
            align-items: center;
            justify-content: center;
            border-radius: 1.4rem;
            background: rgba(255, 255, 255, .16);
            border: 1px solid rgba(255, 255, 255, .24);
            box-shadow: 0 16px 40px rgba(0, 0, 0, .18);
            backdrop-filter: blur(14px);
        }

        .audit-settings-pill {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, .16);
            border: 1px solid rgba(255, 255, 255, .24);
            padding: .55rem .9rem;
            font-size: .75rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .14em;
            color: #ffffff;
            backdrop-filter: blur(14px);
        }

        .audit-settings-body {
            background: var(--audit-soft);
            border-top: 1px solid var(--audit-border);
        }
    </style>

    <div class="audit-settings-page space-y-6">
        <div class="audit-settings-card">
            <div class="audit-settings-hero p-6 sm:p-8">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                        <!--<div class="audit-settings-icon">
                            <x-heroicon-o-cog-6-tooth class="h-9 w-9" />
                        </div>-->

                        <div>
                            <div
                                class="mb-4 flex flex-wrap items-center gap-2 text-xs font-black uppercase tracking-[0.16em] text-white/75">
                                <a href="{{ \App\Filament\Pages\AuditDashboard::getUrl() }}"
                                    class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-white/85 ring-1 ring-white/15 transition hover:bg-white/20 hover:text-white">
                                    <x-heroicon-o-shield-check class="h-4 w-4" />
                                    System Audit
                                </a>

                                <x-heroicon-o-chevron-right class="h-4 w-4 text-white/50" />

                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-white/85 ring-1 ring-white/15">
                                    <x-heroicon-o-cog-6-tooth class="h-4 w-4" />
                                    Audit Settings
                                </span>

                                <x-heroicon-o-chevron-right class="h-4 w-4 text-white/50" />

                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full bg-white/20 px-3 py-1.5 text-white ring-1 ring-white/25">
                                    Settings
                                </span>
                            </div>
                            <div class="audit-settings-pill w-fit">
                                <x-heroicon-o-shield-check class="h-4 w-4" />
                                System Audit
                            </div>

                            <h1 class="mt-4 text-3xl font-black tracking-tight text-white">
                                Audit Settings
                            </h1>

                            <p class="mt-2 max-w-3xl text-sm leading-7 text-white/85">
                                Configure audit emails, high-risk alerts, session expiry, page tracking,
                                Livewire logging, and database notifications from one secure control panel.
                            </p>
                        </div>
                    </div>

                    <div class="relative overflow-hidden rounded-[1.6rem] p-[1px] shadow-2xl shadow-black/10">
                        {{-- Gradient border --}}
                        <div
                            class="absolute inset-0 rounded-[1.6rem] bg-gradient-to-br from-white/45 via-white/10 to-white/25">
                        </div>

                        {{-- Inner glass card --}}
                        <div class="relative rounded-[1.55rem] bg-white/15 p-5 ring-1 ring-white/25 backdrop-blur-xl">
                            <div
                                class="absolute inset-0 rounded-[1.55rem] bg-gradient-to-br from-white/15 via-transparent to-black/10">
                            </div>

                            <div class="relative flex items-center gap-4">
                                <div
                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/20 text-white shadow-lg shadow-black/10 ring-1 ring-white/30">
                                    <x-heroicon-o-finger-print class="h-6 w-6" />
                                </div>

                                <div>
                                    <div class="text-sm font-black tracking-wide text-white">
                                        Session Control
                                    </div>

                                    <div class="mt-1 text-xs font-semibold leading-5 text-white/75">
                                        Email alerts, expiry and tracking rules
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <form wire:submit="save" class="space-y-6">
            {{ $this->form }}

            <div class="flex justify-end">
                <x-filament::button type="submit" icon="heroicon-o-check-circle" size="lg">
                    Save Audit Settings
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
