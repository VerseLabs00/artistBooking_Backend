<?php

use App\Http\Controllers\Api\ArtistBankController;
use App\Http\Controllers\Api\ArtistProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // ── Profile ────────────────────────────────────────────────────────────────
    Route::get('/profile', [ArtistProfileController::class, 'show']);
    Route::put('/profile', [ArtistProfileController::class, 'update']);
    Route::post('/profile/media', [ArtistProfileController::class, 'updateMedia']);
    Route::post('/profile/gallery', [ArtistProfileController::class, 'addMedia']);
    Route::delete('/profile/gallery/{id}', [ArtistProfileController::class, 'deleteMedia']);
    Route::post('/profile/sync-links', [ArtistProfileController::class, 'syncExternalLinks']);

    // ── Bank Details ───────────────────────────────────────────────────────────
    // Artist: view own bank details (masked account number by default)
    Route::get('/profile/bank', [ArtistBankController::class, 'show']);

    // Artist: save or update bank details
    Route::post('/profile/bank', [ArtistBankController::class, 'upsert']);

    // Booking system: fetch full bank details for a confirmed booking payment
    // Pass ?artistProfileId= of the booked artist
    Route::get('/profile/bank/booking-view/{artistProfileId}', [ArtistBankController::class, 'bookingView']);
});
