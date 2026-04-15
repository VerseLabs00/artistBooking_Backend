<?php

use App\Http\Controllers\Api\BookingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Booking Routes
|--------------------------------------------------------------------------
|
| /api/bookings/notify  — PUBLIC (PayHere webhook, no auth)
| All other booking routes — auth:sanctum required
|
*/

// PayHere webhook — must be public, no auth, no CSRF
Route::post('bookings/notify', [BookingController::class, 'notify'])
     ->name('bookings.notify');

// All other booking routes require the customer to be logged in
Route::middleware('auth:sanctum')->group(function () {

    // POST /api/bookings/initiate — create booking + get PayHere payment data
    Route::post('bookings/initiate', [BookingController::class, 'initiate']);

    // GET /api/bookings — list own bookings
    Route::get('bookings', [BookingController::class, 'index']);

    // GET /api/bookings/{id} — single booking (includes bank details if confirmed)
    Route::get('bookings/{id}', [BookingController::class, 'show']);

    // POST /api/bookings/{id}/cancel — cancel a pending booking
    Route::post('bookings/{id}/cancel', [BookingController::class, 'cancel']);
});
