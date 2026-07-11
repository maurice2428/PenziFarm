<?php

use App\Http\Controllers\BreedingPerformanceReportController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')
    ->get(
        '/breeding-performance/{animal}/pdf',
        BreedingPerformanceReportController::class
    )
    ->name('breeding.performance.pdf');
