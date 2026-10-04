<?php

use App\Http\Controllers\Api\TelemetryApiController;
use Illuminate\Support\Facades\Route;

Route::post('/telemetry', [TelemetryApiController::class, 'store']);
Route::get('/telemetry', [TelemetryApiController::class, 'index']);
