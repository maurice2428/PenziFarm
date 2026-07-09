<?php

namespace App\Filament\Clusters\Livestock;

use App\Filament\Clusters\Livestock\Animals\Pages\CurrentAnimals;
use Filament\Clusters\Cluster;

class Animals extends Cluster
{
    protected static ?string $navigationGroup = 'Livestock';

    protected static ?string $navigationLabel = 'Animals';

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.clusters.livestock.animals';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('view animals') ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view animals') ?? false;
    }

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('view animals'), 403);

        $this->redirect(CurrentAnimals::getUrl());
    }
}
