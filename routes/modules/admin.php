<?php

use App\Http\Controllers\Api\Admin\ArtistController;
use App\Http\Controllers\Api\Admin\BookingController;
use App\Http\Controllers\Api\Admin\CustomerController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\DuePaymentsController;
use App\Http\Controllers\Api\Admin\NotificationController;
use App\Http\Controllers\Api\Admin\SettingsController;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {

    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

    Route::get('/artists', [ArtistController::class, 'index']);
    Route::get('/artists/{id}', [ArtistController::class, 'show']);
    Route::put('/artists/{id}/verify', [ArtistController::class, 'verify']);
    Route::put('/artists/{id}/toggle-onboard', [ArtistController::class, 'toggleOnboard']);
    Route::delete('/artists/{id}', [ArtistController::class, 'destroy']);

    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::put('/bookings/{id}/status', [BookingController::class, 'updateStatus']);

    Route::get('/customers', [CustomerController::class, 'index']);
    Route::get('/customers/{id}', [CustomerController::class, 'show']);
    Route::put('/customers/{id}/ban', [CustomerController::class, 'ban']);
    Route::put('/customers/{id}/unban', [CustomerController::class, 'unban']);
    Route::delete('/customers/{id}', [CustomerController::class, 'destroy']);

    Route::get('/settings', [SettingsController::class, 'index']);
    Route::put('/settings', [SettingsController::class, 'update']);

    // Due Payments — advance amounts admin needs to send to artists
    Route::get('/due-payments', [DuePaymentsController::class, 'index']);
    Route::post('/due-payments/{id}/mark-sent', [DuePaymentsController::class, 'markSent']);
});
