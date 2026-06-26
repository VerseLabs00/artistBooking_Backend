<?php

use App\Http\Controllers\Api\FavoriteController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('favorites/toggle', [FavoriteController::class, 'toggle']);
    Route::get('favorites', [FavoriteController::class, 'index']);
    Route::get('favorites/customers/{artistProfileId}', [FavoriteController::class, 'customers']);
});
