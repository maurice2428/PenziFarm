<?php
use App\Filament\Resources\AuditLogResource;
use App\Http\Controllers\Api\MpesaC2BController;
use App\Http\Controllers\Api\MpesaCallbackController;
use App\Http\Controllers\Assets\AssetValuationReportController;
use App\Http\Controllers\Breeding\BreedingBatchReportController;
use App\Http\Controllers\Inventory\StockMovementReportController;
use App\Http\Controllers\Procurement\PaymentVoucherController;
use App\Http\Controllers\Procurement\PurchaseOrderInvoiceController;
use App\Http\Controllers\Procurement\SupplierStatementController;
use App\Http\Controllers\Projects\ProjectReportController;
use App\Http\Controllers\Reports\CasualPayrollReportController;
use App\Http\Controllers\Sales\SalesDashboardReportController;
use App\Http\Controllers\Sales\SalesPaymentReceiptController;
use App\Http\Controllers\AnimalLabRequestPdfController;
use App\Http\Controllers\AnimalProfilePdfController;
use App\Http\Controllers\AnimalWeightReportController;
use App\Http\Controllers\AuditSessionReportController;
use App\Http\Controllers\DataDocumentFileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin', 302);

/*
 * |--------------------------------------------------------------------------
 * | Public M-Pesa Callback
 * |--------------------------------------------------------------------------
 * | Must NOT use auth middleware. Safaricom needs to POST here directly.
 */

Route::post('/mpesa/stk-callback', [MpesaCallbackController::class, 'stkCallback'])
    ->name('api.mpesa.stk.callback');

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/admin/reports/animal-weights/bulk-report', [AnimalWeightReportController::class, 'bulkReport'])
        ->name('animal-weights.bulk-report');

    Route::get('/admin/reports/breed-weight-report', [\App\Http\Controllers\BreedWeightReportController::class, 'download'])
        ->name('reports.breed-weight-report');

    Route::get('/reports/casual-payroll/{casualPayroll}', CasualPayrollReportController::class)
        ->name('casual-payroll.report');

    Route::post('/mpesa/c2b/validation', [MpesaC2BController::class, 'validation'])
        ->name('mpesa.c2b.validation');

    Route::post('/mpesa/c2b/confirmation', [MpesaC2BController::class, 'confirmation'])
        ->name('mpesa.c2b.confirmation');

    Route::middleware(['web', 'auth'])->group(function () {
        Route::get('/admin/sales/payments/{salesPayment}/receipt', SalesPaymentReceiptController::class)
            ->name('sales-payments.receipt');
    });

    Route::middleware(['web', 'auth'])->group(function () {
        Route::get('/admin/sales/reports/dashboard-pdf', SalesDashboardReportController::class)
            ->name('sales.reports.dashboard-pdf');
    });

    Route::middleware(['auth'])->group(function () {
        Route::get(
            '/procurement/purchase-orders/{purchaseOrder}/invoice',
            PurchaseOrderInvoiceController::class
        )->name('procurement.purchase-orders.invoice');
    });

    Route::middleware(['auth'])->group(function () {
        Route::get('/procurement/suppliers/{supplier}/statement', SupplierStatementController::class)
            ->name('procurement.suppliers.statement');

        Route::get('/procurement/payments/{purchaseOrderPayment}/voucher', PaymentVoucherController::class)
            ->name('procurement.payments.voucher');
    });

    Route::middleware(['auth'])->group(function () {
        Route::get('/breeding/batches/print', BreedingBatchReportController::class)
            ->name('breeding.batches.print');
    });

    Route::middleware(['auth'])->group(function () {
        Route::get('/inventory/stock-movements/report', StockMovementReportController::class)
            ->name('inventory.stock-movements.report');

        /*Route::get('/procurement/suppliers/{supplier}/statement', SupplierStatementController::class)
            ->name('procurement.suppliers.statement');*/
    });

    Route::middleware(['auth'])->group(function () {
        Route::get('/assets/valuation-report', AssetValuationReportController::class)
            ->name('assets.valuation-report');
    });

    Route::middleware(['web', 'auth'])
        ->get('/admin/system/audit-sessions/{auditSession}/report', AuditSessionReportController::class)
        ->name('audit-sessions.report');

    Route::middleware(['web', 'auth'])
        ->prefix('admin/projects/reports')
        ->name('projects.reports.')
        ->group(function () {
            Route::get('/summary', [ProjectReportController::class, 'summary'])
                ->name('summary');

            Route::get('/expenses', [ProjectReportController::class, 'expenses'])
                ->name('expenses');

            Route::get('/budget-variance', [ProjectReportController::class, 'budgetVariance'])
                ->name('budget-variance');

            Route::get('/project/{project}', [ProjectReportController::class, 'projectDetail'])
                ->name('detail');
        });
    Route::middleware(['web', 'auth'])->group(function () {
        Route::get('/data-documents/{document}/open', [DataDocumentFileController::class, 'open'])
            ->name('data-documents.open');

        Route::get('/data-documents/{document}/download', [DataDocumentFileController::class, 'download'])
            ->name('data-documents.download');
    });
    Route::get(
        '/animals/{animal}/profile/pdf',
        AnimalProfilePdfController::class
    )->name('animals.profile.pdf');
    Route::get('/livewire/update', function () {
        return redirect('/admin');
    });
    Route::middleware('auth')
        ->get(
            '/animal-lab-requests/{labRequest}/pdf',
            AnimalLabRequestPdfController::class
        )
        ->name('animal-lab-requests.pdf');
});
require __DIR__ . '/accounting-reports.php';
require __DIR__ . '/accounting-reports.php';
require __DIR__ . '/farm-activity-reports.php';

/*
 * |--------------------------------------------------------------------------
 * | Public, signed audit-session PDF link
 * |--------------------------------------------------------------------------
 * |
 * | This route deliberately has no "auth" middleware. The URL is protected
 * | by Laravel's "signed" middleware and expires after seven days.
 * |
 */

Route::get(
    '/audit-sessions/{auditSession}/email-report.pdf',
    \App\Http\Controllers\Audit\AuditSessionEmailPdfController::class,
)
    ->middleware(['signed', 'throttle:30,1'])
    ->name('audit-sessions.email-report.pdf');

require __DIR__ . '/progeny.php';
