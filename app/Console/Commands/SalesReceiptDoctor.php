<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

class SalesReceiptDoctor extends Command
{
    protected $signature =
        'sales-receipt:doctor';

    protected $description =
        'Check the classic sales payment receipt route and PDF layout.';

    public function handle(): int
    {
        $viewPath = resource_path(
            'views/pdf/sales-payment-receipt.blade.php'
        );

        $view = is_file($viewPath)
            ? file_get_contents($viewPath)
            : '';

        $checks = [
            [
                'Receipt route',
                Route::has('sales-payments.receipt')
                    ? 'YES'
                    : 'NO',
            ],
            [
                'Receipt PDF view',
                View::exists(
                    'pdf.sales-payment-receipt'
                )
                    ? 'YES'
                    : 'NO',
            ],
            [
                'Invoice paper margins',
                str_contains(
                    $view,
                    'margin: 118px 34px 92px 34px'
                )
                    ? 'YES'
                    : 'NO',
            ],
            [
                'Fixed branded header',
                str_contains($view, '<header>')
                && str_contains($view, '$logoBase64')
                    ? 'YES'
                    : 'NO',
            ],
            [
                'Fixed footer',
                str_contains($view, '<footer>')
                && str_contains(
                    $view,
                    'Sales Payment Receipt'
                )
                    ? 'YES'
                    : 'NO',
            ],
            [
                'Courier typography',
                str_contains(
                    $view,
                    'font-family: Courier'
                )
                    ? 'YES'
                    : 'NO',
            ],
            [
                'Authorized signature',
                str_contains(
                    $view,
                    'Authorized Signature'
                )
                && str_contains(
                    $view,
                    '$signatureBase64'
                )
                    ? 'YES'
                    : 'NO',
            ],
            [
                'Official stamp',
                str_contains(
                    $view,
                    'Official Stamp'
                )
                && str_contains(
                    $view,
                    '$stampBase64'
                )
                    ? 'YES'
                    : 'NO',
            ],
            [
                'Verification QR',
                str_contains(
                    $view,
                    '$qrDataUri'
                )
                    ? 'YES'
                    : 'NO',
            ],
            [
                'Equal receipt cards',
                str_contains(
                    $view,
                    '<col style="width: 50%;">'
                )
                    ? 'YES'
                    : 'NO',
            ],
            [
                'Equal confirmation columns',
                substr_count(
                    $view,
                    '<col style="width: 25%;">'
                ) >= 8
                    ? 'YES'
                    : 'NO',
            ],
            [
                'Equal allocation proportions',
                str_contains(
                    $view,
                    '<col style="width: 62%;">'
                )
                && str_contains(
                    $view,
                    '<col style="width: 38%;">'
                )
                    ? 'YES'
                    : 'NO',
            ],
            [
                'Equal approval cards',
                str_contains(
                    $view,
                    '.signature-card {'
                )
                && str_contains(
                    $view,
                    'width: 25%;'
                )
                    ? 'YES'
                    : 'NO',
            ],
            [
                'Flush-left grouped tables',
                str_contains(
                    $view,
                    'border-spacing: 0;'
                )
                && str_contains(
                    $view,
                    'border-collapse: collapse;'
                )
                && str_contains(
                    $view,
                    'margin-left: 0;'
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

        $healthy = collect($checks)
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
