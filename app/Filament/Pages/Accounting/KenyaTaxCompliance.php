<?php

namespace App\Filament\Pages\Accounting;

use App\Filament\Concerns\HidesDefaultFilamentPageHeader;
use App\Filament\Concerns\UsesPermissionPageAccess;
use App\Models\Accounting\AccountingTaxTransaction;
use App\Services\Accounting\AccountingReportService;
use Filament\Pages\Page;

class KenyaTaxCompliance extends Page
{
    use UsesPermissionPageAccess;
    use HidesDefaultFilamentPageHeader;

    protected static ?string $pagePermission = 'view kenya tax compliance';
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Kenya Tax & Compliance';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?int $navigationSort = 0;
    protected static string $view = 'filament.pages.accounting.kenya-tax-compliance';

    public ?string $from = null;
    public ?string $to = null;

    public function mount(): void
    {
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = now()->toDateString();
    }

    public function getSummaryProperty()
    {
        return app(AccountingReportService::class)->taxSummary($this->from,$this->to);
    }

    public function getOverdueProperty()
    {
        return AccountingTaxTransaction::query()->whereNotNull('due_date')->whereDate('due_date','<',now())->whereNotIn('status',['paid','filed','reversed'])->orderBy('due_date')->get();
    }
}
