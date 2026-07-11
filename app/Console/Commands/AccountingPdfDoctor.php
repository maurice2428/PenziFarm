<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

class AccountingPdfDoctor extends Command
{
    protected $signature =
        'accounting-pdfs:paper-check';

    protected $description =
        'Check the invoice-paper-sized accounting PDF layout.';

    public function handle(): int
    {
        $viewPath = resource_path(
            'views/reports/accounting/'
            . 'accounting-print.blade.php'
        );

        $view = is_file($viewPath)
            ? file_get_contents($viewPath)
            : '';

        $checks = [
            [
                'Accounting PDF view',
                View::exists(
                    'reports.accounting.accounting-print'
                )
                    ? 'YES'
                    : 'NO',
            ],
            [
                'Exact invoice page margins',
                str_contains(
                    $view,
                    'margin: 118px 34px 92px 34px'
                )
                    ? 'YES'
                    : 'NO',
            ],
            [
                'Fixed invoice-style header',
                str_contains(
                    $view,
                    'top: -96px'
                )
                    ? 'YES'
                    : 'NO',
            ],
            [
                'Fixed invoice-style footer',
                str_contains(
                    $view,
                    'bottom: -70px'
                )
                    ? 'YES'
                    : 'NO',
            ],
            [
                'Courier body typography',
                str_contains(
                    $view,
                    'font-family: Courier'
                )
                    ? 'YES'
                    : 'NO',
            ],
            [
                'Wrap-text report cells',
                str_contains(
                    $view,
                    '.wrap'
                )
                && str_contains(
                    $view,
                    'overflow-wrap: break-word'
                )
                    ? 'YES'
                    : 'NO',
            ],
            [
                'Prepared By',
                str_contains(
                    $view,
                    'Prepared By'
                )
                    ? 'YES'
                    : 'NO',
            ],
            [
                'Authorized Signature',
                str_contains(
                    $view,
                    'Authorized Signature'
                )
                    ? 'YES'
                    : 'NO',
            ],
            [
                'Official Stamp',
                str_contains(
                    $view,
                    'Official Stamp'
                )
                    ? 'YES'
                    : 'NO',
            ],
            [
                'Verification QR',
                str_contains(
                    $view,
                    'Verification'
                )
                && str_contains(
                    $view,
                    '$qrDataUri'
                )
                    ? 'YES'
                    : 'NO',
            ],
            [
                'Page X of Y',
                str_contains(
                    $view,
                    'Page {$pageNumber} of {$pageCount}'
                )
                    ? 'YES'
                    : 'NO',
            ],
        ];

        $this->table(
            ['Check', 'Status'],
            $checks
        );

        $routes = [
            'accounting.reports.trial-balance.pdf',
            'accounting.reports.general-ledger.pdf',
            'accounting.reports.profit-and-loss.pdf',
            'accounting.reports.balance-sheet.pdf',
            'accounting.reports.cash-flow.pdf',
        ];

        $routeRows = collect($routes)
            ->map(
                fn (string $name): array => [
                    $name,
                    Route::has($name)
                        ? 'YES'
                        : 'NO',
                ]
            )
            ->all();

        $this->table(
            ['Route', 'Registered'],
            $routeRows
        );

        $healthy =
            collect($checks)
                ->every(
                    fn (array $row): bool =>
                        $row[1] === 'YES'
                )
            && collect($routeRows)
                ->every(
                    fn (array $row): bool =>
                        $row[1] === 'YES'
                );

        $this->newLine();

        $this->line(
            'Overall status: '
            . (
                $healthy
                    ? 'HEALTHY'
                    : 'REVIEW REQUIRED'
            )
        );

        return $healthy
            ? self::SUCCESS
            : self::FAILURE;
    }
}
