<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CCTVController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\AnalyticsController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CCTVController::class, 'index'])->name('home');
Route::get('/monitoring', [MonitoringController::class, 'index'])->name('monitoring');
Route::get('/api/cctv/stats', [CCTVController::class, 'stats'])->name('cctv.stats');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    Route::post('/analytics/target', [AnalyticsController::class, 'updateTargetCamera'])->name('analytics.updateTarget');
    Route::get('/analytics/data', [AnalyticsController::class, 'getChartData'])->name('analytics.data');
});

require __DIR__.'/auth.php';
