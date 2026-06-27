<?php

require __DIR__.'/modules/auth.php';
require __DIR__.'/modules/onboarding.php';
require __DIR__.'/modules/artist.php';
require __DIR__.'/modules/discovery.php';
require __DIR__.'/modules/booking.php';
require __DIR__.'/modules/notification.php';
require __DIR__.'/modules/admin.php';
require __DIR__.'/modules/customer.php';

// Public (no-auth) endpoint — used by DevGate to check maintenance mode on every page load
Route::get('/settings/maintenance', [\App\Http\Controllers\Api\PublicSettingsController::class, 'index']);

Route::post('/contact', [\App\Http\Controllers\Api\ContactController::class, 'submit']);
