<?php

declare(strict_types=1);

use App\RealEstate\App\Controllers\CountryController;
use Illuminate\Support\Facades\Route;

Route::get('/real-estate/countries', [CountryController::class, 'index']);
Route::get('/real-estate/{code}', [CountryController::class, 'show']);
