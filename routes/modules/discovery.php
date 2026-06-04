<?php

use App\Http\Controllers\Api\ArtistDiscoveryController;
use Illuminate\Support\Facades\Route;

Route::prefix('discovery')->group(function () {


    Route::get('categories', [ArtistDiscoveryController::class, 'categories']);


    Route::get('stats', [ArtistDiscoveryController::class, 'stats']);


    Route::get('artists', [ArtistDiscoveryController::class, 'artists']);


    Route::get('artists/{id}', [ArtistDiscoveryController::class, 'show']);


    Route::get('artists/{id}/reviews', [ArtistDiscoveryController::class, 'reviews']);


    Route::post('artists/{id}/reviews', [ArtistDiscoveryController::class, 'submitReview']);


    Route::get('near-you', [ArtistDiscoveryController::class, 'nearYou']);
});
