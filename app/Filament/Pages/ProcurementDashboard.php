<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class ProcurementDashboard extends Page
{
    protected static ?string $navigationGroup = 'Procurement';

    protected static ?string $navigationLabel = 'Procurement Control';

    protected static ?string $title = 'Procurement Dashboard';

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?int $navigationSort = 0;

    protected static string $view = 'filament.pages.procurement-dashboard';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public string $activeRange = 'this_month';

    public function mount(): void
    {
        $this->setRange('this_month');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view procurement dashboard') ?? false;
    }

    public function setRange(string $range): void
    {
        $this->activeRange = $range;

        $now = now('Africa/Nairobi');

        match ($range) {
            'today' => [
                $this->dateFrom = $now->toDateString(),
                $this->dateTo = $now->toDateString(),
            ],

            'yesterday' => [
                $this->dateFrom = $now->copy()->subDay()->toDateString(),
                $this->dateTo = $now->copy()->subDay()->toDateString(),
            ],

            'this_week' => [
                $this->dateFrom = $now->copy()->startOfWeek()->toDateString(),
                $this->dateTo = $now->copy()->endOfWeek()->toDateString(),
            ],

            'this_month' => [
                $this->dateFrom = $now->copy()->startOfMonth()->toDateString(),
                $this->dateTo = $now->copy()->endOfMonth()->toDateString(),
            ],

            'last_30' => [
                $this->dateFrom = $now->copy()->subDays(30)->toDateString(),
                $this->dateTo = $now->toDateString(),
            ],

            'last_90' => [
                $this->dateFrom = $now->copy()->subDays(90)->toDateString(),
                $this->dateTo = $now->toDateString(),
            ],

            'this_year' => [
                $this->dateFrom = $now->copy()->startOfYear()->toDateString(),
                $this->dateTo = $now->copy()->endOfYear()->toDateString(),
            ],

            default => [
                $this->dateFrom = $now->copy()->startOfMonth()->toDateString(),
                $this->dateTo = $now->copy()->endOfMonth()->toDateString(),
            ],
        };
    }

    public function updatedDateFrom(): void
    {
        $this->activeRange = 'custom';
    }

    public function updatedDateTo(): void
    {
        $this->activeRange = 'custom';
    }
}
