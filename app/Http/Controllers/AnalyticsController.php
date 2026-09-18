<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\TrafficLog;

class AnalyticsController extends Controller
{
    public function index()
    {
        $targetCameraSetting = \App\Models\Setting::where('key', 'target_camera')->first();
        $currentTarget = $targetCameraSetting ? $targetCameraSetting->value : 'Simpang Kodim Arah Banjar';

        $linePositionSetting = \App\Models\Setting::where('key', 'line_position')->first();
        $linePosition = $linePositionSetting ? $linePositionSetting->value : 60;

        $lineDirectionSetting = \App\Models\Setting::where('key', 'line_direction')->first();
        $lineDirection = $lineDirectionSetting ? $lineDirectionSetting->value : 'normal';

        // Ambil 30 data terakhir untuk render pertama kali
        $logs = TrafficLog::where('camera_name', $currentTarget)
            ->latest()
            ->take(30)
            ->get()
            ->reverse()
            ->values();

        $labels = [];
        $carIn = [];
        $carOut = [];
        $motorIn = [];
        $motorOut = [];

        foreach ($logs as $log) {
            $labels[] = \Carbon\Carbon::parse($log->created_at)->format('H:i');
            $carIn[] = $log->car_in ?? 0;
            $carOut[] = $log->car_out ?? 0;
            $motorIn[] = $log->motorcycle_in ?? 0;
            $motorOut[] = $log->motorcycle_out ?? 0;
        }

        // Ambil kamera terakhir yang sedang diproses oleh AI
        $latestLog = TrafficLog::latest()->first();
        $cameraName = $latestLog ? $latestLog->camera_name : 'Menunggu Data AI...';
        
        $streamId = '';
        if ($latestLog) {
            $cleanName = str_replace([' ', '-'], '_', $latestLog->camera_name);
            $streamId = $cleanName . '_ai';
        }

        // Ambil daftar kamera dari Ant Media Server untuk dropdown
        $activeCameras = [];
        try {
            // Karena Laravel jalan di cPanel, harus panggil AMS lewat IP/Domain publik
            $response = \Illuminate\Support\Facades\Http::withOptions(['verify' => false])->timeout(5)->get('https://ams.ciamiskab.go.id:5443/LiveApp/rest/v2/broadcasts/list/0/50');
            if ($response->successful()) {
                foreach ($response->json() as $cctv) {
                    // Hanya ambil stream CCTV asli (abaikan stream AI yang berakhiran _ai)
                    if ($cctv['status'] === 'broadcasting' && !str_ends_with($cctv['streamId'], '_ai')) {
                        $activeCameras[] = $cctv['name'];
                    }
                }
            }
        } catch (\Exception $e) {
            // Abaikan jika AMS tidak jalan di lokal
        }

        return view('analytics.index', compact(
            'labels', 'carIn', 'carOut', 'motorIn', 'motorOut', 
            'cameraName', 'streamId', 'activeCameras', 'currentTarget',
            'linePosition', 'lineDirection'
        ));
    }

    public function updateTargetCamera(Request $request)
    {
        $request->validate([
            'target_camera' => 'required|string',
            'line_position' => 'required|numeric|min:10|max:90',
            'line_direction' => 'required|in:normal,swapped'
        ]);

        \App\Models\Setting::updateOrCreate(['key' => 'target_camera'], ['value' => $request->target_camera]);
        \App\Models\Setting::updateOrCreate(['key' => 'line_position'], ['value' => $request->line_position]);
        \App\Models\Setting::updateOrCreate(['key' => 'line_direction'], ['value' => $request->line_direction]);

        return back()->with('status', 'Pengaturan AI berhasil diubah. AI akan otomatis memproses ulang dalam 20 detik.');
    }

    public function getChartData()
    {
        $targetCameraSetting = \App\Models\Setting::where('key', 'target_camera')->first();
        $currentTarget = $targetCameraSetting ? $targetCameraSetting->value : 'Simpang Kodim Arah Banjar';

        $logs = TrafficLog::where('camera_name', $currentTarget)
            ->latest()
            ->take(30)
            ->get()
            ->reverse()
            ->values();

        $labels = [];
        $carIn = [];
        $carOut = [];
        $motorIn = [];
        $motorOut = [];

        foreach ($logs as $log) {
            $labels[] = \Carbon\Carbon::parse($log->created_at)->format('H:i');
            $carIn[] = $log->car_in ?? 0;
            $carOut[] = $log->car_out ?? 0;
            $motorIn[] = $log->motorcycle_in ?? 0;
            $motorOut[] = $log->motorcycle_out ?? 0;
        }

        return response()->json([
            'labels' => $labels,
            'carIn' => $carIn,
            'carOut' => $carOut,
            'motorIn' => $motorIn,
            'motorOut' => $motorOut
        ]);
    }
}
