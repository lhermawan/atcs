<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;


use Illuminate\Http\Client\RequestException;

class CCTVController extends Controller
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
                $errorMessage = 'Mohon maaf, saat ini layanan CCTV tidak tersedia.';
            }
        } catch (\Exception $e) {
            // Catat log jika ingin
            \Log::error('Gagal mengambil data CCTV: ' . $e->getMessage());

            $errorMessage = 'Mohon maaf, saat ini layanan CCTV tidak dapat diakses.';
        }

        return view('cctv.index', compact('cctvs', 'errorMessage'));
    }

}
