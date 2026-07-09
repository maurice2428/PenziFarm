<?php

namespace App\Filament\Clusters\Livestock\Animals\Pages;

use App\Models\Animal;
use Illuminate\Database\Eloquent\Builder;

class DeadCulledAnimals extends BaseAnimalListPage
{
    protected static ?string $navigationLabel = 'Dead & Culled Animals';

    protected static ?string $title = 'Dead & Culled Animals';

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return (string) Animal::query()
            ->whereIn('status', ['Dead', 'Culled'])
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    protected function getFilteredQuery(): Builder
    {
        return Animal::query()
            ->with(['breed', 'location'])
            ->whereIn('status', ['Dead', 'Culled']);
    }
    public static function canAccess(): bool
{
    return auth()->user()?->can('view dead culled animals') ?? false;
}
}
