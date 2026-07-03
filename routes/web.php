<?php

use App\Http\Controllers\LandingController;
use App\Http\Controllers\LiveViewController;
use App\Http\Controllers\PlayerAuthController;
use App\Http\Controllers\SpinActionController;
use App\Http\Controllers\SpinController;
use App\Livewire\Player\Register;
use App\Livewire\Player\RegistrationForm;
use App\Livewire\Player\VerifyOtp;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/
Route::get('/', [LandingController::class, 'index'])->name('home');

// Public event-screen display + its realtime bootstrap endpoint.
Route::get('/live-view', [LiveViewController::class, 'index'])->name('live-view');
Route::get('/live-view/active-spin', [LiveViewController::class, 'active'])->name('live-view.active');

/*
|--------------------------------------------------------------------------
| Player registration & OTP (guest-ish; guarded internally)
|--------------------------------------------------------------------------
*/
Route::get('/register', Register::class)->name('player.register');
Route::get('/verify-otp', VerifyOtp::class)->name('player.verify-otp');
Route::post('/logout', [PlayerAuthController::class, 'logout'])->name('player.logout');

/*
|--------------------------------------------------------------------------
| Authenticated player area
|--------------------------------------------------------------------------
*/
Route::middleware('player')->group(function () {
    // Dynamic registration form (must be completed before spinning).
    Route::get('/player/form', RegistrationForm::class)->name('player.form');

    // Spin experience requires the form to be complete.
    Route::middleware('player.form')->group(function () {
        Route::get('/spin', [SpinController::class, 'index'])->name('spin');
        Route::get('/result/{spin}', [SpinController::class, 'result'])->name('spin.result');

        // Secure spin action endpoints (session-authenticated JSON).
        Route::prefix('spin')->name('spin.')->group(function () {
            Route::get('/api/eligibility', [SpinActionController::class, 'eligibility'])->name('eligibility');
            Route::post('/api/geofence-check', [SpinActionController::class, 'geofenceCheck'])->name('geofence');
            Route::post('/api/start', [SpinActionController::class, 'start'])
                ->middleware('throttle:12,1')->name('start');
            Route::get('/api/active', [SpinActionController::class, 'active'])->name('active');
            Route::post('/api/{spin}/complete', [SpinActionController::class, 'complete'])->name('complete');
        });
    });
});

require __DIR__.'/admin.php';
