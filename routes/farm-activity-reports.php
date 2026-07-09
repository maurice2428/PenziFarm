<?php

use App\Http\Controllers\Reports\FarmActivitySnapshotPdfController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'permission:download accounting pdf reports'])->group(function () {
    Route::get('/farm-activity-explorer/pdf', FarmActivitySnapshotPdfController::class)
        ->name('farm-activity-explorer.pdf');

    Route::get('/farm-activity-explorer/print', function () {
        return redirect()->route('farm-activity-explorer.pdf', request()->query());
    })->name('farm-activity-explorer.print');
});
