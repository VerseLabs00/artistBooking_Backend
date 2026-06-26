<?php

use App\Http\Controllers\Api\OnboardingController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/onboarding/status',        [OnboardingController::class, 'status']);
    Route::get('/onboarding/basic-info',    [OnboardingController::class, 'getBasicInfo']);
    Route::get('/onboarding/verification',  [OnboardingController::class, 'getVerification']);
    Route::get('/onboarding/talent',        [OnboardingController::class, 'getTalent']);
    Route::post('/onboarding/basic-info',   [OnboardingController::class, 'storeBasicInfo']);
    Route::post('/onboarding/verification', [OnboardingController::class, 'uploadVerification']);
    Route::post('/onboarding/talent',       [OnboardingController::class, 'storeTalent']);
    Route::delete('/onboarding/cancel',      [OnboardingController::class, 'cancelRegistration']);
});
