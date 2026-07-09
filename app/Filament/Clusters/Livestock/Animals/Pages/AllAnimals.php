<?php

namespace App\Filament\Clusters\Livestock\Animals\Pages;

use App\Models\Animal;
use Illuminate\Database\Eloquent\Builder;

class AllAnimals extends BaseAnimalListPage
{
    protected static ?string $navigationLabel = 'All Animals';

    protected static ?string $title = 'All Animals';

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?int $navigationSort = 5;

    public static function getNavigationBadge(): ?string
    {
        return (string) Animal::query()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    protected function getFilteredQuery(): Builder
    {
        return Animal::query()->with(['breed', 'location']);
    }
    public static function canAccess(): bool
{
    return auth()->user()?->can('view animals') ?? false;
}
}
