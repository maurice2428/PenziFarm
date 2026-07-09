<?php

namespace App\Filament\Pages\Accounting;

use App\Filament\Concerns\UsesPermissionPageAccess;

use App\Filament\Concerns\HidesDefaultFilamentPageHeader;

use App\Models\Accounting\AccountingAccount;
use App\Services\Accounting\AccountingReportService;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class GeneralLedger extends Page
{
    use UsesPermissionPageAccess;

    protected static ?string $pagePermission = 'view general ledger';

    use HidesDefaultFilamentPageHeader;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationGroup = 'Accounting Reports';
    protected static ?string $navigationLabel = 'General Ledger';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.pages.accounting.general-ledger';

    public ?int $accountId = null;
    public ?string $accountSearch = null;
    public ?string $from = null;
    public ?string $to = null;
    public ?string $search = null;
    public int $perPage = 25;
    public int $page = 1;


    public function getSubheading(): ?string
    {
        return 'Trace every posted movement for a selected account, including references, projects and running balance.';
    }

    public function mount(): void
    {
        $this->to = now()->toDateString();
        $this->from = now()->startOfYear()->toDateString();
        $this->accountId = AccountingAccount::query()->active()->orderBy('code')->value('id');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('Print View')
                ->icon('heroicon-o-printer')
                ->color('success')
                ->url(fn () => Route::has('accounting.reports.general-ledger.print') ? route('accounting.reports.general-ledger.print', $this->queryParams()) : '#')
                ->openUrlInNewTab()
                ->visible(fn () => Route::has('accounting.reports.general-ledger.print') && filled($this->accountId)),
            Action::make('pdf')
                ->label('PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(fn () => Route::has('accounting.reports.general-ledger.pdf') ? route('accounting.reports.general-ledger.pdf', $this->queryParams()) : '#')
                ->openUrlInNewTab()
                ->visible(fn () => Route::has('accounting.reports.general-ledger.pdf') && filled($this->accountId)),
        ];
    }

    public function selectAccount(int $accountId): void
    {
        $this->accountId = $accountId;
        $this->page = 1;
    }

    public function setPerPage(int $perPage): void
    {
        $this->perPage = $perPage;
        $this->page = 1;
    }

    public function getAccountsProperty(): Collection
    {
        $query = AccountingAccount::query()->active()->orderBy('code');

        if ($this->accountSearch) {
            $needle = '%' . $this->accountSearch . '%';
            $query->where(fn ($q) => $q->where('code', 'like', $needle)->orWhere('name', 'like', $needle));
        }

        return $query->limit(140)->get(['id', 'code', 'name', 'type']);
    }

    public function getSelectedAccountProperty(): ?AccountingAccount
    {
        return $this->accountId ? AccountingAccount::find($this->accountId) : null;
    }

    public function getRowsProperty(): Collection
    {
        if (! $this->accountId) {
            return collect();
        }

        $rows = app(AccountingReportService::class)->generalLedger($this->accountId, $this->from, $this->to);

        if ($this->search) {
            $needle = mb_strtolower($this->search);
            $rows = $rows->filter(fn (array $row) => str_contains(mb_strtolower(implode(' ', array_filter([
                $row['journal_number'] ?? '',
                $row['reference'] ?? '',
                $row['description'] ?? '',
                $row['cost_center'] ?? '',
                $row['project'] ?? '',
            ]))), $needle));
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
        return round($this->rows->sum('debit'), 2);
    }

    public function getTotalCreditsProperty(): float
    {
        return round($this->rows->sum('credit'), 2);
    }

    public function getClosingBalanceProperty(): float
    {
        return round((float) ($this->rows->last()['balance'] ?? 0), 2);
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
        if (in_array($property, ['accountId', 'from', 'to', 'search', 'accountSearch'], true)) {
            $this->page = 1;
        }
    }

    protected function queryParams(): array
    {
        return array_filter([
            'account_id' => $this->accountId,
            'from' => $this->from,
            'to' => $this->to,
            'search' => $this->search,
        ]);
    }
}
