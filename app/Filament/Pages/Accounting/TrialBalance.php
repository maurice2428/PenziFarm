<?php

namespace App\Filament\Pages\Accounting;

use App\Filament\Concerns\UsesPermissionPageAccess;

use App\Filament\Concerns\HidesDefaultFilamentPageHeader;

use App\Services\Accounting\AccountingReportService;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class TrialBalance extends Page
{
    use UsesPermissionPageAccess;

    protected static ?string $pagePermission = 'view trial balance';

    use HidesDefaultFilamentPageHeader;

    protected static ?string $navigationIcon = 'heroicon-o-scale';
    protected static ?string $navigationGroup = 'Accounting Reports';
    protected static ?string $navigationLabel = 'Trial Balance';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.accounting.trial-balance';

    public ?string $from = null;
    public ?string $to = null;
    public ?string $search = null;
    public int $perPage = 25;
    public int $page = 1;


    public function getSubheading(): ?string
    {
        return 'Director-level control report for confirming debit and credit equality before closing reports.';
    }

    public function mount(): void
    {
        $this->from = null;
        $this->to = now()->toDateString();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('Print View')
                ->icon('heroicon-o-printer')
                ->color('success')
                ->url(fn () => Route::has('accounting.reports.trial-balance.print') ? route('accounting.reports.trial-balance.print', $this->queryParams()) : '#')
                ->openUrlInNewTab()
                ->visible(fn () => Route::has('accounting.reports.trial-balance.print')),
            Action::make('pdf')
                ->label('PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(fn () => Route::has('accounting.reports.trial-balance.pdf') ? route('accounting.reports.trial-balance.pdf', $this->queryParams()) : '#')
                ->openUrlInNewTab()
                ->visible(fn () => Route::has('accounting.reports.trial-balance.pdf')),
        ];
    }

    public function setPerPage(int $perPage): void
    {
        $this->perPage = $perPage;
        $this->page = 1;
    }

    public function getRowsProperty(): Collection
    {
        $rows = app(AccountingReportService::class)->trialBalance($this->from, $this->to);

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

    public function getTotalDebitsProperty(): float
    {
        return round($this->rows->sum('debit_balance'), 2);
    }

    public function getTotalCreditsProperty(): float
    {
        return round($this->rows->sum('credit_balance'), 2);
    }

    public function getDifferenceProperty(): float
    {
        return round($this->totalDebits - $this->totalCredits, 2);
    }

    public function getAccountTypeSummaryProperty(): Collection
    {
        return $this->rows
            ->groupBy('type')
            ->map(fn (Collection $items, string $type) => [
                'type' => $type,
                'label' => str($type)->replace('_', ' ')->headline()->toString(),
                'debits' => round($items->sum('debit_balance'), 2),
                'credits' => round($items->sum('credit_balance'), 2),
                'balance' => round($items->sum('balance'), 2),
            ])
            ->values();
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
