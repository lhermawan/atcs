<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class MonitoringController extends Controller
{
    private function fetchCctvData($cacheSeconds = 30)
    {
        return Cache::remember('cctv_list', $cacheSeconds, function () {
            try {
                $response = Http::withoutVerifying()->timeout(10)->get('https://ams.ciamiskab.go.id:5443/LiveApp/rest/v2/broadcasts/list/0/50');
                $data = $response->json();

                if (is_array($data) && isset($data[0]['streamId'])) {
                    // Only get active broadcasting cameras for monitoring grid, or return all
                    // But usually, users only want to monitor live cameras on the grid.
                    return collect($data)
                        // ->where('status', 'broadcasting') // Un-comment if you only want live streams
                        ->sortByDesc('hlsViewerCount')->values()->toArray();
                }
            } catch (\Exception $e) {
                \Log::error('Gagal mengambil data CCTV (Monitoring): ' . $e->getMessage());
            }
            return null;
        });
    }

    public function index()
    {
        $cctvs = $this->fetchCctvData(30);
        $errorMessage = null;
        $activeCctvs = [];

        if (!$cctvs) {
            $cctvs = [];
            $errorMessage = 'Mohon maaf, saat ini layanan CCTV tidak tersedia atau tidak dapat diakses.';
        } else {
            // Filter only broadcasting cameras for initial grid load
            $activeCctvs = collect($cctvs)->where('status', 'broadcasting')->values()->toArray();
        }

        return view('monitoring.index', compact('cctvs', 'activeCctvs', 'errorMessage'));
    }
}
