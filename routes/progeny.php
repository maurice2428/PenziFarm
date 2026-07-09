<?php

use App\Http\Controllers\ProgenyReportController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('/breeding/progeny/{animal}/pdf', ProgenyReportController::class)
        ->name('breeding.progeny.pdf');
});
