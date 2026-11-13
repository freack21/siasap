<?php

use App\Console\Commands\ExportRoomTestCommand;
use App\Http\Controllers\RoomTestExportController;
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

// Latihan sidang skripsi: tidak ditautkan dari menu, alamat diatur LATIHAN_SIDANG_PATH.
Route::get('/'.config('latihan_sidang.path'), fn () => response()
    ->view('latihan-sidang')
    ->header('X-Robots-Tag', 'noindex, nofollow'))
    ->name('latihan-sidang');

// Hasil ekspor pengujian kondisi ruangan: tidak ditautkan dari menu, alamat diatur UJI_RUANGAN_PATH.
Route::get('/'.config('uji_ruangan.path'), [RoomTestExportController::class, 'index'])->name('uji-ruangan.index');
Route::get('/'.config('uji_ruangan.path').'/{date}/{file}', [RoomTestExportController::class, 'download'])
    ->where(['date' => '\d{4}-\d{2}-\d{2}', 'file' => implode('|', array_map('preg_quote', ExportRoomTestCommand::FILES))])
    ->name('uji-ruangan.download');
