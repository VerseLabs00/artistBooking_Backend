<?php

use App\Http\Controllers\Api\ArtistDiscoveryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Artist Discovery Routes (Public — no auth required)
|--------------------------------------------------------------------------
|
| These routes power the customer-facing booking UI. They are intentionally
| unauthenticated so customers can browse without creating an account.
|
| Review submission (POST) optionally accepts a Bearer token to:
|   - Link the review to a registered customer account
|   - Prevent duplicate reviews from the same customer
|
*/

Route::prefix('discovery')->group(function () {

    // GET /api/discovery/categories
    Route::get('categories', [ArtistDiscoveryController::class, 'categories']);

    // GET /api/discovery/artists
    // Supports: ?category=Singer  ?search=nova  ?per_page=12
    Route::get('artists', [ArtistDiscoveryController::class, 'artists']);

    // GET /api/discovery/artists/{id}
    // Full profile view for a single artist (customer-side)
    Route::get('artists/{id}', [ArtistDiscoveryController::class, 'show']);

    // GET /api/discovery/artists/{id}/reviews
    // Paginated list of approved reviews + rating summary
    Route::get('artists/{id}/reviews', [ArtistDiscoveryController::class, 'reviews']);

    // POST /api/discovery/artists/{id}/reviews
    // Submit a review — works for both authenticated and guest customers
    Route::post('artists/{id}/reviews', [ArtistDiscoveryController::class, 'submitReview']);

    // GET /api/discovery/near-you
    // Requires: ?location=Colombo
    Route::get('near-you', [ArtistDiscoveryController::class, 'nearYou']);
});
