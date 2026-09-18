<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\TrafficLog;

class AnalyticsController extends Controller
{
    public function index()
    {
        // Ambil data hari ini, urutkan dari pagi ke malam
        $todayLogs = TrafficLog::whereDate('created_at', today())
            ->orderBy('created_at')
            ->get();

        // Kelompokkan rata-rata kepadatan berdasarkan Jam
        $hourlyData = $todayLogs->groupBy(function ($log) {
            return \Carbon\Carbon::parse($log->created_at)->format('H:00');
        });

        $labels = [];
        $carData = [];
        $motorData = [];

        foreach ($hourlyData as $hour => $logs) {
            $labels[] = $hour;
            $carData[] = round($logs->avg('car_count'));
            $motorData[] = round($logs->avg('motorcycle_count'));
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
            $response = \Illuminate\Support\Facades\Http::withOptions(['verify' => false])->timeout(5)->get('http://127.0.0.1:5080/LiveApp/rest/v2/broadcasts/list/0/50');
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
}
