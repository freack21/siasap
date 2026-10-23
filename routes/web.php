<?php

use App\Livewire\AirQualityGuide;
use App\Livewire\RoomDetail;
use App\Livewire\SurveyForm;
use App\Livewire\UserDashboard;
use App\Livewire\UserHistory;
use Illuminate\Support\Facades\Route;

// Halaman pengguna (publik, tanpa login): navigasi atas
Route::get('/', UserDashboard::class)->name('home');
Route::get('/ruangan/{room}', RoomDetail::class)->name('rooms.show');
Route::get('/riwayat', UserHistory::class)->name('history');
Route::get('/panduan', AirQualityGuide::class)->name('guide');
Route::get('/kuesioner', SurveyForm::class)->name('survey');
