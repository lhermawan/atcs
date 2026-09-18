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

        // Ambil 30 data terakhir untuk render pertama kali
        $logs = TrafficLog::where('camera_name', $currentTarget)
            ->latest()
            ->take(30)
            ->get()
            ->reverse()
            ->values();

        $labels = [];
        $carData = [];
        $motorData = [];

        foreach ($logs as $log) {
            $labels[] = \Carbon\Carbon::parse($log->created_at)->format('H:i');
            $carData[] = $log->car_count;
            $motorData[] = $log->motorcycle_count;
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

        $targetCameraSetting = \App\Models\Setting::where('key', 'target_camera')->first();
        $currentTarget = $targetCameraSetting ? $targetCameraSetting->value : 'Simpang Kodim Arah Banjar';

        return view('analytics.index', compact('labels', 'carData', 'motorData', 'cameraName', 'streamId', 'activeCameras', 'currentTarget'));
    }

    public function updateTargetCamera(Request $request)
    {
        $request->validate([
            'target_camera' => 'required|string'
        ]);

        \App\Models\Setting::updateOrCreate(
            ['key' => 'target_camera'],
            ['value' => $request->target_camera]
        );

        return back()->with('status', 'Kamera target AI berhasil diubah. AI akan otomatis me-restart proses dalam 10-30 detik.');
    }

    public function getChartData()
    {
        $targetCameraSetting = \App\Models\Setting::where('key', 'target_camera')->first();
        $currentTarget = $targetCameraSetting ? $targetCameraSetting->value : 'Simpang Kodim Arah Banjar';

        // Ambil 30 data terakhir (30 menit terakhir karena AI mengirim tiap 1 menit)
        $logs = TrafficLog::where('camera_name', $currentTarget)
            ->latest()
            ->take(30)
            ->get()
            ->reverse()
            ->values();

        $labels = [];
        $carData = [];
        $motorData = [];

        foreach ($logs as $log) {
            $labels[] = \Carbon\Carbon::parse($log->created_at)->format('H:i');
            $carData[] = $log->car_count;
            $motorData[] = $log->motorcycle_count;
        }

        return response()->json([
            'labels' => $labels,
            'carData' => $carData,
            'motorData' => $motorData
        ]);
    }
}
