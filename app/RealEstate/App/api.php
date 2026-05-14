<?php

declare(strict_types=1);

use App\RealEstate\App\Controllers\CountryController;
use App\RealEstate\App\Controllers\DetailedController;
use Illuminate\Support\Facades\Route;

Route::get('/real-estate/countries', [CountryController::class, 'index'])->name('real-estate.countries');
Route::get('/real-estate/{code}', [CountryController::class, 'show'])->name('real-estate.show');
Route::get('/real-estate/{code}/detailed', [DetailedController::class, 'show'])->name('real-estate.detailed');
Route::get('/real-estate/{code}/detailed/series', [DetailedController::class, 'series'])->name('real-estate.detailed.series');
