<?php

namespace App\Filament\Pages\Accounting;

use App\Filament\Concerns\UsesPermissionPageAccess;

use App\Filament\Concerns\HidesDefaultFilamentPageHeader;

use App\Services\Accounting\AccountingInsightService;
use Filament\Pages\Page;

class AccountingDashboard extends Page
{
    use UsesPermissionPageAccess;

    protected static ?string $pagePermission = 'view accounting dashboard';

    use HidesDefaultFilamentPageHeader;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationGroup = 'Accounting Reports';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?int $navigationSort = 0;
    protected static string $view = 'filament.pages.accounting.accounting-dashboard';

    public ?string $from = null;
    public ?string $to = null;


    public function getSubheading(): ?string
    {
        return 'Executive accounting dashboard for profitability, project funds, account mix, ratios and recent journals.';
    }

    public function mount(): void
    {
        $this->from = now()->subMonths(5)->startOfMonth()->toDateString();
        $this->to = now()->toDateString();
    }

    public function getDashboardProperty(): array
    {
        return app(AccountingInsightService::class)->dashboard($this->from, $this->to);
    }
}
