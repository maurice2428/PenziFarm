<?php

namespace App\Filament\Clusters\Livestock\Animals\Pages;

use App\Models\Animal;
use Illuminate\Database\Eloquent\Builder;

class CurrentAnimals extends BaseAnimalListPage
{
    protected static ?string $navigationLabel = 'Current Animals';

    protected static ?string $title = 'Current Animals';

    protected static ?string $navigationIcon = 'heroicon-o-check-badge';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return (string) Animal::query()
            ->where('status', 'Active')
            ->where('is_archived', false)
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    protected function getFilteredQuery(): Builder
    {
        return Animal::query()
            ->with(['breed', 'location'])
            ->where('status', 'Active')
            ->where('is_archived', false);
    }
    public static function canAccess(): bool
{
    return auth()->user()?->can('view animals') ?? false;
}
}
