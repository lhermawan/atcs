@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6 text-gray-800">ATCS Analytics Dashboard</h1>
    
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold mb-4">Live Traffic Data (Mobil & Motor)</h2>
        <canvas id="trafficChart" width="400" height="150"></canvas>
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
