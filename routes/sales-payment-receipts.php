<?php

use App\Http\Controllers\Sales\SalesPaymentReceiptController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')
    ->get(
        '/admin/sales/payments/{salesPayment}/receipt',
        SalesPaymentReceiptController::class
    )
    ->name('sales-payments.receipt');
