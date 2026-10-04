<?php

use App\Livewire\NavigationManagement;
use App\Livewire\QosAnalytics;
use App\Livewire\RbacManagement;
use App\Livewire\TelemetryDashboard;
use App\Livewire\UserDashboard;
use App\Livewire\UserHistory;
use Illuminate\Support\Facades\Route;

// Tampilan User / Publik (Clean, Non-Teknis)
Route::get('/', UserDashboard::class)->name('user.dashboard');
Route::get('/riwayat', UserHistory::class)->name('user.history');

// Panel Admin / Peneliti (Teknis, QoS & Sensor Nodes)
Route::get('/admin/telemetry', TelemetryDashboard::class)->name('telemetry');
Route::get('/admin/qos-analytics', QosAnalytics::class)->name('qos-analytics');
Route::get('/admin/sessions-management', QosAnalytics::class)->name('sessions-management');
Route::get('/admin/rbac-management', RbacManagement::class)->name('rbac-management');
Route::get('/admin/navigation-management', NavigationManagement::class)->name('navigation-management');
