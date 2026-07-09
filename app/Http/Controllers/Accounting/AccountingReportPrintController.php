<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\AccountingAccount;
use App\Services\Accounting\AccountingReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AccountingReportPrintController extends Controller
{
    public function trialBalancePrint(Request $request) { return $this->trialBalance($request, false); }
    public function trialBalancePdf(Request $request) { return $this->trialBalance($request, true); }
    public function generalLedgerPrint(Request $request) { return $this->generalLedger($request, false); }
    public function generalLedgerPdf(Request $request) { return $this->generalLedger($request, true); }
    public function profitAndLossPrint(Request $request) { return $this->profitAndLoss($request, false); }
    public function profitAndLossPdf(Request $request) { return $this->profitAndLoss($request, true); }
    public function balanceSheetPrint(Request $request) { return $this->balanceSheet($request, false); }
    public function balanceSheetPdf(Request $request) { return $this->balanceSheet($request, true); }

    protected function trialBalance(Request $request, bool $pdf)
    {
        $from = $request->query('from');
        $to = $request->query('to', now()->toDateString());
        $search = $request->query('search');

        $rows = app(AccountingReportService::class)->trialBalance($from, $to);
        $rows = $this->filterRows($rows, $search);
        $totalDebits = round($rows->sum('debit_balance'), 2);
        $totalCredits = round($rows->sum('credit_balance'), 2);
        $difference = round($totalDebits - $totalCredits, 2);

        return $this->render('trial-balance', $pdf, [
            'reportTitle' => 'Trial Balance',
            'reportSubtitle' => 'Debit-versus-credit control report for posted accounting movements.',
            'periodLabel' => ($from ? date('d M Y', strtotime($from)) : 'Opening') . ' to ' . date('d M Y', strtotime($to)),
            'decisionNote' => abs($difference) < 0.01
                ? 'The books are balanced for the selected reporting scope. This supports reliable Profit & Loss and Balance Sheet preparation.'
                : 'The report has a non-zero difference. Management should review journal mappings, opening balances and any partially posted transactions before issuing final reports.',
            'controlNote' => 'Use this report as the first control checkpoint before period closure, director reporting and audit review.',
            'summary' => [
                'Total Debits' => $totalDebits,
                'Total Credits' => $totalCredits,
                'Difference' => $difference,
                'Accounts' => $rows->count(),
            ],
            'columns' => ['Code', 'Account', 'Class', 'Debit', 'Credit', 'Net Balance'],
            'rows' => $rows->map(fn ($row) => [
                $row['code'], $row['name'], str($row['type'])->replace('_', ' ')->headline()->toString(),
                number_format($row['debit_balance'], 2), number_format($row['credit_balance'], 2), number_format($row['balance'], 2),
            ])->values(),
            'totals' => ['Totals', '', '', number_format($totalDebits, 2), number_format($totalCredits, 2), number_format($difference, 2)],
        ]);
    }

    protected function generalLedger(Request $request, bool $pdf)
    {
        $accountId = (int) $request->query('account_id');
        $account = $accountId ? AccountingAccount::find($accountId) : AccountingAccount::query()->active()->orderBy('code')->first();
        abort_if(! $account, 404, 'No accounting account found.');

        $from = $request->query('from', now()->startOfYear()->toDateString());
        $to = $request->query('to', now()->toDateString());
        $search = $request->query('search');

        $rows = app(AccountingReportService::class)->generalLedger($account->id, $from, $to);
        if ($search) {
            $needle = mb_strtolower($search);
            $rows = $rows->filter(fn (array $row) => str_contains(mb_strtolower(implode(' ', array_filter($row))), $needle))->values();
        }

        $closing = round((float) ($rows->last()['balance'] ?? 0), 2);

        return $this->render('general-ledger', $pdf, [
            'reportTitle' => 'General Ledger',
            'reportSubtitle' => $account->code . ' — ' . $account->name,
            'periodLabel' => date('d M Y', strtotime($from)) . ' to ' . date('d M Y', strtotime($to)),
            'decisionNote' => 'This ledger explains every movement behind the selected account balance. It is ideal for tracing cash, bank, receivable, payable, director funding and expense changes.',
            'controlNote' => 'Review narration, reference numbers, project funds and cost centres for unusual transactions before approving management reports.',
            'summary' => [
                'Total Debits' => round($rows->sum('debit'), 2),
                'Total Credits' => round($rows->sum('credit'), 2),
                'Closing Balance' => $closing,
                'Movements' => $rows->count(),
            ],
            'columns' => ['Date', 'Journal', 'Reference', 'Description', 'Project / Cost Centre', 'Debit', 'Credit', 'Balance'],
            'rows' => $rows->map(fn ($row) => [
                $row['date'], $row['journal_number'], $row['reference'] ?: '-', $row['description'] ?: '-',
                trim(($row['project'] ?: '-') . ' / ' . ($row['cost_center'] ?: '-')),
                number_format($row['debit'], 2), number_format($row['credit'], 2), number_format($row['balance'], 2),
            ])->values(),
            'totals' => ['Totals', '', '', '', '', number_format($rows->sum('debit'), 2), number_format($rows->sum('credit'), 2), number_format($closing, 2)],
        ]);
    }

    protected function profitAndLoss(Request $request, bool $pdf)
    {
        $from = $request->query('from', now()->startOfYear()->toDateString());
        $to = $request->query('to', now()->toDateString());
        $search = $request->query('search');

        $report = app(AccountingReportService::class)->profitAndLoss($from, $to);
        $rows = $this->filterRows(collect($report['lines'] ?? []), $search);
        $netProfit = (float) ($report['net_profit'] ?? 0);

        return $this->render('profit-and-loss', $pdf, [
            'reportTitle' => 'Profit & Loss Statement',
            'reportSubtitle' => 'Income, farm costs, operating expenses and net result.',
            'periodLabel' => date('d M Y', strtotime($from)) . ' to ' . date('d M Y', strtotime($to)),
            'decisionNote' => $netProfit >= 0
                ? 'The farm is reporting a positive net result in the selected period. Directors should review which income streams and cost centres contributed most to this performance.'
                : 'The farm is reporting a loss or no posted income in the selected period. Review sales postings, production costs, payroll, feed, veterinary costs and overheads.',
            'controlNote' => 'Use this report for pricing decisions, expense control, project funding requests and monthly director reviews.',
            'summary' => [
                'Income' => $report['income'] ?? 0,
                'Cost of Sales' => $report['cost_of_sales'] ?? 0,
                'Gross Profit' => $report['gross_profit'] ?? 0,
                'Net Profit' => $netProfit,
            ],
            'columns' => ['Code', 'Account', 'Class', 'Debit', 'Credit', 'Amount'],
            'rows' => $rows->map(fn ($row) => [
                $row['code'], $row['name'], str($row['type'])->replace('_', ' ')->headline()->toString(),
                number_format($row['debits'], 2), number_format($row['credits'], 2), number_format($row['balance'], 2),
            ])->values(),
            'totals' => ['Net Profit', '', '', '', '', number_format($netProfit, 2)],
        ]);
    }

    protected function balanceSheet(Request $request, bool $pdf)
    {
        $asAt = $request->query('as_at', now()->toDateString());
        $search = $request->query('search');

        $report = app(AccountingReportService::class)->balanceSheet($asAt);
        $rows = $this->filterRows(collect($report['lines'] ?? []), $search);
        $difference = (float) ($report['difference'] ?? 0);

        return $this->render('balance-sheet', $pdf, [
            'reportTitle' => 'Balance Sheet',
            'reportSubtitle' => 'Financial position as at ' . date('d M Y', strtotime($asAt)) . '.',
            'periodLabel' => 'As at ' . date('d M Y', strtotime($asAt)),
            'decisionNote' => abs($difference) < 0.01
                ? 'The position statement balances. Directors can use it to understand the farm’s assets, obligations and equity position.'
                : 'The position statement does not balance. Review retained earnings, opening balances, journals and account mappings before issuing it.',
            'controlNote' => 'Use this report to discuss solvency, director loans, capital contributions, asset value, working capital and funding exposure.',
            'summary' => [
                'Assets' => $report['assets'] ?? 0,
                'Liabilities' => $report['liabilities'] ?? 0,
                'Equity + Profit' => ($report['equity'] ?? 0) + ($report['current_year_profit'] ?? 0),
                'Difference' => $difference,
            ],
            'columns' => ['Code', 'Account', 'Class', 'Normal', 'Debit', 'Credit', 'Balance'],
            'rows' => $rows->map(fn ($row) => [
                $row['code'], $row['name'], str($row['type'])->replace('_', ' ')->headline()->toString(), ucfirst($row['normal_balance']),
                number_format($row['debits'], 2), number_format($row['credits'], 2), number_format($row['balance'], 2),
            ])->values(),
            'totals' => ['Difference', '', '', '', '', '', number_format($difference, 2)],
        ]);
    }

    protected function filterRows(Collection $rows, ?string $search): Collection
    {
        if (! $search) {
            return $rows->values();
        }

        $needle = mb_strtolower($search);
        return $rows->filter(fn (array $row) => str_contains(mb_strtolower(implode(' ', array_filter([
            $row['code'] ?? '', $row['name'] ?? '', $row['type'] ?? '', $row['normal_balance'] ?? '',
        ]))), $needle))->values();
    }

    protected function render(string $slug, bool $pdf, array $data)
    {
        $data['generatedBy'] = auth()->user();
        $data['generatedByRole'] = method_exists(auth()->user(), 'getRoleNames') ? (auth()->user()?->getRoleNames()?->first() ?: 'User') : 'User';
        $data['recordCount'] = collect($data['rows'] ?? [])->count();
        $data['reportSlug'] = $slug;
        $data['isPdf'] = $pdf;

        if (! $pdf) {
            return view('reports.accounting.accounting-print', $data);
        }

        return Pdf::loadView('reports.accounting.accounting-print', $data)
            ->setPaper('a4', 'landscape')
            ->stream($slug . '-' . now()->format('Ymd-His') . '.pdf');
    }
}
