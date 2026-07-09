<?php

namespace App\Filament\Clusters\Livestock\Animals\Pages;

use App\Models\Animal;
use Illuminate\Database\Eloquent\Builder;

class SoldAnimals extends BaseAnimalListPage
{
    protected static ?string $navigationLabel = 'Sold Animals';

    protected static ?string $title = 'Sold Animals';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return (string) Animal::query()
            ->where('status', 'Sold')
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    protected function getFilteredQuery(): Builder
    {
        return Animal::query()
            ->with(['breed', 'location'])
            ->where('status', 'Sold');
    }
    public static function canAccess(): bool
{
    return auth()->user()?->can('view sold animals') ?? false;
}
}
