<?php

use App\Http\Controllers\Accounting\AccountingReportPrintController;
use App\Http\Middleware\EnsureAccountingPdfPermission;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'web',
    'auth',
    EnsureAccountingPdfPermission::class,
])
    ->prefix('accounting-reports')
    ->name('accounting.reports.')
    ->group(function (): void {
        Route::get(
            'trial-balance/print',
            [
                AccountingReportPrintController::class,
                'trialBalancePrint',
            ]
        )->name('trial-balance.print');

        Route::get(
            'trial-balance/pdf',
            [
                AccountingReportPrintController::class,
                'trialBalancePdf',
            ]
        )->name('trial-balance.pdf');

        Route::get(
            'general-ledger/print',
            [
                AccountingReportPrintController::class,
                'generalLedgerPrint',
            ]
        )->name('general-ledger.print');

        Route::get(
            'general-ledger/pdf',
            [
                AccountingReportPrintController::class,
                'generalLedgerPdf',
            ]
        )->name('general-ledger.pdf');

        Route::get(
            'profit-and-loss/print',
            [
                AccountingReportPrintController::class,
                'profitAndLossPrint',
            ]
        )->name('profit-and-loss.print');

        Route::get(
            'profit-and-loss/pdf',
            [
                AccountingReportPrintController::class,
                'profitAndLossPdf',
            ]
        )->name('profit-and-loss.pdf');

        Route::get(
            'balance-sheet/print',
            [
                AccountingReportPrintController::class,
                'balanceSheetPrint',
            ]
        )->name('balance-sheet.print');

        Route::get(
            'balance-sheet/pdf',
            [
                AccountingReportPrintController::class,
                'balanceSheetPdf',
            ]
        )->name('balance-sheet.pdf');

        Route::get(
            'cash-flow/print',
            [
                AccountingReportPrintController::class,
                'cashFlowPrint',
            ]
        )->name('cash-flow.print');

        Route::get(
            'cash-flow/pdf',
            [
                AccountingReportPrintController::class,
                'cashFlowPdf',
            ]
        )->name('cash-flow.pdf');
    });
