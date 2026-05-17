<?php

use App\Http\Controllers\Api\Admin\ArtistController;
use App\Http\Controllers\Api\Admin\BookingController;
use App\Http\Controllers\Api\Admin\CustomerController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\NotificationController;
use App\Http\Controllers\Api\Admin\SettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin API Routes
|--------------------------------------------------------------------------
|
| All routes are grouped under /api/admin and protected by auth and admin middleware.
|
|
*/

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {

    // ── Dashboard ──────────────────────────────────────────────────────────────
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    // ── Notification Management ────────────────────────────────────────────────
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

    // ── Artist Management ──────────────────────────────────────────────────────
    Route::get('/artists', [ArtistController::class, 'index']);
    Route::get('/artists/{id}', [ArtistController::class, 'show']);
    Route::put('/artists/{id}/verify', [ArtistController::class, 'verify']);
    Route::put('/artists/{id}/toggle-onboard', [ArtistController::class, 'toggleOnboard']);
    Route::delete('/artists/{id}', [ArtistController::class, 'destroy']);

    // ── Booking Management ─────────────────────────────────────────────────────
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::put('/bookings/{id}/status', [BookingController::class, 'updateStatus']);

    // ── Customer Management ────────────────────────────────────────────────────
    Route::get('/customers', [CustomerController::class, 'index']);
    Route::get('/customers/{id}', [CustomerController::class, 'show']);
    Route::put('/customers/{id}/ban', [CustomerController::class, 'ban']);
    Route::put('/customers/{id}/unban', [CustomerController::class, 'unban']);
    Route::delete('/customers/{id}', [CustomerController::class, 'destroy']);

    // ── Platform Settings ──────────────────────────────────────────────────────
    Route::get('/settings', [SettingsController::class, 'index']);
    Route::put('/settings', [SettingsController::class, 'update']);
});