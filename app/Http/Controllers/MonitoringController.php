<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MonitoringController extends Controller
{
    public function index()
    {
        $cctvs = [];
        $errorMessage = null;

        try {
            $response = Http::timeout(5)->get('https://ams.ciamiskab.go.id:5443/LiveApp/rest/v2/broadcasts/list/0/50');

            $data = $response->json();

            if (is_array($data) && isset($data[0]['streamId'])) {
                $cctvs = collect($data)->sortByDesc('hlsViewerCount')->values();
            } else {
                $errorMessage = 'Mohon maaf, layanan monitoring CCTV sedang tidak tersedia.';
            }
        } catch (\Exception $e) {
            \Log::error('Gagal mengambil data CCTV (MonitoringController): ' . $e->getMessage());
            $errorMessage = 'Mohon maaf, layanan monitoring CCTV tidak dapat diakses.';
        }

        return view('cctv.monitoring', compact('cctvs', 'errorMessage'));
    }
}
