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
    $camera = App\Models\Setting::where('key', 'target_camera')->first();
    $linePos = App\Models\Setting::where('key', 'line_position')->first();
    $lineDir = App\Models\Setting::where('key', 'line_direction')->first();

    return response()->json([
        'target_camera' => $camera ? $camera->value : 'Simpang Kodim Arah Banjar',
        'line_position' => $linePos ? (int)$linePos->value : 60,
        'line_direction' => $lineDir ? $lineDir->value : 'normal'
    ]);
});
