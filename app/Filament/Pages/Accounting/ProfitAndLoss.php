<?php

namespace App\Filament\Pages\Accounting;

use App\Filament\Concerns\UsesPermissionPageAccess;

use App\Filament\Concerns\HidesDefaultFilamentPageHeader;

use App\Services\Accounting\AccountingReportService;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class ProfitAndLoss extends Page
{
    use UsesPermissionPageAccess;

    protected static ?string $pagePermission = 'view profit and loss';

    use HidesDefaultFilamentPageHeader;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationGroup = 'Accounting Reports';
    protected static ?string $navigationLabel = 'Profit & Loss';
    protected static ?int $navigationSort = 3;
    protected static string $view = 'filament.pages.accounting.profit-and-loss';

    public ?string $from = null;
    public ?string $to = null;
    public ?string $search = null;
    public int $perPage = 25;
    public int $page = 1;


    public function getSubheading(): ?string
    {
        return 'Executive performance statement for farm income, production costs, operating expenses and net profit.';
    }

    public function mount(): void
    {
        $this->from = now()->startOfYear()->toDateString();
        $this->to = now()->toDateString();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('Print View')
                ->icon('heroicon-o-printer')
                ->color('success')
                ->url(fn () => Route::has('accounting.reports.profit-and-loss.print') ? route('accounting.reports.profit-and-loss.print', $this->queryParams()) : '#')
                ->openUrlInNewTab()
                ->visible(fn () => Route::has('accounting.reports.profit-and-loss.print')),
            Action::make('pdf')
                ->label('PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(fn () => Route::has('accounting.reports.profit-and-loss.pdf') ? route('accounting.reports.profit-and-loss.pdf', $this->queryParams()) : '#')
                ->openUrlInNewTab()
                ->visible(fn () => Route::has('accounting.reports.profit-and-loss.pdf')),
        ];
    }

    public function setPerPage(int $perPage): void
    {
        $this->perPage = $perPage;
        $this->page = 1;
    }

    public function getReportProperty(): array
    {
        return app(AccountingReportService::class)->profitAndLoss($this->from, $this->to);
    }

    public function getRowsProperty(): Collection
    {
        $rows = collect($this->report['lines'] ?? []);

        if ($this->search) {
            $needle = mb_strtolower($this->search);
            $rows = $rows->filter(fn (array $row) => str_contains(mb_strtolower(($row['code'] ?? '') . ' ' . ($row['name'] ?? '') . ' ' . ($row['type'] ?? '')), $needle));
        }

        return $rows->values();
    }

    public function getPagedRowsProperty(): Collection
    {
        return $this->rows->slice(($this->page - 1) * $this->perPage, $this->perPage)->values();
    }

    public function getTotalPagesProperty(): int
    {
        return max(1, (int) ceil($this->rows->count() / $this->perPage));
    }

    public function getMarginProperty(): float
    {
        $income = (float) ($this->report['income'] ?? 0);
        return $income > 0 ? round(((float) ($this->report['net_profit'] ?? 0) / $income) * 100, 2) : 0.0;
    }

    public function previousPage(): void
    {
        $this->page = max(1, $this->page - 1);
    }

    public function nextPage(): void
    {
        if ($this->page < $this->totalPages) {
            $this->page++;
        }
    }

    public function updated($property): void
    {
        if (in_array($property, ['from', 'to', 'search'], true)) {
            $this->page = 1;
        }
    }

    protected function queryParams(): array
    {
        return array_filter([
            'from' => $this->from,
            'to' => $this->to,
            'search' => $this->search,
        ]);
    }
}
