<x-filament-widgets::widget>
    <x-filament::section>
        <style>
            .breed-cards-grid {
                display: grid;
                grid-template-columns: repeat(1, minmax(0, 1fr));
                gap: 0.9rem;
            }

            @media (min-width: 640px) {
                .breed-cards-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            @media (min-width: 1024px) {
                .breed-cards-grid {
                    grid-template-columns: repeat(3, minmax(0, 1fr));
                }
            }

            @media (min-width: 1536px) {
                .breed-cards-grid {
                    grid-template-columns: repeat(4, minmax(0, 1fr));
                }
            }

            .breed-card {
                display: block;
                border-radius: 1.35rem;
                border: 1px solid rgba(229, 231, 235, 1);

                padding: 1rem;
                text-decoration: none;
                box-shadow: 0 10px 24px rgba(2, 6, 23, 0.04);
                transition: transform 220ms ease, box-shadow 220ms ease, border-color 220ms ease;
            }

            .breed-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 18px 36px rgba(2, 6, 23, 0.08);
                border-color: rgba(20, 83, 45, 0.18);
            }

            .breed-card__head {
                display: flex;
                align-items: center;
                gap: 0.85rem;
            }

            .breed-card__avatar {
                width: 52px;
                height: 52px;
                min-width: 52px;
                border-radius: 1rem;
                overflow: hidden;
                background: #f3f4f6;
                border: 1px solid rgba(229, 231, 235, 1);
            }

            .breed-card__avatar img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .breed-card__fallback {
                width: 100%;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 0.78rem;
                font-weight: 800;
                color: #4b5563;
            }

            .breed-card__title {
                font-size: 1rem;
                font-weight: 800;
                color: #111827;
                line-height: 1.15;
                margin: 0;
            }

            .breed-card__subtitle {
                margin-top: 0.2rem;
                font-size: 0.82rem;
                color: #6b7280;
            }

            .breed-card__active {
                margin-top: 0.95rem;
                display: inline-flex;
                align-items: center;
                gap: 0.8rem;
                border-radius: 1rem;
                background: linear-gradient(135deg, #ecfdf5, #d1fae5);
                padding: 0.8rem 1rem;
                min-width: 140px;
            }

            .breed-card__active-label {
                font-size: 0.8rem;
                font-weight: 700;
                color: #065f46;
                line-height: 1.2;
            }

            .breed-card__active-value {
                font-size: 1.5rem;
                font-weight: 800;
                color: #064e3b;
                line-height: 1;
            }
        </style>

        <div class="breed-cards-grid">
            @foreach ($breeds as $breed)
                <a
                    href="{{ \App\Filament\Resources\BreedResource::getUrl('edit', ['record' => $breed]) }}"
                    class="breed-card"
                >
                    <div class="breed-card__head">
                        <div class="breed-card__avatar">
                            @if ($breed->avatar)
                                <img src="{{ asset('storage/' . $breed->avatar) }}" alt="{{ $breed->breed_name }}">
                            @else
                                <div class="breed-card__fallback">
                                    {{ strtoupper(substr($breed->breed_name, 0, 2)) }}
                                </div>
                            @endif
                        </div>

                        <div>
                            <h3 class="breed-card__title">{{ $breed->breed_name }}</h3>
                            <div class="breed-card__subtitle">{{ $breed->parent_category }}</div>
                        </div>
                    </div>

                    <div class="breed-card__active">
                        <div class="breed-card__active-label">Active Animals</div>
                        <div class="breed-card__active-value">{{ $breed->active_animals_count }}</div>
                    </div>
                </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
