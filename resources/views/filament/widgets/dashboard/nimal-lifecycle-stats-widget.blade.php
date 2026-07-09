<x-filament-widgets::widget>
    <x-filament::section>
        <style>
            .animal-stat-grid {
                display: grid;
                grid-template-columns: repeat(1, minmax(0, 1fr));
                gap: 1rem;
            }

            @media (min-width: 768px) {
                .animal-stat-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            @media (min-width: 1280px) {
                .animal-stat-grid {
                    grid-template-columns: repeat(3, minmax(0, 1fr));
                }
            }

            .animal-stat-card {
                position: relative;
                display: block;
                overflow: hidden;
                border-radius: 1.5rem;
                border: 1px solid rgba(229, 231, 235, 1);
                background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(249,250,251,0.96));
                padding: 1.2rem;
                text-decoration: none;
                box-shadow: 0 10px 28px rgba(2, 6, 23, 0.04);
                transition: transform 240ms ease, box-shadow 240ms ease, border-color 240ms ease;
            }

            .animal-stat-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 18px 38px rgba(2, 6, 23, 0.08);
            }

            .animal-stat-card__top {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 1rem;
            }

            .animal-stat-card__icon {
                width: 3rem;
                height: 3rem;
                min-width: 3rem;
                border-radius: 1rem;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .animal-stat-card__label {
                font-size: 0.98rem;
                font-weight: 700;
                color: rgb(75 85 99);
                line-height: 1.35;
            }

            .animal-stat-card__value {
                margin-top: 0.65rem;
                font-size: 2.2rem;
                line-height: 1;
                font-weight: 800;
                color: rgb(17 24 39);
            }

            .animal-stat-card__desc {
                margin-top: 0.7rem;
                font-size: 0.92rem;
                font-weight: 600;
            }

            .animal-stat-card__footer {
                margin-top: 1rem;
                display: inline-flex;
                align-items: center;
                gap: 0.45rem;
                font-size: 0.82rem;
                font-weight: 700;
                opacity: 0.9;
            }

            .animal-theme-emerald:hover { border-color: rgba(16,185,129,0.26); }
            .animal-theme-emerald .animal-stat-card__icon { background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #047857; }
            .animal-theme-emerald .animal-stat-card__desc,
            .animal-theme-emerald .animal-stat-card__footer { color: #059669; }

            .animal-theme-amber:hover { border-color: rgba(245,158,11,0.26); }
            .animal-theme-amber .animal-stat-card__icon { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #b45309; }
            .animal-theme-amber .animal-stat-card__desc,
            .animal-theme-amber .animal-stat-card__footer { color: #d97706; }

            .animal-theme-rose:hover { border-color: rgba(244,63,94,0.26); }
            .animal-theme-rose .animal-stat-card__icon { background: linear-gradient(135deg, #ffe4e6, #fecdd3); color: #be123c; }
            .animal-theme-rose .animal-stat-card__desc,
            .animal-theme-rose .animal-stat-card__footer { color: #e11d48; }

            .animal-theme-slate:hover { border-color: rgba(100,116,139,0.26); }
            .animal-theme-slate .animal-stat-card__icon { background: linear-gradient(135deg, #e2e8f0, #cbd5e1); color: #334155; }
            .animal-theme-slate .animal-stat-card__desc,
            .animal-theme-slate .animal-stat-card__footer { color: #475569; }

            .animal-theme-blue:hover { border-color: rgba(59,130,246,0.26); }
            .animal-theme-blue .animal-stat-card__icon { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #1d4ed8; }
            .animal-theme-blue .animal-stat-card__desc,
            .animal-theme-blue .animal-stat-card__footer { color: #2563eb; }

            .animal-theme-green:hover { border-color: rgba(34,197,94,0.26); }
            .animal-theme-green .animal-stat-card__icon { background: linear-gradient(135deg, #dcfce7, #bbf7d0); color: #15803d; }
            .animal-theme-green .animal-stat-card__desc,
            .animal-theme-green .animal-stat-card__footer { color: #16a34a; }
        </style>

        <div class="animal-stat-grid">
            @foreach ($cards as $card)
                <a href="{{ $card['url'] }}" class="animal-stat-card animal-theme-{{ $card['theme'] }}">
                    <div class="animal-stat-card__top">
                        <div>
                            <div class="animal-stat-card__label">{{ $card['label'] }}</div>
                            <div class="animal-stat-card__value">{{ number_format($card['value']) }}</div>
                            <div class="animal-stat-card__desc">{{ $card['description'] }}</div>
                        </div>

                        <div class="animal-stat-card__icon">
                            <x-dynamic-component :component="$card['icon']" class="h-6 w-6" />
                        </div>
                    </div>

                    <div class="animal-stat-card__footer">
                        <span>Open view</span>
                        <x-heroicon-o-arrow-right class="h-4 w-4" />
                    </div>
                </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
