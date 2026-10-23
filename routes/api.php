<?php

use App\Http\Controllers\Api\TelemetryApiController;
use Illuminate\Support\Facades\Route;

// Firmware mengirim tiap 5 detik (12/menit); batas 60/menit per IP menahan banjir permintaan.
Route::post('/telemetry', [TelemetryApiController::class, 'store'])->middleware('throttle:60,1');
Route::get('/telemetry', [TelemetryApiController::class, 'index']);
