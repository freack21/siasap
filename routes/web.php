<?php

use App\Livewire\AdminOverview;
use App\Livewire\AirQualityGuide;
use App\Livewire\Auth\Login;
use App\Livewire\QosAnalytics;
use App\Livewire\RoomDetail;
use App\Livewire\RoomManagement;
use App\Livewire\SurveyForm;
use App\Livewire\TelemetryDashboard;
use App\Livewire\UserDashboard;
use App\Livewire\UserHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Halaman pengguna (publik, tanpa login): navigasi atas
Route::get('/', UserDashboard::class)->name('home');
Route::get('/ruangan/{room}', RoomDetail::class)->name('rooms.show');
Route::get('/riwayat', UserHistory::class)->name('history');
Route::get('/panduan', AirQualityGuide::class)->name('guide');
Route::get('/kuesioner', SurveyForm::class)->name('survey');

// Autentikasi pengelola
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('home');
})->name('logout')->middleware('auth');

// Panel admin / pengelola: sidebar, hak akses dicek di tiap komponen
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminOverview::class)->name('overview');
    Route::get('/data-sensor', TelemetryDashboard::class)->name('data');
    Route::get('/ruangan', RoomManagement::class)->name('rooms');
    Route::get('/pengujian-jaringan', QosAnalytics::class)->name('qos');
});
