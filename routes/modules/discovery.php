<?php

use App\Http\Controllers\Api\ArtistDiscoveryController;
use Illuminate\Support\Facades\Route;

Route::prefix('discovery')->group(function () {

    // GET /api/discovery/categories
    Route::get('categories', [ArtistDiscoveryController::class, 'categories']);

    // GET /api/discovery/artists
    Route::get('artists', [ArtistDiscoveryController::class, 'artists']);

    // GET /api/discovery/artists/{id}
    Route::get('artists/{id}', [ArtistDiscoveryController::class, 'show']);

    // GET /api/discovery/artists/{id}/reviews
    Route::get('artists/{id}/reviews', [ArtistDiscoveryController::class, 'reviews']);

    // POST /api/discovery/artists/{id}/reviews
    Route::post('artists/{id}/reviews', [ArtistDiscoveryController::class, 'submitReview']);

    // GET /api/discovery/near-you
    Route::get('near-you', [ArtistDiscoveryController::class, 'nearYou']);
});
