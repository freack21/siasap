<?php

use App\Livewire\Auth\Login;
use App\Livewire\NavigationManagement;
use App\Livewire\QosAnalytics;
use App\Livewire\RbacManagement;
use App\Livewire\TelemetryDashboard;
use App\Livewire\UserDashboard;
use App\Livewire\UserHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Guest Routes (Public Login)
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

// Logout Route
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('login');
})->name('logout')->middleware('auth');

// Protected Dashboard Routes (Requires Authenticated User)
Route::middleware('auth')->group(function () {
    // Tampilan User / Publik (Clean, Non-Teknis)
    Route::get('/', UserDashboard::class)->name('user.dashboard');
    Route::get('/riwayat', UserHistory::class)->name('user.history');

    // Panel Admin / Peneliti (Teknis, QoS & Sensor Nodes)
    Route::get('/admin/telemetry', TelemetryDashboard::class)->name('telemetry');
    Route::get('/admin/qos-analytics', QosAnalytics::class)->name('qos-analytics');
    Route::get('/admin/sessions-management', QosAnalytics::class)->name('sessions-management');
    Route::get('/admin/rbac-management', RbacManagement::class)->name('rbac-management');
    Route::get('/admin/navigation-management', NavigationManagement::class)->name('navigation-management');
});
