<?php

namespace App\Filament\Widgets\Dashboard;

use App\Models\Breed;
use Filament\Widgets\Widget;

class AnimalBreedCardsWidget extends Widget
{
    protected static string $view = 'filament.widgets.dashboard.animal-breed-cards-widget';

    protected int | string | array $columnSpan = 'full';
public static function canView(): bool
{
    return auth()->user()?->can('view breeds') ?? false;
}

    public function getViewData(): array
    {
        $breeds = Breed::query()
            ->withCount([
                'animals as active_animals_count' => fn ($query) => $query
                    ->where('status', 'Active')
                    ->where('is_archived', false),
            ])
            ->orderBy('breed_name')
            ->get();

        return [
            'breeds' => $breeds,
        ];
    }
}
