@extends('layouts.app')

@section('content')
<div class="container mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-slate-800">ATCS Analytics Dashboard</h1>
    </div>

    @if (session('status'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
            <p>{{ session('status') }}</p>
        </div>
    @endif

    <!-- Control Panel -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
        <form action="{{ route('analytics.updateTarget') }}" method="POST" class="flex items-end gap-4">
            @csrf
            <div class="flex-1 max-w-sm">
                <label for="target_camera" class="block text-sm font-medium text-slate-700 mb-1">Target Kamera AI</label>
                <select id="target_camera" name="target_camera" class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                    @forelse($activeCameras as $cam)
                        <option value="{{ $cam }}" {{ $currentTarget == $cam ? 'selected' : '' }}>{{ $cam }}</option>
                    @empty
                        <option value="Simpang Kodim Arah Banjar" {{ $currentTarget == 'Simpang Kodim Arah Banjar' ? 'selected' : '' }}>Simpang Kodim Arah Banjar</option>
                        <option value="Simpang Tonjong" {{ $currentTarget == 'Simpang Tonjong' ? 'selected' : '' }}>Simpang Tonjong</option>
                    @endforelse
                </select>
            </div>
            <button type="submit" class="text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5">
                Pindahkan AI
            </button>
        </form>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Live Video Section -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="bg-slate-900 px-4 py-3 flex justify-between items-center">
                <h2 class="text-white font-semibold flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                    Live AI Stream
                </h2>
                <span class="text-xs text-slate-400">{{ $cameraName }}</span>
            </div>
            <div class="w-full aspect-video bg-black relative flex items-center justify-center">
                @if($streamId)
                    <!-- Gunakan iframe bawaan Ant Media Server agar lebih stabil dan otomatis WebRTC/HLS -->
                    <iframe 
                        src="https://ams.ciamiskab.go.id:5443/live/play.html?name={{ $streamId }}&autoplay=true" 
                        frameborder="0" 
                        allowfullscreen 
                        class="w-full h-full absolute top-0 left-0">
                    </iframe>
                @else
                    <span class="text-slate-500 text-sm">Menunggu Stream AI Aktif...</span>
                @endif
            </div>
        </div>

        <!-- Chart Section -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex flex-col">
            <h2 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path></svg>
                Trafik Kepadatan (Real-Time 30 Menit Terakhir)
            </h2>
            <div class="flex-1 w-full relative min-h-[300px]">
                <canvas id="trafficChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('trafficChart').getContext('2d');
        const trafficChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($labels) !!},
                datasets: [
                    {
                        label: 'Mobil (Per Menit)',
                        data: {!! json_encode($carData) !!},
                        borderColor: '#3b82f6', // Blue
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Motor (Per Menit)',
                        data: {!! json_encode($motorData) !!},
                        borderColor: '#f59e0b', // Amber
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Jumlah Kendaraan Lewat'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Waktu (Jam:Menit)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });

        // Polling Data Real-Time tiap 30 detik
        setInterval(() => {
            fetch('{{ route("analytics.data") }}')
                .then(response => response.json())
                .then(data => {
                    trafficChart.data.labels = data.labels;
                    trafficChart.data.datasets[0].data = data.carData;
                    trafficChart.data.datasets[1].data = data.motorData;
                    trafficChart.update();
                })
                .catch(error => console.error('Error fetching real-time data:', error));
        }, 30000); // 30 detik
    });
</script>
@endsection
