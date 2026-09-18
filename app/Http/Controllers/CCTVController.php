<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Client\RequestException;

class CCTVController extends Controller
{
    /**
     * Helper to fetch CCTV data with Cache
     */
    private function fetchCctvData($cacheSeconds = 30)
    {
        return Cache::remember('cctv_list', $cacheSeconds, function () {
            try {
                $response = Http::withoutVerifying()->timeout(10)->get('https://ams.ciamiskab.go.id:5443/LiveApp/rest/v2/broadcasts/list/0/50');
                $data = $response->json();

                if (is_array($data) && isset($data[0]['streamId'])) {
                    return collect($data)->sortByDesc('hlsViewerCount')->values()->toArray();
                }
            } catch (\Exception $e) {
                \Log::error('Gagal mengambil data CCTV: ' . $e->getMessage());
            }

            return null;
        });
    }

    public function index()
    {
        $errorMessage = null;
        
        // Cache data for 30 seconds to prevent overloading AMS
        $cctvs = $this->fetchCctvData(30);

        if (!$cctvs) {
            $cctvs = [];
            $errorMessage = 'Mohon maaf, saat ini layanan CCTV tidak tersedia atau tidak dapat diakses.';
        }

        return view('cctv.index', compact('cctvs', 'errorMessage'));
    }

    /**
     * Endpoint for frontend to poll stats (viewers, status)
     * Cached for 10 seconds to reduce load
     */
    public function stats()
    {
        // Using a shorter cache for stats to get near real-time viewers
        $cctvs = Cache::remember('cctv_stats', 10, function () {
            try {
                $response = Http::withoutVerifying()->timeout(10)->get('https://ams.ciamiskab.go.id:5443/LiveApp/rest/v2/broadcasts/list/0/50');
                return $response->json();
            } catch (\Exception $e) {
                return null;
            }
        });

        if (!is_array($cctvs)) {
            return response()->json(['error' => 'Data unavailable'], 503);
        }

        // Map to a lighter format for frontend
        $stats = collect($cctvs)->mapWithKeys(function ($item) {
            return [
                $item['streamId'] ?? '' => [
                    'viewers' => $item['hlsViewerCount'] ?? 0,
                    'status' => $item['status'] ?? 'offline'
                ]
            ];
        })->filter(function ($value, $key) {
            return $key !== '';
        });

        return response()->json($stats);
    }
}
