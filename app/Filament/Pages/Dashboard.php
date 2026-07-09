<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Dashboard\AnimalBreedCardsWidget;
use App\Filament\Widgets\Dashboard\AnimalLifecycleStatsWidget;
use App\Filament\Widgets\Dashboard\AnimalBreedPieChartWidget;
use App\Filament\Widgets\Dashboard\AnimalStatusChartWidget;
use App\Filament\Widgets\Dashboard\CurrentAnimalsTableWidget;
use App\Filament\Widgets\HR\AttendanceTodayStats;
use App\Filament\Widgets\HR\HrAdvancedStatsWidget;
use App\Filament\Widgets\HR\PendingApprovalsStats;
use App\Filament\Widgets\HR\StaffOverviewStats;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?int $navigationSort = -2;

    protected static string $view = 'filament.pages.dashboard';

    public function getTitle(): string
    {
        return '';
    }

    public function getHeading(): string
    {
        return '';
    }

    public function getColumns(): int | string | array
    {
        return 1;
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }

    public function getAnimalBreedWidgets(): array
    {
        return [
            AnimalBreedCardsWidget::class,
        ];
    }

    public function getAnimalStatWidgets(): array
    {
        return [
            AnimalLifecycleStatsWidget::class,
        ];
    }

    public function getAnimalChartWidgets(): array
    {
        return [
            AnimalBreedPieChartWidget::class,
            AnimalStatusChartWidget::class,
        ];
    }

    public function getAnimalTableWidgets(): array
    {
        return [
            CurrentAnimalsTableWidget::class,
        ];
    }

    public function getHrWidgets(): array
    {
        return [
            AttendanceTodayStats::class,
            PendingApprovalsStats::class,
            StaffOverviewStats::class,
            HrAdvancedStatsWidget::class,
        ];
    }
}
