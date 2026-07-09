<?php

namespace App\Filament\Pages;

use App\Models\CropCareTask;
use App\Models\CropSeason;
use App\Models\NurseryBatch;
use Filament\Pages\Page;

class CropIntelligenceDashboard extends Page
{
    protected static ?string $navigationGroup = 'Crop Farming';

    protected static ?string $navigationLabel = 'Crop(s) Intelligence';

    protected static ?string $title = 'Crop Intelligence';

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?int $navigationSort = 0;

    protected static string $view = 'filament.pages.crop-intelligence-dashboard';
     public function getTitle(): string
    {
        return '';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view crop seasons')
            || auth()->user()?->can('view crops')
            || auth()->user()?->hasRole('Admin')
            || auth()->user()?->hasRole('Administrator')
            || false;
    }

    protected function getViewData(): array
    {
        $seasons = CropSeason::query()
            ->with(['cropCatalog', 'farmField', 'fieldPartition'])
            ->whereIn('status', ['planned', 'active'])
            ->latest()
            ->limit(8)
            ->get();

        $nurseryBatches = NurseryBatch::query()
            ->with(['cropCatalog', 'farmField', 'fieldPartition'])
            ->whereIn('status', ['active', 'ready'])
            ->latest()
            ->limit(6)
            ->get();

        $dueTasks = CropCareTask::query()
            ->with(['cropSeason.cropCatalog', 'nurseryBatch.cropCatalog'])
            ->where('status', 'pending')
            ->whereDate('due_date', '<=', now('Africa/Nairobi')->addDays(7)->toDateString())
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        return [
            'seasons' => $seasons,
            'nurseryBatches' => $nurseryBatches,
            'dueTasks' => $dueTasks,
            'stats' => [
                'activeSeasons' => CropSeason::query()->where('status', 'active')->count(),
                'dueSoon' => CropSeason::query()
                    ->where('status', 'active')
                    ->whereDate('expected_harvest_from', '<=', now('Africa/Nairobi')->addDays(14)->toDateString())
                    ->count(),
                'nurseryReady' => NurseryBatch::query()->where('status', 'ready')->count(),
                'pendingTasks' => CropCareTask::query()->where('status', 'pending')->count(),
            ],
        ];
    }
}
