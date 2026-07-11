<?php

namespace App\Filament\Pages\Accounting;

use App\Filament\Concerns\HidesDefaultFilamentPageHeader;
use App\Filament\Concerns\UsesPermissionPageAccess;
use App\Services\Accounting\AccountingReportService;
use Filament\Pages\Page;

class CashFlowStatement extends Page
{
    use UsesPermissionPageAccess;
    use HidesDefaultFilamentPageHeader;

    protected static ?string $pagePermission =
        'view cash flow statement';

    protected static ?string $navigationIcon =
        'heroicon-o-arrows-up-down';

    protected static ?string $navigationGroup =
        'Accounting Reports';

    protected static ?string $navigationLabel =
        'Cash Flow Statement';

    protected static ?int $navigationSort = 5;

    protected static string $view =
        'filament.pages.accounting.cash-flow-statement';

    public ?string $from = null;
    public ?string $to = null;
    public ?string $search = null;

    public function mount(): void
    {
        $this->from = now('Africa/Nairobi')
            ->startOfYear()
            ->toDateString();

        $this->to = now('Africa/Nairobi')
            ->toDateString();
    }

    protected function getHeaderActions(): array
    {
        /*
         * Print and PDF controls are already presented cleanly inside
         * the report hero. Returning no Filament header actions removes
         * the duplicated controls above the banner.
         */
        return [];
    }

    public function getReportProperty(): array
    {
        $report = app(
            AccountingReportService::class
        )->cashFlow(
            $this->from,
            $this->to
        );

        if (blank($this->search)) {
            return $report;
        }

        $needle = mb_strtolower(
            trim($this->search)
        );

        $report['lines'] = collect(
            $report['lines'] ?? []
        )
            ->filter(
                function ($line) use (
                    $needle
                ): bool {
                    return str_contains(
                        mb_strtolower(
                            implode(
                                ' ',
                                array_filter([
                                    $line
                                        ->journalEntry
                                        ?->journal_number,
                                    $line
                                        ->journalEntry
                                        ?->reference,
                                    $line
                                        ->account
                                        ?->code,
                                    $line
                                        ->account
                                        ?->name,
                                    $line
                                        ->description,
                                    $line
                                        ->journalEntry
                                        ?->narration,
                                ])
                            )
                        ),
                        $needle
                    );
                }
            )
            ->values();

        return $report;
    }
}
