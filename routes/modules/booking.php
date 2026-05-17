<?php

use App\Http\Controllers\Api\BookingController;
use Illuminate\Support\Facades\Route;


Route::post('bookings/notify', [BookingController::class, 'notify'])
     ->name('bookings.notify');


Route::middleware('auth:sanctum')->group(function () {


    Route::post('bookings/initiate', [BookingController::class, 'initiate']);


    Route::get('bookings', [BookingController::class, 'index']);


    Route::get('bookings/{id}', [BookingController::class, 'show']);


    Route::post('bookings/{id}/cancel', [BookingController::class, 'cancel']);
});
