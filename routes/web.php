<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CCTVController;
use App\Http\Controllers\MonitoringController;

Route::get('/', [CCTVController::class, 'index'])->name('home');
Route::get('/monitoring', [MonitoringController::class, 'index'])->name('monitoring');
Route::get('/api/cctv/stats', [CCTVController::class, 'stats'])->name('cctv.stats');
