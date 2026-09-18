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
        const trafficChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['10:00', '11:00', '12:00', '13:00', '14:00', '15:00'],
                datasets: [{
                    label: 'Mobil',
                    data: [12, 19, 3, 5, 2, 3],
                    borderColor: 'rgba(54, 162, 235, 1)',
                    tension: 0.1
                }, {
                    label: 'Motor',
                    data: [20, 30, 15, 25, 10, 18],
                    borderColor: 'rgba(255, 206, 86, 1)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    });
</script>
@endsection
