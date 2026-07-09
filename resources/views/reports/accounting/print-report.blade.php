<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #111827; margin: 0; background: #f8fafc; font-size: 12px; }
        .page { max-width: 1120px; margin: 0 auto; padding: 24px; background: #fff; }
        .header { border-bottom: 3px solid #0f766e; padding-bottom: 14px; margin-bottom: 18px; display: table; width: 100%; }
        .header-left, .header-right { display: table-cell; vertical-align: top; }
        .header-right { text-align: right; }
        .company { font-size: 22px; font-weight: 900; color: #064e3b; letter-spacing: -.03em; }
        .report-title { margin-top: 6px; font-size: 18px; font-weight: 900; }
        .subtitle { color: #64748b; margin-top: 4px; }
        .badge { display: inline-block; border-radius: 999px; padding: 5px 10px; color: #065f46; background: #d1fae5; font-weight: 800; font-size: 10px; }
        .summary { display: table; width: 100%; margin: 0 0 16px; border-spacing: 8px; }
        .summary-card { display: table-cell; border: 1px solid #e5e7eb; border-radius: 12px; padding: 10px; background: #f9fafb; }
        .summary-card small { display: block; color: #64748b; text-transform: uppercase; font-weight: 800; font-size: 9px; letter-spacing: .08em; }
        .summary-card strong { display: block; margin-top: 4px; font-size: 15px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f1f5f9; color: #334155; text-transform: uppercase; font-size: 9px; letter-spacing: .08em; text-align: left; padding: 8px; border: 1px solid #e5e7eb; }
        td { padding: 7px 8px; border: 1px solid #e5e7eb; vertical-align: top; }
        tfoot td { font-weight: 900; background: #f8fafc; }
        .right { text-align: right; font-variant-numeric: tabular-nums; }
        .muted { color: #64748b; }
        .print-actions { position: sticky; top: 0; background: #ffffff; border-bottom: 1px solid #e5e7eb; padding: 10px 24px; text-align: right; }
        .btn { border: 0; background: #0f766e; color: #fff; padding: 9px 14px; border-radius: 9px; font-weight: 800; cursor: pointer; }
        .footer { margin-top: 20px; padding-top: 12px; border-top: 1px solid #e5e7eb; color: #64748b; font-size: 10px; display: table; width: 100%; }
        .footer div { display: table-cell; }
        .footer div:last-child { text-align: right; }
        @media print { body { background: white; } .print-actions { display: none; } .page { padding: 0; max-width: none; } }
    </style>
</head>
<body>
    <div class="print-actions"><button class="btn" onclick="window.print()">Print Document</button></div>
    <main class="page">
        <header class="header">
            <div class="header-left">
                <div class="company">Lelekwe Farm Ltd</div>
                <div class="report-title">{{ $title }}</div>
                <div class="subtitle">{{ $subtitle }}</div>
            </div>
            <div class="header-right">
                <span class="badge">Accounting Report</span>
                <div class="subtitle">Generated: {{ now()->format('d M Y H:i') }}</div>
            </div>
        </header>

        @if ($reportType === 'trial_balance')
            <section class="summary">
                <div class="summary-card"><small>Total Debits</small><strong>KES {{ number_format($totals['debits'], 2) }}</strong></div>
                <div class="summary-card"><small>Total Credits</small><strong>KES {{ number_format($totals['credits'], 2) }}</strong></div>
                <div class="summary-card"><small>Difference</small><strong>KES {{ number_format($totals['difference'], 2) }}</strong></div>
            </section>
            <table><thead><tr><th>Code</th><th>Account</th><th>Type</th><th class="right">Debit</th><th class="right">Credit</th></tr></thead><tbody>
                @foreach($rows as $row)<tr><td>{{ $row['code'] }}</td><td>{{ $row['name'] }}</td><td>{{ str($row['type'])->replace('_',' ')->headline() }}</td><td class="right">{{ number_format($row['debit_balance'], 2) }}</td><td class="right">{{ number_format($row['credit_balance'], 2) }}</td></tr>@endforeach
            </tbody><tfoot><tr><td colspan="3" class="right">Totals</td><td class="right">KES {{ number_format($totals['debits'], 2) }}</td><td class="right">KES {{ number_format($totals['credits'], 2) }}</td></tr></tfoot></table>
        @elseif ($reportType === 'general_ledger')
            <section class="summary">
                <div class="summary-card"><small>Account</small><strong>{{ $account->code }}</strong></div>
                <div class="summary-card"><small>Debits</small><strong>KES {{ number_format($totals['debits'], 2) }}</strong></div>
                <div class="summary-card"><small>Credits</small><strong>KES {{ number_format($totals['credits'], 2) }}</strong></div>
                <div class="summary-card"><small>Closing Balance</small><strong>KES {{ number_format($totals['closing_balance'], 2) }}</strong></div>
            </section>
            <table><thead><tr><th>Date</th><th>Journal</th><th>Reference</th><th>Description</th><th>Project</th><th class="right">Debit</th><th class="right">Credit</th><th class="right">Balance</th></tr></thead><tbody>
                @foreach($rows as $row)<tr><td>{{ $row['date'] }}</td><td>{{ $row['journal_number'] }}</td><td>{{ $row['reference'] }}</td><td>{{ $row['description'] }}</td><td>{{ $row['project'] ?: $row['cost_center'] }}</td><td class="right">{{ number_format($row['debit'], 2) }}</td><td class="right">{{ number_format($row['credit'], 2) }}</td><td class="right">{{ number_format($row['balance'], 2) }}</td></tr>@endforeach
            </tbody><tfoot><tr><td colspan="5" class="right">Totals</td><td class="right">KES {{ number_format($totals['debits'], 2) }}</td><td class="right">KES {{ number_format($totals['credits'], 2) }}</td><td class="right">KES {{ number_format($totals['closing_balance'], 2) }}</td></tr></tfoot></table>
        @elseif ($reportType === 'profit_and_loss')
            <section class="summary">
                <div class="summary-card"><small>Income</small><strong>KES {{ number_format($report['income'], 2) }}</strong></div>
                <div class="summary-card"><small>Gross Profit</small><strong>KES {{ number_format($report['gross_profit'], 2) }}</strong></div>
                <div class="summary-card"><small>Expenses</small><strong>KES {{ number_format($report['expenses'], 2) }}</strong></div>
                <div class="summary-card"><small>Net Profit</small><strong>KES {{ number_format($report['net_profit'], 2) }}</strong></div>
            </section>
            <table><thead><tr><th>Code</th><th>Account</th><th>Class</th><th class="right">Debit</th><th class="right">Credit</th><th class="right">Amount</th></tr></thead><tbody>
                @foreach($rows as $row)<tr><td>{{ $row['code'] }}</td><td>{{ $row['name'] }}</td><td>{{ str($row['type'])->replace('_',' ')->headline() }}</td><td class="right">{{ number_format($row['debits'], 2) }}</td><td class="right">{{ number_format($row['credits'], 2) }}</td><td class="right">{{ number_format($row['balance'], 2) }}</td></tr>@endforeach
            </tbody><tfoot><tr><td colspan="5" class="right">Net Profit / Loss</td><td class="right">KES {{ number_format($report['net_profit'], 2) }}</td></tr></tfoot></table>
        @elseif ($reportType === 'balance_sheet')
            <section class="summary">
                <div class="summary-card"><small>Assets</small><strong>KES {{ number_format($report['assets'], 2) }}</strong></div>
                <div class="summary-card"><small>Liabilities</small><strong>KES {{ number_format($report['liabilities'], 2) }}</strong></div>
                <div class="summary-card"><small>Equity + Profit</small><strong>KES {{ number_format($report['equity'] + $report['current_year_profit'], 2) }}</strong></div>
                <div class="summary-card"><small>Difference</small><strong>KES {{ number_format($report['difference'], 2) }}</strong></div>
            </section>
            <table><thead><tr><th>Code</th><th>Account</th><th>Class</th><th class="right">Debit</th><th class="right">Credit</th><th class="right">Balance</th></tr></thead><tbody>
                @foreach($rows as $row)<tr><td>{{ $row['code'] }}</td><td>{{ $row['name'] }}</td><td>{{ str($row['type'])->replace('_',' ')->headline() }}</td><td class="right">{{ number_format($row['debits'], 2) }}</td><td class="right">{{ number_format($row['credits'], 2) }}</td><td class="right">{{ number_format($row['balance'], 2) }}</td></tr>@endforeach
            </tbody><tfoot><tr><td colspan="5" class="right">Liabilities + Equity + Profit</td><td class="right">KES {{ number_format($report['liabilities_and_equity'], 2) }}</td></tr></tfoot></table>
        @endif

        <footer class="footer">
            <div>Prepared from Lelekwe ERP posted accounting records.</div>
            <div>Page generated by system</div>
        </footer>
    </main>
</body>
</html>
