<?php

use App\Http\Controllers\Api\TelemetryApiController;
use App\Http\Controllers\Api\TopologyTestController;
use Illuminate\Support\Facades\Route;

// Firmware mengirim tiap 5 detik (12/menit); batas 60/menit per IP menahan banjir permintaan.
Route::post('/telemetry', [TelemetryApiController::class, 'store'])->middleware('throttle:60,1');
Route::get('/telemetry', [TelemetryApiController::class, 'index']);

// Endpoint uji topologi star/mesh (tersembunyi). Gateway mesh meneruskan data tiga perangkat
// dari satu IP (36/menit), sehingga batasnya lebih longgar.
Route::post('/'.config('topology_test.path'), [TopologyTestController::class, 'store'])->middleware('throttle:120,1');
