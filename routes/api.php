<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Spatie\Health\Http\Controllers\HealthCheckJsonResultsController;

Route::get('/health', HealthCheckJsonResultsController::class)->middleware('throttle:api');

Route::get('/version', fn () => response()->json([
    'version' => config('app.version'),
    'laravel' => app()->version(),
    'php' => PHP_VERSION,
]))->middleware('throttle:api');
