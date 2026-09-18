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
            // Replikasi logika Python: clean_name = camera_name.replace(" ", "_").replace("-", "_")
            $cleanName = str_replace([' ', '-'], '_', $latestLog->camera_name);
            $streamId = $cleanName . '_ai';
        }

        return view('analytics.index', compact('labels', 'carData', 'motorData', 'cameraName', 'streamId'));
    }
}
