<?php

use App\Livewire\AirQualityGuide;
use App\Livewire\DeviceStatus;
use App\Livewire\RecommendationPage;
use App\Livewire\RoomDetail;
use App\Livewire\SurveyForm;
use App\Livewire\TopologyTest;
use App\Livewire\UserDashboard;
use App\Livewire\UserHistory;
use Illuminate\Support\Facades\Route;

// Halaman pengguna (publik, tanpa login): navigasi atas
Route::get('/', UserDashboard::class)->name('home');
Route::get('/ruangan/{room}', RoomDetail::class)->name('rooms.show');
Route::get('/riwayat', UserHistory::class)->name('history');
Route::get('/rekomendasi', RecommendationPage::class)->name('recommendations');
Route::get('/perangkat', DeviceStatus::class)->name('devices');
Route::get('/panduan', AirQualityGuide::class)->name('guide');
Route::get('/kuesioner', SurveyForm::class)->name('survey');

// Halaman uji topologi star/mesh: tidak ditautkan dari menu, alamat diatur TOPOLOGY_TEST_PATH.
Route::get('/'.config('topology_test.path'), TopologyTest::class)->name('topology-test');
