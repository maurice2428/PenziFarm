<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\AccountingAccount;
use App\Services\Accounting\AccountingReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AccountingReportPrintController extends Controller
{
    public function trialBalancePrint(Request $request)
    {
        return $this->trialBalance($request, false);
    }

    public function trialBalancePdf(Request $request)
    {
        return $this->trialBalance($request, true);
    }

    public function generalLedgerPrint(Request $request)
    {
        return $this->generalLedger($request, false);
    }

    public function generalLedgerPdf(Request $request)
    {
        return $this->generalLedger($request, true);
    }

    public function profitAndLossPrint(Request $request)
    {
        return $this->profitAndLoss($request, false);
    }

    public function profitAndLossPdf(Request $request)
    {
        return $this->profitAndLoss($request, true);
    }

    public function balanceSheetPrint(Request $request)
    {
        return $this->balanceSheet($request, false);
    }

    public function balanceSheetPdf(Request $request)
    {
        return $this->balanceSheet($request, true);
    }

    public function cashFlowPrint(Request $request)
    {
        return $this->cashFlow($request, false);
    }

    public function cashFlowPdf(Request $request)
    {
        return $this->cashFlow($request, true);
    }

    protected function trialBalance(Request $request, bool $pdf)
    {
        $from = $this->optionalDate($request->query('from'));
        $to = $this->dateValue(
            $request->query('to'),
            now('Africa/Nairobi')->toDateString()
        );
        $search = trim((string) $request->query('search', ''));

        $rows = app(AccountingReportService::class)
            ->trialBalance($from, $to);

        $rows = $this->filterRows($rows, $search);

        $totalDebits = round($rows->sum('debit_balance'), 2);
        $totalCredits = round($rows->sum('credit_balance'), 2);
        $difference = round($totalDebits - $totalCredits, 2);

        return $this->render('trial-balance', $pdf, [
            'reportTitle' => 'Trial Balance',
            'reportSubtitle' =>
                'Debit-versus-credit control report for posted accounting movements.',
            'periodLabel' => ($from
                ? Carbon::parse($from)->format('d M Y')
                : 'Opening')
                . ' to '
                . Carbon::parse($to)->format('d M Y'),
            'decisionNote' => abs($difference) < 0.01
                ? 'The books are balanced for the selected reporting scope.'
                : 'The report has a non-zero difference. Review mappings, opening balances and partially posted journals.',
            'controlNote' =>
                'Use this report as the first control checkpoint before period closure, management reporting and audit review.',
            'reportCode' => 'ACC-TB',
            'paperOrientation' => 'portrait',
            'reportStatus' => abs($difference) < 0.01
                ? 'Balanced'
                : 'Review Required',
            'reportStatusTone' => abs($difference) < 0.01
                ? 'success'
                : 'danger',
            'summary' => [
                [
                    'label' => 'Total Debits',
                    'value' => $totalDebits,
                    'format' => 'money',
                ],
                [
                    'label' => 'Total Credits',
                    'value' => $totalCredits,
                    'format' => 'money',
                ],
                [
                    'label' => 'Difference',
                    'value' => $difference,
                    'format' => 'money',
                    'tone' => abs($difference) < 0.01
                        ? 'success'
                        : 'danger',
                ],
                [
                    'label' => 'Accounts',
                    'value' => $rows->count(),
                    'format' => 'number',
                ],
            ],
            'columns' => [
                'Code', 'Account', 'Class',
                'Debit', 'Credit', 'Net',
            ],
            'columnWidths' => [8, 37, 11, 14, 14, 16],
            'rightAlignedColumns' => [3, 4, 5],
            'centerAlignedColumns' => [],
            'nowrapColumns' => [0, 3, 4, 5],
            'tableFontSize' => '7.8px',
            'tableHeaderFontSize' => '6.8px',
            'rows' => $rows->map(fn (array $row): array => [
                $row['code'],
                $row['name'],
                str($row['type'])
                    ->replace('_', ' ')
                    ->headline()
                    ->toString(),
                number_format((float) $row['debit_balance'], 2),
                number_format((float) $row['credit_balance'], 2),
                number_format((float) $row['balance'], 2),
            ])->values(),
            'totals' => [
                'Totals', '', '',
                number_format($totalDebits, 2),
                number_format($totalCredits, 2),
                number_format($difference, 2),
            ],
        ]);
    }

    protected function generalLedger(Request $request, bool $pdf)
    {
        $accountId = (int) $request->query('account_id');

        $account = $accountId
            ? AccountingAccount::find($accountId)
            : AccountingAccount::query()
                ->active()
                ->orderBy('code')
                ->first();

        abort_if(! $account, 404, 'No accounting account found.');

        $from = $this->dateValue(
            $request->query('from'),
            now('Africa/Nairobi')->startOfYear()->toDateString()
        );
        $to = $this->dateValue(
            $request->query('to'),
            now('Africa/Nairobi')->toDateString()
        );
        $search = trim((string) $request->query('search', ''));

        $rows = app(AccountingReportService::class)
            ->generalLedger($account->id, $from, $to);

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $rows = $rows->filter(
                fn (array $row): bool => str_contains(
                    mb_strtolower(
                        implode(' ', array_filter($row))
                    ),
                    $needle
                )
            )->values();
        }

        $closing = round((float) ($rows->last()['balance'] ?? 0), 2);

        return $this->render('general-ledger', $pdf, [
            'reportTitle' => 'General Ledger',
            'reportSubtitle' => $account->code . ' - ' . $account->name,
            'periodLabel' => Carbon::parse($from)->format('d M Y')
                . ' to '
                . Carbon::parse($to)->format('d M Y'),
            'decisionNote' =>
                'This ledger explains every movement behind the selected account balance.',
            'controlNote' =>
                'Review narration, references, project funds and cost centres before approving management reports.',
            'reportCode' => 'ACC-GL',
            'paperOrientation' => 'landscape',
            'reportStatus' => 'Posted Ledger',
            'reportStatusTone' => 'success',
            'summary' => [
                [
                    'label' => 'Total Debits',
                    'value' => $rows->sum('debit'),
                    'format' => 'money',
                ],
                [
                    'label' => 'Total Credits',
                    'value' => $rows->sum('credit'),
                    'format' => 'money',
                ],
                [
                    'label' => 'Closing Balance',
                    'value' => $closing,
                    'format' => 'money',
                ],
                [
                    'label' => 'Movements',
                    'value' => $rows->count(),
                    'format' => 'number',
                ],
            ],
            'columns' => [
                'Date', 'Journal', 'Reference', 'Description',
                'Project / Cost Ctr', 'Debit', 'Credit', 'Balance',
            ],
            'columnWidths' => [8, 10, 9, 23, 18, 10, 10, 12],
            'rightAlignedColumns' => [5, 6, 7],
            'centerAlignedColumns' => [],
            'nowrapColumns' => [0, 1, 2, 5, 6, 7],
            'tableFontSize' => '7.05px',
            'tableHeaderFontSize' => '6.35px',
            'rows' => $rows->map(fn (array $row): array => [
                $row['date'],
                $row['journal_number'],
                $row['reference'] ?: '-',
                $row['description'] ?: '-',
                trim(
                    ($row['project'] ?: '-')
                    . ' / '
                    . ($row['cost_center'] ?: '-')
                ),
                number_format((float) $row['debit'], 2),
                number_format((float) $row['credit'], 2),
                number_format((float) $row['balance'], 2),
            ])->values(),
            'totals' => [
                'Totals', '', '', '', '',
                number_format((float) $rows->sum('debit'), 2),
                number_format((float) $rows->sum('credit'), 2),
                number_format($closing, 2),
            ],
        ]);
    }

    protected function profitAndLoss(Request $request, bool $pdf)
    {
        $from = $this->dateValue(
            $request->query('from'),
            now('Africa/Nairobi')->startOfYear()->toDateString()
        );
        $to = $this->dateValue(
            $request->query('to'),
            now('Africa/Nairobi')->toDateString()
        );
        $search = trim((string) $request->query('search', ''));

        $report = app(AccountingReportService::class)
            ->profitAndLoss($from, $to);

        $rows = $this->filterRows(
            collect($report['lines'] ?? []),
            $search
        );

        $netProfit = (float) ($report['net_profit'] ?? 0);

        return $this->render('profit-and-loss', $pdf, [
            'reportTitle' => 'Profit & Loss Statement',
            'reportSubtitle' =>
                'Income, farm costs, operating expenses and net result.',
            'periodLabel' => Carbon::parse($from)->format('d M Y')
                . ' to '
                . Carbon::parse($to)->format('d M Y'),
            'decisionNote' => $netProfit >= 0
                ? 'The farm is reporting a positive net result in the selected period.'
                : 'The farm is reporting a loss or no posted income in the selected period.',
            'controlNote' =>
                'Use this report for pricing, expense control, project funding and monthly director reviews.',
            'reportCode' => 'ACC-PL',
            'paperOrientation' => 'portrait',
            'reportStatus' => $netProfit >= 0
                ? 'Profit'
                : 'Loss',
            'reportStatusTone' => $netProfit >= 0
                ? 'success'
                : 'danger',
            'summary' => [
                [
                    'label' => 'Income',
                    'value' => $report['income'] ?? 0,
                    'format' => 'money',
                ],
                [
                    'label' => 'Cost of Sales',
                    'value' => $report['cost_of_sales'] ?? 0,
                    'format' => 'money',
                ],
                [
                    'label' => 'Gross Profit',
                    'value' => $report['gross_profit'] ?? 0,
                    'format' => 'money',
                ],
                [
                    'label' => 'Net Profit',
                    'value' => $netProfit,
                    'format' => 'money',
                    'tone' => $netProfit >= 0
                        ? 'success'
                        : 'danger',
                ],
            ],
            'columns' => [
                'Code', 'Account', 'Class',
                'Debit', 'Credit', 'Amount',
            ],
            'columnWidths' => [8, 37, 11, 14, 14, 16],
            'rightAlignedColumns' => [3, 4, 5],
            'centerAlignedColumns' => [],
            'nowrapColumns' => [0, 3, 4, 5],
            'tableFontSize' => '7.8px',
            'tableHeaderFontSize' => '6.8px',
            'rows' => $rows->map(fn (array $row): array => [
                $row['code'],
                $row['name'],
                str($row['type'])
                    ->replace('_', ' ')
                    ->headline()
                    ->toString(),
                number_format((float) $row['debits'], 2),
                number_format((float) $row['credits'], 2),
                number_format((float) $row['balance'], 2),
            ])->values(),
            'totals' => [
                'Net Profit', '', '', '', '',
                number_format($netProfit, 2),
            ],
        ]);
    }

    protected function balanceSheet(Request $request, bool $pdf)
    {
        $asAt = $this->dateValue(
            $request->query('as_at'),
            now('Africa/Nairobi')->toDateString()
        );
        $search = trim((string) $request->query('search', ''));

        $report = app(AccountingReportService::class)
            ->balanceSheet($asAt);

        $rows = $this->filterRows(
            collect($report['lines'] ?? []),
            $search
        );

        $difference = (float) ($report['difference'] ?? 0);

        return $this->render('balance-sheet', $pdf, [
            'reportTitle' => 'Balance Sheet',
            'reportSubtitle' =>
                'Financial position as at '
                . Carbon::parse($asAt)->format('d M Y')
                . '.',
            'periodLabel' =>
                'As at ' . Carbon::parse($asAt)->format('d M Y'),
            'decisionNote' => abs($difference) < 0.01
                ? 'The position statement balances and is ready for management review.'
                : 'The position statement does not balance. Review retained earnings, opening balances and mappings.',
            'controlNote' =>
                'Use this report to review solvency, director funding, asset value, working capital and obligations.',
            'reportCode' => 'ACC-BS',
            'paperOrientation' => 'portrait',
            'reportStatus' => abs($difference) < 0.01
                ? 'Balanced'
                : 'Review Required',
            'reportStatusTone' => abs($difference) < 0.01
                ? 'success'
                : 'danger',
            'summary' => [
                [
                    'label' => 'Assets',
                    'value' => $report['assets'] ?? 0,
                    'format' => 'money',
                ],
                [
                    'label' => 'Liabilities',
                    'value' => $report['liabilities'] ?? 0,
                    'format' => 'money',
                ],
                [
                    'label' => 'Equity + Profit',
                    'value' => ($report['equity'] ?? 0)
                        + ($report['current_year_profit'] ?? 0),
                    'format' => 'money',
                ],
                [
                    'label' => 'Difference',
                    'value' => $difference,
                    'format' => 'money',
                    'tone' => abs($difference) < 0.01
                        ? 'success'
                        : 'danger',
                ],
            ],
            'columns' => [
                'Code', 'Account', 'Class', 'Normal',
                'Debit', 'Credit', 'Balance',
            ],
            'columnWidths' => [8, 33, 10, 9, 13, 13, 14],
            'rightAlignedColumns' => [4, 5, 6],
            'centerAlignedColumns' => [3],
            'nowrapColumns' => [0, 3, 4, 5, 6],
            'tableFontSize' => '7.45px',
            'tableHeaderFontSize' => '6.8px',
            'rows' => $rows->map(fn (array $row): array => [
                $row['code'],
                $row['name'],
                str($row['type'])
                    ->replace('_', ' ')
                    ->headline()
                    ->toString(),
                ucfirst((string) $row['normal_balance']),
                number_format((float) $row['debits'], 2),
                number_format((float) $row['credits'], 2),
                number_format((float) $row['balance'], 2),
            ])->values(),
            'totals' => [
                'Difference', '', '', '', '', '',
                number_format($difference, 2),
            ],
        ]);
    }

    protected function cashFlow(Request $request, bool $pdf)
    {
        $from = $this->dateValue(
            $request->query('from'),
            now('Africa/Nairobi')->startOfYear()->toDateString()
        );
        $to = $this->dateValue(
            $request->query('to'),
            now('Africa/Nairobi')->toDateString()
        );
        $search = trim((string) $request->query('search', ''));

        $report = app(AccountingReportService::class)
            ->cashFlow($from, $to);

        $rows = collect($report['lines'] ?? []);

        if ($search !== '') {
            $needle = mb_strtolower($search);

            $rows = $rows->filter(function ($line) use ($needle): bool {
                return str_contains(
                    mb_strtolower(implode(' ', array_filter([
                        $line->journalEntry?->journal_number,
                        $line->journalEntry?->reference,
                        $line->account?->code,
                        $line->account?->name,
                        $line->description,
                        $line->journalEntry?->narration,
                    ]))),
                    $needle
                );
            })->values();
        }

        return $this->render('cash-flow', $pdf, [
            'reportTitle' => 'Cash Flow Statement',
            'reportSubtitle' =>
                'Cash, bank and mobile-money inflows and outflows from posted journals.',
            'periodLabel' => Carbon::parse($from)->format('d M Y')
                . ' to '
                . Carbon::parse($to)->format('d M Y'),
            'decisionNote' => ($report['net_cash_flow'] ?? 0) >= 0
                ? 'Cash inflows exceeded outflows for the selected period.'
                : 'Cash outflows exceeded inflows. Review liquidity requirements and payment timing.',
            'controlNote' =>
                'This report follows movements in configured cash-equivalent ledger accounts. Reconcile it to bank, M-Pesa and cash statements.',
            'reportCode' => 'ACC-CF',
            'paperOrientation' => 'landscape',
            'reportStatus' => ($report['net_cash_flow'] ?? 0) >= 0
                ? 'Net Inflow'
                : 'Net Outflow',
            'reportStatusTone' => ($report['net_cash_flow'] ?? 0) >= 0
                ? 'success'
                : 'danger',
            'summary' => [
                [
                    'label' => 'Cash Inflows',
                    'value' => $report['inflows'] ?? 0,
                    'format' => 'money',
                ],
                [
                    'label' => 'Cash Outflows',
                    'value' => $report['outflows'] ?? 0,
                    'format' => 'money',
                ],
                [
                    'label' => 'Net Cash Flow',
                    'value' => $report['net_cash_flow'] ?? 0,
                    'format' => 'money',
                    'tone' => ($report['net_cash_flow'] ?? 0) >= 0
                        ? 'success'
                        : 'danger',
                ],
                [
                    'label' => 'Movements',
                    'value' => $report['movements'] ?? 0,
                    'format' => 'number',
                ],
            ],
            'columns' => [
                'Date', 'Journal', 'Reference', 'Cash Account',
                'Description', 'Inflow', 'Outflow',
            ],
            'columnWidths' => [8, 10, 9, 24, 25, 12, 12],
            'rightAlignedColumns' => [5, 6],
            'centerAlignedColumns' => [],
            'nowrapColumns' => [0, 1, 2, 5, 6],
            'tableFontSize' => '7.0px',
            'tableHeaderFontSize' => '6.3px',
            'rows' => $rows->map(fn ($line): array => [
                $line->journalEntry?->transaction_date?->format('d M Y') ?: '-',
                $line->journalEntry?->journal_number ?: '-',
                $line->journalEntry?->reference ?: '-',
                trim(
                    ($line->account?->code ?: '-')
                    . ' - '
                    . ($line->account?->name ?: '-')
                ),
                $line->description
                    ?: $line->journalEntry?->narration
                    ?: '-',
                number_format((float) $line->debit, 2),
                number_format((float) $line->credit, 2),
            ])->values(),
            'totals' => [
                'Totals', '', '', '', '',
                number_format((float) ($report['inflows'] ?? 0), 2),
                number_format((float) ($report['outflows'] ?? 0), 2),
            ],
        ]);
    }

    protected function filterRows(
        Collection $rows,
        ?string $search
    ): Collection {
        if (blank($search)) {
            return $rows->values();
        }

        $needle = mb_strtolower(trim((string) $search));

        return $rows->filter(fn (array $row): bool => str_contains(
            mb_strtolower(implode(' ', array_filter([
                $row['code'] ?? '',
                $row['name'] ?? '',
                $row['type'] ?? '',
                $row['normal_balance'] ?? '',
            ]))),
            $needle
        ))->values();
    }

    protected function render(
        string $slug,
        bool $pdf,
        array $data
    ) {
        $user = auth()->user();
        $generatedAt = now('Africa/Nairobi');

        $reportCode = $data['reportCode']
            ?? 'ACC-REPORT';

        $reportReference = sprintf(
            '%s-%s',
            $reportCode,
            $generatedAt->format('Ymd-His')
        );

        $generatedByRole = $user
            && method_exists(
                $user,
                'getRoleNames'
            )
                ? (
                    $user->getRoleNames()->first()
                    ?: 'User'
                )
                : 'User';

        $recordCount = collect(
            $data['rows'] ?? []
        )->count();

        $verificationText = implode(' | ', [
            setting(
                'farm.name',
                config(
                    'app.name',
                    'Farm Management System'
                )
            ),
            $data['reportTitle']
                ?? 'Accounting Report',
            'Period: '
                . (
                    $data['periodLabel']
                    ?? '-'
                ),
            'Reference: '
                . $reportReference,
            'Records: '
                . $recordCount,
            'Prepared By: '
                . ($user?->name ?? 'System'),
            'Role: '
                . $generatedByRole,
            'Generated: '
                . $generatedAt
                    ->format('Y-m-d H:i:s')
                . ' EAT',
        ]);

        $data = array_merge(
            $data,
            [
                'generatedBy' => $user,
                'generatedByRole' =>
                    $generatedByRole,

                'generatedAt' =>
                    $generatedAt,

                'recordCount' =>
                    $recordCount,

                'reportSlug' => $slug,

                'reportReference' =>
                    $reportReference,

                'verificationText' =>
                    $verificationText,

                'isPdf' => $pdf,

                'paperOrientation' =>
                    $data[
                        'paperOrientation'
                    ] ?? 'portrait',

                'rightAlignedColumns' =>
                    $data[
                        'rightAlignedColumns'
                    ] ?? [],

                'centerAlignedColumns' =>
                    $data[
                        'centerAlignedColumns'
                    ] ?? [],

                'nowrapColumns' =>
                    $data[
                        'nowrapColumns'
                    ] ?? [],
            ]
        );

        if (! $pdf) {
            return view(
                'reports.accounting.accounting-print',
                $data
            );
        }

        return Pdf::loadView(
            'reports.accounting.accounting-print',
            $data
        )
            ->setPaper(
                'a4',
                $data['paperOrientation']
            )
            ->setOptions([
                'defaultFont' => 'Courier',
                'dpi' => 120,
                'fontHeightRatio' => 1.0,
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => true,
                'defaultMediaType' => 'print',
            ])
            ->stream(
                $slug
                . '-'
                . $generatedAt
                    ->format('Ymd-His')
                . '.pdf'
            );
    }

    private function optionalDate(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return $this->dateValue($value, null);
    }

    private function dateValue(
        mixed $value,
        ?string $fallback
    ): ?string {
        $value = rtrim(trim((string) $value), ". \t\n\r\0\x0B");

        if ($value === '') {
            return $fallback;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return $fallback;
        }
    }
}
