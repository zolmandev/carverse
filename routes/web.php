<?php

use App\Http\Controllers\CarCatalogController;
use App\Http\Controllers\CarverseController as C;
use App\Http\Controllers\PaymentCallbackController;
use App\Http\Controllers\SuperUserController;
use App\Http\Middleware\RetiredWorkflow;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('carverse/welcome'))->name('home');
Route::get('/photos/{type}/{id}', [C::class, 'photo'])->middleware('throttle:120,1');
Route::get('/cars/brands/{brand}/logo', [CarCatalogController::class, 'logo']);
Route::get('/provider-logos/{organization}', [C::class, 'providerLogo'])->middleware('throttle:120,1');
Route::get('/directory', [C::class, 'publicDirectory']);
Route::get('/history/{token}', [C::class, 'shared'])->middleware('throttle:30,1');
Route::post('/payments/callback/{token}', PaymentCallbackController::class)->middleware('throttle:60,1');
Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('/dashboard', '/app')->name('dashboard');
    Route::get('/app/{section?}', [C::class, 'index'])->whereIn('section', ['overview', 'garage', 'parts', 'providers', 'requests', 'passport', 'reminders', 'community', 'provider', 'admin', 'preferences']);
    Route::post('/vehicles', [C::class, 'vehicle']);
    Route::put('/vehicles/{vehicle}', [C::class, 'updateVehicle']);
    Route::post('/vehicles/{vehicle}/active', [C::class, 'active']);
    Route::post('/requests/{serviceRequest}/cancel', [C::class, 'cancelRequest']);
    Route::put('/articles/{article}', [C::class, 'content']);
    Route::post('/requests', [C::class, 'requestQuote'])->middleware('throttle:10,1');
    Route::post('/requests/{serviceRequest}/quotes', [C::class, 'quote']);
    Route::post('/quotes/{quote}/accept', [C::class, 'accept']);
    Route::post('/transactions/{transaction}/status', [C::class, 'transition']);
    Route::post('/transactions/{transaction}/pay', [C::class, 'pay'])->middleware('throttle:5,1');
    Route::post('/records', [C::class, 'record']);
    Route::post('/records/{record}/correct', [C::class, 'correct']);
    Route::post('/reminders', [C::class, 'reminder']);
    Route::post('/transactions/{transaction}/reviews', [C::class, 'review']);
    Route::post('/reviews/{review}/response', [C::class, 'respondReview']);
    Route::post('/transactions/{transaction}/complaints', [C::class, 'complain']);
    Route::post('/complaints/{complaint}', [C::class, 'complaintAction']);
    Route::get('/attachments/{attachment}', [C::class, 'download']);
    Route::post('/vehicles/{vehicle}/shares', [C::class, 'share'])->middleware('throttle:10,1');
    Route::delete('/shares/{share}', [C::class, 'revoke']);
    Route::post('/providers/{organization}/logo', [C::class, 'updateProviderLogo']);
    Route::post('/providers/apply', [C::class, 'apply'])->middleware('throttle:5,10');
    Route::post('/providers/{organization}/review', [C::class, 'approve']);
    Route::post('/parts', [C::class, 'part']);
    Route::put('/parts/{part}', [C::class, 'part']);
    Route::post('/parts/{part}/fitment', [C::class, 'fitment']);
    Route::post('/articles', [C::class, 'content']);
    Route::post('/articles/{article}/publish', [C::class, 'publish']);
    Route::get('/cars/brands/{brand}/models', [CarCatalogController::class, 'models']);
    Route::get('/cars/models/{model}/trims', [CarCatalogController::class, 'trims']);
    Route::post('/cars/brands', [CarCatalogController::class, 'brand']);
    Route::put('/cars/brands/{brand}', [CarCatalogController::class, 'brand']);
    Route::post('/cars/brands/{brand}/restore', [CarCatalogController::class, 'restoreBrand']);
    Route::post('/cars/models', [CarCatalogController::class, 'model']);
    Route::put('/cars/models/{model}', [CarCatalogController::class, 'model']);
    Route::post('/cars/trims', [CarCatalogController::class, 'trim']);
    Route::put('/cars/trims/{trim}', [CarCatalogController::class, 'trim']);
    Route::delete('/cars/catalog/{kind}/{id}', [CarCatalogController::class, 'archive']);
    Route::put('/communities/{community}', [C::class, 'community']);
    Route::post('/racing', [SuperUserController::class, 'storeRace']);
    Route::post('/racing/{entry}/review', [SuperUserController::class, 'reviewRace']);
    Route::get('/racing/{entry}/evidence/{kind}', [SuperUserController::class, 'evidence']);
    Route::delete('/communities/{community}', [C::class, 'removeCommunity']);
    Route::post('/communities', [C::class, 'community']);
    Route::post('/transactions/{transaction}/finance', [C::class, 'finance']);
    Route::post('/providers/{organization}/members', [C::class, 'membership']);
    Route::post('/users/{user}/role', [C::class, 'role']);
    Route::post('/account/deletion', [C::class, 'deletion']);
    Route::post('/deletions/{deletionRequest}', [C::class, 'reviewDeletion']);
    Route::post('/records/{record}/evidence-review', [C::class, 'reviewEvidence']);
    Route::post('/preferences', [C::class, 'preferences']);
    Route::get('/account/export', [C::class, 'export']);
});
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

// Retired product modules are inaccessible, including existing bookmarked links.
foreach (Route::getRoutes() as $route) {
    $uri = $route->uri();
    if (preg_match('~^(requests(?:/|$)|quotes/|records(?:/|$)|reminders(?:/|$)|history/|shares/|payments/|vehicles/[^/]+/shares|transactions/[^/]+/(status|pay|reviews|complaints)$)~', $uri)) {
        $route->middleware(RetiredWorkflow::class);
    }
}
