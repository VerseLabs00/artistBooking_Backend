<?php

use App\Http\Controllers\Api\ArtistProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [ArtistProfileController::class, 'show']);
    Route::put('/profile', [ArtistProfileController::class, 'update']);
    Route::post('/profile/media', [ArtistProfileController::class, 'updateMedia']);
    Route::post('/profile/gallery', [ArtistProfileController::class, 'addMedia']);
    Route::delete('/profile/gallery/{id}', [ArtistProfileController::class, 'deleteMedia']);
    Route::post('/profile/sync-links', [ArtistProfileController::class, 'syncExternalLinks']);
});
