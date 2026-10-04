<?php

use App\Livewire\QosAnalytics;
use App\Livewire\TelemetryDashboard;
use Illuminate\Support\Facades\Route;

Route::get('/', TelemetryDashboard::class)->name('telemetry');
Route::get('/qos-analytics', QosAnalytics::class)->name('qos-analytics');
