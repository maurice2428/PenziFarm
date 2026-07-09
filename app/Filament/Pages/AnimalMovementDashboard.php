<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\Livestock\Animals as AnimalsCluster;
use App\Models\Animal;
use App\Models\AnimalGroup;
use App\Models\AnimalTransfer;
use App\Models\Location;
use Filament\Pages\Page;

class AnimalMovementDashboard extends Page
{
    protected static ?string $cluster = AnimalsCluster::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Transfer(s) Dashboard';

    protected static ?int $navigationSort = 17;

    protected static ?string $title = 'Animal Movement Dashboard';

    protected static string $view = 'filament.pages.animal-movement-dashboard';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view animal movement dashboard') ?? false;
    }

    public function getViewData(): array
    {
        $today = today('Africa/Nairobi');
        $monthStart = now('Africa/Nairobi')->startOfMonth();

        $recentTransfers = AnimalTransfer::query()
            ->with(['fromLocation', 'toLocation', 'items'])
            ->latest('transfer_date')
            ->take(8)
            ->get();

        $locationSummary = Location::query()
            ->where('is_active', true)
            ->withCount([
                'animals as active_animals_count' => fn ($query) => $query
                    ->where('status', 'Active')
                    ->where('is_archived', false),
            ])
            ->orderByDesc('active_animals_count')
            ->take(8)
            ->get();

        $pendingTransfers = AnimalTransfer::query()
            ->where('status', 'pending')
            ->count();

        $completedTransfersToday = AnimalTransfer::query()
            ->where('status', 'completed')
            ->whereDate('received_at', $today)
            ->count();

        $completedTransfersThisMonth = AnimalTransfer::query()
            ->where('status', 'completed')
            ->where('received_at', '>=', $monthStart)
            ->count();

        $activeAnimals = Animal::query()
            ->where('status', 'Active')
            ->where('is_archived', false)
            ->count();

        $animalGroups = class_exists(AnimalGroup::class)
            ? AnimalGroup::query()->where('status', 'active')->count()
            : 0;

        $autoGroups = class_exists(AnimalGroup::class)
            ? AnimalGroup::query()->where('status', 'active')->where('auto_sync', true)->count()
            : 0;

        return [
            'activeAnimals' => $activeAnimals,
            'pendingTransfers' => $pendingTransfers,
            'completedTransfersToday' => $completedTransfersToday,
            'completedTransfersThisMonth' => $completedTransfersThisMonth,
            'animalGroups' => $animalGroups,
            'autoGroups' => $autoGroups,
            'recentTransfers' => $recentTransfers,
            'locationSummary' => $locationSummary,

            'totalTransfers' => AnimalTransfer::query()->count(),
            'cancelledTransfers' => AnimalTransfer::query()->where('status', 'cancelled')->count(),
            'draftTransfers' => AnimalTransfer::query()->where('status', 'draft')->count(),
        ];
    }
}
