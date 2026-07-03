<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\Admin\PlayerExportController;
use App\Http\Controllers\Admin\SpinExportController;
use App\Livewire\Admin\Campaigns;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\FormsBuilder;
use App\Livewire\Admin\Geofence;
use App\Livewire\Admin\LiveViewSettings;
use App\Livewire\Admin\Login;
use App\Livewire\Admin\PlayRules;
use App\Livewire\Admin\Players;
use App\Livewire\Admin\Prizes;
use App\Livewire\Admin\Settings;
use App\Livewire\Admin\Spins;
use App\Livewire\Admin\WheelDesign;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {
    // Guest.
    Route::get('/login', Login::class)->name('admin.login');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

    // Authenticated admins only.
    Route::middleware(['auth:web', 'admin'])->group(function () {
        Route::get('/', Dashboard::class)->name('admin.dashboard');
        Route::get('/campaigns', Campaigns::class)->name('admin.campaigns');
        Route::get('/prizes', Prizes::class)->name('admin.prizes');
        Route::get('/wheel', WheelDesign::class)->name('admin.wheel');
        Route::get('/play-rules', PlayRules::class)->name('admin.play-rules');
        Route::get('/forms', FormsBuilder::class)->name('admin.forms');
        Route::get('/geofence', Geofence::class)->name('admin.geofence');
        Route::get('/live-view', LiveViewSettings::class)->name('admin.live-view');
        Route::get('/spins', Spins::class)->name('admin.spins');
        Route::get('/spins/export', [SpinExportController::class, 'export'])->name('admin.spins.export');
        Route::get('/players', Players::class)->name('admin.players');
        Route::get('/players/export', [PlayerExportController::class, 'export'])->name('admin.players.export');
        Route::get('/settings', Settings::class)->name('admin.settings');
    });
});
