<?php

declare(strict_types=1);

use App\RealEstate\App\Controllers\CountryController;
use App\RealEstate\App\Controllers\DetailedController;
use Illuminate\Support\Facades\Route;

Route::get('/real-estate/countries', [CountryController::class, 'index']);
Route::get('/real-estate/{code}', [CountryController::class, 'show']);
Route::get('/real-estate/{code}/detailed', [DetailedController::class, 'show']);
Route::get('/real-estate/{code}/detailed/series', [DetailedController::class, 'series']);
