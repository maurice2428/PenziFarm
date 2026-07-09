<?php

namespace App\Filament\Widgets\Dashboard;

use App\Models\Animal;
use Filament\Widgets\Widget;

class AnimalLifecycleStatsWidget extends Widget
{
    protected static string $view = 'filament.widgets.dashboard.animal-lifecycle-stats-widget';

    protected int | string | array $columnSpan = 'full';
    public static function canView(): bool
{
    return auth()->user()?->can('view animals') ?? false;
}

    public function getViewData(): array
    {
        return [
            'cards' => [
                [
                    'label' => 'Current Animals',
                    'value' => Animal::query()
                        ->where('status', 'Active')
                        ->where('is_archived', false)
                        ->count(),
                    'description' => 'Active non-archived records',
                    'url' => url('/admin/animals/current-animals'),
                    'theme' => 'emerald',
                    'icon' => 'heroicon-o-check-badge',
                ],
                [
                    'label' => 'Sold Animals',
                    'value' => Animal::query()
                        ->where('status', 'Sold')
                        ->count(),
                    'description' => 'Disposed through sales',
                    'url' => url('/admin/animals/sold-animals'),
                    'theme' => 'amber',
                    'icon' => 'heroicon-o-banknotes',
                ],
                [
                    'label' => 'Dead Animals',
                    'value' => Animal::query()
                        ->where('status', 'Dead')
                        ->count(),
                    'description' => 'Mortality records',
                    'url' => url('/admin/animals/dead-culled-animals'),
                    'theme' => 'rose',
                    'icon' => 'heroicon-o-exclamation-triangle',
                ],
                [
                    'label' => 'Culled Animals',
                    'value' => Animal::query()
                        ->where('status', 'Culled')
                        ->count(),
                    'description' => 'Culled stock',
                    'url' => url('/admin/animals/dead-culled-animals'),
                    'theme' => 'slate',
                    'icon' => 'heroicon-o-no-symbol',
                ],
                [
                    'label' => 'Archived Animals',
                    'value' => Animal::query()
                        ->where('is_archived', true)
                        ->count(),
                    'description' => 'Moved to archive',
                    'url' => url('/admin/animals/archived-animals'),
                    'theme' => 'blue',
                    'icon' => 'heroicon-o-archive-box',
                ],
                [
                    'label' => 'Breeding Pool',
                    'value' => Animal::query()
                        ->where('is_breeder', true)
                        ->where('is_archived', false)
                        ->count(),
                    'description' => 'Retained for breeding',
                    'url' => url('/admin/animals/all-animals'),
                    'theme' => 'green',
                    'icon' => 'heroicon-o-sparkles',
                ],
            ],
        ];
    }
}
