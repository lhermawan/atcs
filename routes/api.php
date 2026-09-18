<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TrafficLogController;

use App\Models\Setting;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/traffic-logs', [TrafficLogController::class, 'store']);

Route::get('/ai-config', function () {
    $camera = Setting::where('key', 'target_camera')->first();
    return response()->json([
        'target_camera' => $camera ? $camera->value : 'Simpang Kodim Arah Banjar'
    ]);
});
