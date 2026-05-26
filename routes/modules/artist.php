<?php

use App\Http\Controllers\Api\ArtistBankController;
use App\Http\Controllers\Api\ArtistProfileController;
use App\Http\Controllers\Api\ArtistBookingRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/profile', [ArtistProfileController::class, 'show']);
    Route::put('/profile', [ArtistProfileController::class, 'update']);
    Route::match(['post', 'put'], '/profile/media', [ArtistProfileController::class, 'updateMedia']);
    Route::post('/profile/gallery', [ArtistProfileController::class, 'addMedia']);
    Route::delete('/profile/gallery/{id}', [ArtistProfileController::class, 'deleteMedia']);
    Route::post('/profile/sync-links', [ArtistProfileController::class, 'syncExternalLinks']);


    Route::get('/profile/bank', [ArtistBankController::class, 'show']);


    Route::post('/profile/bank', [ArtistBankController::class, 'upsert']);


    Route::get('/profile/bank/booking-view/{artistProfileId}', [ArtistBankController::class, 'bookingView']);


    Route::get('/bookings/dashboard', [ArtistBookingRequestController::class, 'dashboard']);
    Route::prefix('artist')->group(function () {
        Route::get('/bookings', [ArtistBookingRequestController::class, 'index']);
        Route::get('/bookings/{id}', [ArtistBookingRequestController::class, 'show']);
        Route::put('/bookings/{id}/status', [ArtistBookingRequestController::class, 'updateStatus']);
    });
});
