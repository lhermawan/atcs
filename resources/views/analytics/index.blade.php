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
        <form action="{{ route('analytics.updateTarget') }}" method="POST" class="flex flex-wrap items-end gap-4">
            @csrf
            <div class="flex-1 min-w-[200px]">
                <label for="target_camera" class="block text-sm font-medium text-slate-700 mb-1">Target Kamera AI</label>
                <select id="target_camera" name="target_camera" class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                    @forelse($activeCameras as $cam)
                        <option value="{{ $cam }}" {{ $currentTarget == $cam ? 'selected' : '' }}>{{ $cam }}</option>
                    @empty
                        <option value="Simpang Kodim Arah Banjar" {{ $currentTarget == 'Simpang Kodim Arah Banjar' ? 'selected' : '' }}>Simpang Kodim Arah Banjar</option>
                    @endforelse
                </select>
            </div>
            
            <div class="w-full md:w-48">
                <label for="line_position" class="block text-sm font-medium text-slate-700 mb-1">Posisi Garis (10-90%)</label>
                <input type="number" id="line_position" name="line_position" min="10" max="90" value="{{ $linePosition }}" class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
            </div>

            <div class="w-full md:w-48">
                <label for="line_direction" class="block text-sm font-medium text-slate-700 mb-1">Arah Kendaraan Masuk (IN)</label>
                <select id="line_direction" name="line_direction" class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                    <option value="normal" {{ $lineDirection == 'normal' ? 'selected' : '' }}>Dari Atas ke Bawah</option>
                    <option value="swapped" {{ $lineDirection == 'swapped' ? 'selected' : '' }}>Dari Bawah ke Atas</option>
                </select>
            </div>

            <button type="submit" class="text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 mt-4 md:mt-0">
                Simpan & Restart AI
            </button>
        </form>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Live Video Section -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden flex flex-col">
            <div class="p-4 bg-slate-800 text-white flex justify-between items-center">
                <h2 class="text-lg font-semibold flex items-center gap-2">
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
                Trafik Kendaraan (30 Menit Terakhir)
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
                        label: 'Mobil Masuk (IN)',
                        data: {!! json_encode($carIn) !!},
                        borderColor: '#2563eb', // Blue
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        tension: 0.3
                    },
                    {
                        label: 'Mobil Keluar (OUT)',
                        data: {!! json_encode($carOut) !!},
                        borderColor: '#93c5fd', // Light Blue
                        borderDash: [5, 5],
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        tension: 0.3
                    },
                    {
                        label: 'Motor Masuk (IN)',
                        data: {!! json_encode($motorIn) !!},
                        borderColor: '#d97706', // Amber
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        tension: 0.3
                    },
                    {
                        label: 'Motor Keluar (OUT)',
                        data: {!! json_encode($motorOut) !!},
                        borderColor: '#fcd34d', // Light Amber
                        borderDash: [5, 5],
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        tension: 0.3
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
                            text: 'Volume per Menit'
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
                        labels: {
                            usePointStyle: true,
                            boxWidth: 8
                        }
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
                    trafficChart.data.datasets[0].data = data.carIn;
                    trafficChart.data.datasets[1].data = data.carOut;
                    trafficChart.data.datasets[2].data = data.motorIn;
                    trafficChart.data.datasets[3].data = data.motorOut;
                    trafficChart.update();
                })
                .catch(error => console.error('Error fetching real-time data:', error));
        }, 30000); // 30 detik
    });
</script>
@endsection
