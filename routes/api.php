<?php

use App\Http\Controllers\Api\V1\MobileAuthController;
use App\Http\Controllers\Api\V1\MobileProfileController;
use App\Http\Controllers\Api\V1\MobileVehicleController;
use App\Http\Controllers\Api\V1\PublicContentController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:120,1')->group(function () {
    Route::post('/auth/login', [MobileAuthController::class, 'login'])->middleware('throttle:5,1');

    Route::get('/leaderboard', [PublicContentController::class, 'leaderboard']);

    Route::get('/events', [PublicContentController::class, 'events']);
    Route::get('/events/{event}', [PublicContentController::class, 'event'])->whereNumber('event');

    Route::get('/articles', [PublicContentController::class, 'articles']);
    Route::get('/articles/{article:slug}', [PublicContentController::class, 'article']);

    Route::get('/features', [PublicContentController::class, 'features']);
    Route::get('/features/{article:slug}', [PublicContentController::class, 'feature']);

    Route::get('/garages', [PublicContentController::class, 'garages']);
    Route::get('/garages/{organization}', [PublicContentController::class, 'garage'])->whereNumber('organization');

    Route::get('/mechanics', [PublicContentController::class, 'mechanics']);
    Route::get('/mechanics/{organization}', [PublicContentController::class, 'mechanic'])->whereNumber('organization');

    Route::middleware(['auth:sanctum', 'phone.verified'])->group(function () {
        Route::post('/auth/logout', [MobileAuthController::class, 'logout']);

        Route::get('/profile', [MobileProfileController::class, 'show']);
        Route::patch('/profile', [MobileProfileController::class, 'update']);
        Route::get('/profile/photo', [MobileProfileController::class, 'image']);
        Route::post('/profile/photo', [MobileProfileController::class, 'photo']);
        Route::delete('/profile/photo', [MobileProfileController::class, 'destroyPhoto']);

        Route::get('/cars', [MobileVehicleController::class, 'index']);
        Route::post('/cars', [MobileVehicleController::class, 'store']);
        Route::get('/cars/{vehicle}', [MobileVehicleController::class, 'show'])->whereNumber('vehicle');
        Route::patch('/cars/{vehicle}', [MobileVehicleController::class, 'update'])->whereNumber('vehicle');
        Route::delete('/cars/{vehicle}', [MobileVehicleController::class, 'destroy'])->whereNumber('vehicle');
        Route::get('/cars/{vehicle}/photo', [MobileVehicleController::class, 'image'])->whereNumber('vehicle');
        Route::post('/cars/{vehicle}/photo', [MobileVehicleController::class, 'photo'])->whereNumber('vehicle');
        Route::delete('/cars/{vehicle}/photo', [MobileVehicleController::class, 'destroyPhoto'])->whereNumber('vehicle');
    });
});
