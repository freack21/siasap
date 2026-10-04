<?php

use App\Livewire\NavigationManagement;
use App\Livewire\QosAnalytics;
use App\Livewire\RbacManagement;
use App\Livewire\TelemetryDashboard;
use Illuminate\Support\Facades\Route;

Route::get('/', TelemetryDashboard::class)->name('telemetry');
Route::get('/qos-analytics', QosAnalytics::class)->name('qos-analytics');
Route::get('/sessions-management', QosAnalytics::class)->name('sessions-management');
Route::get('/rbac-management', RbacManagement::class)->name('rbac-management');
Route::get('/navigation-management', NavigationManagement::class)->name('navigation-management');
