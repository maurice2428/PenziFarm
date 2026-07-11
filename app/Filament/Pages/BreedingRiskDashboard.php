<?php

namespace App\Filament\Pages;

use App\Filament\Pages\ProgenyExplorer;
use App\Filament\Resources\AnimalResource;
use App\Models\Animal;
use App\Services\BreedingRiskAnalyticsService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class BreedingRiskDashboard extends Page
{
    protected static ?string $navigationIcon =
        'heroicon-o-shield-exclamation';

    protected static ?string $navigationGroup =
        'Breeding Management';

    protected static ?string $navigationLabel =
        'Risk Dashboard';

    protected static ?string $title =
        'Breeding Performance & Risk';

    protected static ?int $navigationSort = 5;

    protected static string $view =
        'filament.pages.breeding-risk-dashboard';

    public string $sexFilter = 'all';

    public string $recommendationFilter = 'all';

    public int $minimumEvidence = 1;

    public int $limit = 16;

    public ?int $selectedAnimalId = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->can(
            'view breeding risk dashboard'
        )
            || auth()->user()?->can(
                'view progeny analytics'
            )
            || auth()->user()?->can(
                'view breeding outcomes'
            )
            || auth()->user()?->hasAnyRole([
                'Administrator',
                'Admin',
                'Manager',
                'Veterinary Officer',
            ])
            || false;
    }

    public function mount(): void
    {
        $requestedAnimal = request()->integer('animal');

        if ($requestedAnimal > 0) {
            $this->selectedAnimalId = $requestedAnimal;

            return;
        }

        $dashboard = app(
            BreedingRiskAnalyticsService::class
        )->dashboard(
            sexFilter: $this->sexFilter,
            recommendationFilter:
                $this->recommendationFilter,
            minimumEvidence: $this->minimumEvidence,
            limit: $this->limit,
        );

        $this->selectedAnimalId = data_get(
            $dashboard,
            'lowest.0.animal.id'
        );
    }

    public function selectAnimal(int $animalId): void
    {
        $exists = Animal::query()
            ->whereKey($animalId)
            ->exists();

        if (! $exists) {
            Notification::make()
                ->title('Animal record not found')
                ->danger()
                ->send();

            return;
        }

        $this->selectedAnimalId = $animalId;
    }

    public function clearSelection(): void
    {
        $this->selectedAnimalId = null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('openAnimalProfile')
                ->label('Animal Profile')
                ->icon('heroicon-o-identification')
                ->color('success')
                ->visible(
                    fn (): bool =>
                        filled($this->selectedAnimalId)
                        && (
                            auth()->user()?->can(
                                'view animals'
                            )
                            ?? false
                        )
                )
                ->url(
                    fn (): string => AnimalResource::getUrl(
                        'profile',
                        [
                            'record' =>
                                $this->selectedAnimalId,
                        ]
                    )
                )
                ->openUrlInNewTab(),

            Actions\Action::make('openProgeny')
                ->label('Progeny Tree')
                ->icon('heroicon-o-share')
                ->color('info')
                ->visible(
                    fn (): bool =>
                        filled($this->selectedAnimalId)
                )
                ->url(
                    fn (): string => ProgenyExplorer::getUrl([
                        'animal' =>
                            $this->selectedAnimalId,
                    ])
                )
                ->openUrlInNewTab(),

            Actions\Action::make('printHistory')
                ->label('Print History')
                ->icon('heroicon-o-printer')
                ->color('danger')
                ->visible(
                    fn (): bool =>
                        filled($this->selectedAnimalId)
                )
                ->url(
                    fn (): string => route(
                        'breeding.performance.pdf',
                        [
                            'animal' =>
                                $this->selectedAnimalId,
                        ]
                    )
                )
                ->openUrlInNewTab(),
        ];
    }

    protected function getViewData(): array
    {
        $service = app(
            BreedingRiskAnalyticsService::class
        );

        $dashboard = $service->dashboard(
            sexFilter: $this->sexFilter,
            recommendationFilter:
                $this->recommendationFilter,
            minimumEvidence: $this->minimumEvidence,
            limit: $this->limit,
        );

        $selectedAnimal = $this->selectedAnimalId
            ? Animal::query()
                ->with([
                    'breed:id,breed_name',
                    'location:id,name',
                    'sire:id,tag_number',
                    'dam:id,tag_number',
                ])
                ->find($this->selectedAnimalId)
            : null;

        $selectedSnapshot = $selectedAnimal
            ? $service->animalSnapshot(
                $selectedAnimal
            )
            : null;

        return [
            'dashboard' => $dashboard,
            'selectedAnimal' => $selectedAnimal,
            'selectedSnapshot' => $selectedSnapshot,
            'farmName' => setting(
                'farm.name',
                'Penzi Farm Limited'
            ),
            'farmTagline' => setting(
                'farm.tagline',
                'Nurturing Quality, Inspiring Global Standards'
            ),
            'primaryColor' => $this->safeColor(
                setting('theme.primary', '#14532d'),
                '#14532d'
            ),
            'secondaryColor' => $this->safeColor(
                setting('theme.secondary', '#166534'),
                '#166534'
            ),
            'accentColor' => $this->safeColor(
                setting('theme.accent', '#b7791f'),
                '#b7791f'
            ),
            'successColor' => $this->safeColor(
                setting('theme.success', '#16a34a'),
                '#16a34a'
            ),
            'dangerColor' => $this->safeColor(
                setting('theme.danger', '#dc2626'),
                '#dc2626'
            ),
        ];
    }

    private function safeColor(
        mixed $value,
        string $fallback
    ): string {
        $color = trim((string) $value);

        return preg_match(
            '/^#[0-9a-fA-F]{6}$/',
            $color
        )
            ? $color
            : $fallback;
    }
}
