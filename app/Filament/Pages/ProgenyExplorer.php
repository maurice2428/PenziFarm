<?php

namespace App\Filament\Pages;

use App\Models\Animal;
use App\Models\AnimalBreedingReview;
use App\Services\ProgenyAnalyticsService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ProgenyExplorer extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-share';

    protected static ?string $navigationGroup = 'Breeding Management';

    protected static ?string $navigationLabel = 'Progeny & Heredity';

    protected static ?string $title = 'Progeny & Heredity Explorer';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.progeny-explorer';

    //protected ?string $maxContentWidth = 'full';

    public ?int $animalId = null;

    public int $generations = 3;

    public string $mode = 'descendants';

    public function mount(): void
    {
        $requestedAnimal = request()->integer('animal');

        $this->animalId = $requestedAnimal > 0
            ? $requestedAnimal
            : Animal::query()
                ->where('is_archived', false)
                ->where(function ($query): void {
                    $query->where('is_breeder', true)
                        ->orWhereHas('offspringAsSire')
                        ->orWhereHas('offspringAsDam');
                })
                ->orderByDesc('is_breeder')
                ->orderBy('tag_number')
                ->value('id');

        $this->generations = max(1, min(5, request()->integer('generations', 3)));
        $this->mode = request()->string('mode')->toString() === 'ancestors'
            ? 'ancestors'
            : 'descendants';
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->can('view progeny analytics')
            || $user?->hasAnyRole(['Administrator', 'Admin', 'Manager', 'Veterinary Officer'])
            || false;
    }

    public function getAnimalOptionsProperty(): array
    {
        return Animal::query()
            ->with('breed:id,breed_name')
            ->where('is_archived', false)
            ->where(function ($query): void {
                $query->where('is_breeder', true)
                    ->orWhereHas('offspringAsSire')
                    ->orWhereHas('offspringAsDam')
                    ->orWhereNotNull('sire_id')
                    ->orWhereNotNull('dam_id');
            })
            ->orderBy('sex')
            ->orderBy('tag_number')
            ->get()
            ->mapWithKeys(fn (Animal $animal): array => [
                $animal->id => sprintf(
                    '%s — %s — %s',
                    $animal->tag_number,
                    $animal->breed?->breed_name ?? 'Unknown breed',
                    $animal->sex
                ),
            ])
            ->all();
    }

    public function getSelectedAnimalProperty(): ?Animal
    {
        if (! $this->animalId) {
            return null;
        }

        return Animal::query()
            ->with([
                'breed:id,breed_name',
                'location:id,name',
                'sire.breed:id,breed_name',
                'dam.breed:id,breed_name',
            ])
            ->find($this->animalId);
    }

    public function getTreeProperty(): ?array
    {
        $animal = $this->selectedAnimal;

        if (! $animal) {
            return null;
        }

        return app(ProgenyAnalyticsService::class)->tree(
            $animal,
            $this->generations,
            $this->mode
        );
    }

    public function getMetricsProperty(): array
    {
        $animal = $this->selectedAnimal;

        return $animal
            ? app(ProgenyAnalyticsService::class)->metrics($animal)
            : [];
    }

    public function getLatestReviewProperty(): ?AnimalBreedingReview
    {
        $animal = $this->selectedAnimal;

        return $animal
            ? app(ProgenyAnalyticsService::class)->latestReview($animal)
            : null;
    }

    public function getTopSiresProperty()
    {
        return app(ProgenyAnalyticsService::class)->topSires(4);
    }

    public function getTopDamsProperty()
    {
        return app(ProgenyAnalyticsService::class)->topDams(4);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('printReport')
                ->label('Print Heredity PDF')
                ->icon('heroicon-o-printer')
                ->color('danger')
                ->visible(fn (): bool => filled($this->animalId))
                ->url(fn (): string => route('breeding.progeny.pdf', [
                    'animal' => $this->animalId,
                    'generations' => $this->generations,
                    'mode' => $this->mode,
                ]))
                ->openUrlInNewTab(),

            Actions\Action::make('recordDecision')
                ->label('Record Breeding Decision')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('warning')
                ->visible(fn (): bool =>
                    filled($this->animalId)
                    && (
                        auth()->user()?->can('manage breeding reviews')
                        || auth()->user()?->hasAnyRole(['Administrator', 'Admin', 'Manager', 'Veterinary Officer'])
                        || false
                    )
                )
                ->fillForm(fn (): array => [
                    'recommendation' => $this->metrics['recommendation'] ?? 'monitor',
                    'performance_score' => $this->metrics['score'] ?? null,
                    'reason' => $this->metrics['reason'] ?? null,
                ])
                ->form([
                    Forms\Components\Select::make('recommendation')
                        ->options([
                            'retain' => 'Retain for Breeding',
                            'monitor' => 'Monitor Closely',
                            'sell' => 'Recommend for Sale',
                            'cull' => 'Recommend for Culling',
                            'insufficient_data' => 'Insufficient Data',
                        ])
                        ->required()
                        ->native(false),
                    Forms\Components\TextInput::make('performance_score')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('/100'),
                    Forms\Components\Textarea::make('reason')
                        ->required()
                        ->rows(5)
                        ->columnSpanFull(),
                ])
                ->action(function (array $data): void {
                    $animal = $this->selectedAnimal;

                    if (! $animal) {
                        return;
                    }

                    AnimalBreedingReview::create([
                        'animal_id' => $animal->id,
                        'recommendation' => $data['recommendation'],
                        'source' => 'manual_review',
                        'performance_score' => $data['performance_score'] ?? null,
                        'reason' => $data['reason'],
                        'metrics_snapshot' => $this->metrics,
                        'reviewed_by' => auth()->id(),
                        'reviewed_at' => now(),
                    ]);

                    Notification::make()
                        ->success()
                        ->title('Breeding decision recorded')
                        ->body($animal->tag_number . ' now has an auditable breeding recommendation.')
                        ->send();
                }),
        ];
    }
}
