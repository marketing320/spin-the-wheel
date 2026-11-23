<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\Admin\PlayerExportController;
use App\Http\Controllers\Admin\SpinExportController;
use App\Livewire\Admin\Campaigns;
use App\Livewire\Admin\ChangePassword;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\FormsBuilder;
use App\Livewire\Admin\Geofence;
use App\Livewire\Admin\Login;
use App\Livewire\Admin\PlayRules;
use App\Livewire\Admin\Players;
use App\Livewire\Admin\Prizes;
use App\Livewire\Admin\Settings;
use App\Livewire\Admin\Spins;
use App\Livewire\Admin\Users;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {
    // Guest.
    Route::get('/login', Login::class)->name('admin.login');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

    // Staff and admins can access the dashboard and spin history.
    Route::middleware(['auth:web', 'staff'])->group(function () {
        Route::get('/', Dashboard::class)->name('admin.dashboard');
        Route::get('/spins', Spins::class)->name('admin.spins');
        Route::get('/spins/export', [SpinExportController::class, 'export'])->name('admin.spins.export');

        // Self-service: any signed-in back-office user may change their own password.
        Route::get('/account/password', ChangePassword::class)->name('admin.account.password');
    });

    // Admins only: campaign configuration, prizes, players, and settings.
    Route::middleware(['auth:web', 'admin'])->group(function () {
        Route::get('/campaigns', Campaigns::class)->name('admin.campaigns');
        Route::get('/prizes', Prizes::class)->name('admin.prizes');
        Route::get('/play-rules', PlayRules::class)->name('admin.play-rules');
        Route::get('/forms', FormsBuilder::class)->name('admin.forms');
        Route::get('/geofence', Geofence::class)->name('admin.geofence');
        Route::get('/players', Players::class)->name('admin.players');
        Route::get('/players/export', [PlayerExportController::class, 'export'])->name('admin.players.export');
        Route::get('/settings', Settings::class)->name('admin.settings');
    });

    // User management + destructive campaign reset: admins only, behind a
    // dedicated middleware on top of the auth guard.
    Route::middleware(['auth:web', 'user-manager'])->group(function () {
        Route::get('/users', Users::class)->name('admin.users');
    });
});
