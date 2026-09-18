@extends('layouts.app')

@section('content')
<div class="container mx-auto">
    <h1 class="text-3xl font-bold mb-6 text-slate-800">ATCS Analytics Dashboard</h1>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Live Video Section -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="bg-slate-900 px-4 py-3 flex justify-between items-center">
                <h2 class="text-white font-semibold flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                    Live AI Stream
                </h2>
                <span class="text-xs text-slate-400">Simpang Kodim Arah Banjar</span>
            </div>
            <div class="w-full aspect-video bg-black relative">
                <!-- Using HLS equivalent or default video source since RTMP directly via HTML5 requires specific setups, assuming AMS provides HLS -->
                <video
                    id="live-ai-video"
                    class="video-js vjs-default-skin vjs-16-9"
                    controls
                    autoplay
                    muted
                    preload="auto"
                    data-setup='{"fluid": true}'
                >
                    <source src="https://ams.ciamiskab.go.id:5443/live/streams/Simpang_Kodim_Arah_Banjar_ai.m3u8" type="application/x-mpegURL">
                    <p class="vjs-no-js">
                        To view this video please enable JavaScript, and consider upgrading to a web browser that supports HTML5 video
                    </p>
                </video>
            </div>
        </div>

        <!-- Chart Section -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex flex-col">
            <h2 class="text-lg font-semibold mb-4 text-slate-800">Trafik Kepadatan (Hari Ini)</h2>
            <div class="flex-1 w-full relative min-h-[300px]">
                <canvas id="trafficChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('trafficChart').getContext('2d');
        
        // Data dari Database Laravel
        const labels = @json($labels);
        const carData = @json($carData);
        const motorData = @json($motorData);

        const trafficChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels.length > 0 ? labels : ['Belum Ada Data'],
                datasets: [{
                    label: 'Rata-rata Mobil per Menit',
                    data: carData.length > 0 ? carData : [0],
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    fill: true,
                    tension: 0.3
                }, {
                    label: 'Rata-rata Motor per Menit',
                    data: motorData.length > 0 ? motorData : [0],
                    borderColor: 'rgba(255, 206, 86, 1)',
                    backgroundColor: 'rgba(255, 206, 86, 0.2)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Jumlah Kendaraan' }
                    },
                    x: {
                        title: { display: true, text: 'Jam' }
                    }
                }
            }
        });
    });
</script>
@endsection
