<?php

namespace App\Filament\Pages\Accounting;

use App\Filament\Concerns\UsesPermissionPageAccess;

use App\Filament\Concerns\HidesDefaultFilamentPageHeader;

use App\Services\Accounting\AccountingReportService;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class BalanceSheet extends Page
{
    use UsesPermissionPageAccess;

    protected static ?string $pagePermission = 'view balance sheet';

    use HidesDefaultFilamentPageHeader;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';
    protected static ?string $navigationGroup = 'Accounting Reports';
    protected static ?string $navigationLabel = 'Balance Sheet';
    protected static ?int $navigationSort = 4;
    protected static string $view = 'filament.pages.accounting.balance-sheet';

    public ?string $asAt = null;
    public ?string $search = null;
    public int $perPage = 25;
    public int $page = 1;


    public function getSubheading(): ?string
    {
        return 'Financial position report showing assets, liabilities, equity and balance integrity at a selected date.';
    }

    public function mount(): void
    {
        $this->asAt = now()->toDateString();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('Print View')
                ->icon('heroicon-o-printer')
                ->color('success')
                ->url(fn () => Route::has('accounting.reports.balance-sheet.print') ? route('accounting.reports.balance-sheet.print', $this->queryParams()) : '#')
                ->openUrlInNewTab()
                ->visible(fn () => Route::has('accounting.reports.balance-sheet.print')),
            Action::make('pdf')
                ->label('PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(fn () => Route::has('accounting.reports.balance-sheet.pdf') ? route('accounting.reports.balance-sheet.pdf', $this->queryParams()) : '#')
                ->openUrlInNewTab()
                ->visible(fn () => Route::has('accounting.reports.balance-sheet.pdf')),
        ];
    }

    public function setPerPage(int $perPage): void
    {
        $this->perPage = $perPage;
        $this->page = 1;
    }

    public function getReportProperty(): array
    {
        return app(AccountingReportService::class)->balanceSheet($this->asAt);
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
        if (in_array($property, ['asAt', 'search'], true)) {
            $this->page = 1;
        }
    }

    protected function queryParams(): array
    {
        return array_filter([
            'as_at' => $this->asAt,
            'search' => $this->search,
        ]);
    }
}
