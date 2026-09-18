@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-4 h-full min-h-[calc(100vh-8rem)] relative z-10">
    <!-- Header Control Panel -->
    <div class="bg-white/70 backdrop-blur-md p-4 rounded-xl shadow-sm border border-slate-200/50 flex flex-wrap justify-between items-center gap-4">
        <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
            <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
            Command Center Grid
        </h2>
        
        <div class="flex items-center gap-2">
            <span class="text-sm text-slate-500 font-medium mr-2">Pilih Layout:</span>
            <button onclick="changeLayout(1)" class="layout-btn px-3 py-1.5 text-sm font-semibold rounded-md border border-slate-300 hover:bg-indigo-50 transition-colors" data-grid="1">1x1</button>
            <button onclick="changeLayout(4)" class="layout-btn px-3 py-1.5 text-sm font-semibold rounded-md border border-slate-300 hover:bg-indigo-50 transition-colors" data-grid="4">2x2</button>
            <button onclick="changeLayout(9)" class="layout-btn px-3 py-1.5 text-sm font-semibold rounded-md border border-indigo-500 bg-indigo-50 text-indigo-600 transition-colors" data-grid="9">3x3</button>
            <button onclick="changeLayout(16)" class="layout-btn px-3 py-1.5 text-sm font-semibold rounded-md border border-slate-300 hover:bg-indigo-50 transition-colors" data-grid="16">4x4</button>
            <button onclick="changeLayout(25)" class="layout-btn px-3 py-1.5 text-sm font-semibold rounded-md border border-slate-300 hover:bg-indigo-50 transition-colors" data-grid="25">5x5</button>
        </div>
    </div>

    <!-- Grid Container -->
    <div id="video-grid" class="grid grid-cols-3 gap-2 flex-grow bg-slate-900/80 backdrop-blur-sm p-2 rounded-xl border border-slate-800 shadow-2xl ring-1 ring-white/5">
        @for ($i = 0; $i < 25; $i++)
        <div class="video-slot group relative bg-black rounded-lg overflow-hidden border border-slate-700/50 hover:border-indigo-500/50 transition-colors duration-300 flex flex-col {{ $i >= 9 ? 'hidden' : '' }}" data-index="{{ $i }}">
            <!-- Camera Selector Overlay -->
            <div class="absolute top-2 left-2 right-12 z-10 flex justify-between items-start opacity-0 group-hover:opacity-100 focus-within:opacity-100 transition-opacity">
                <select class="camera-select bg-black/70 text-white text-xs py-1 px-2 rounded border border-slate-600 focus:outline-none focus:border-indigo-500 max-w-full backdrop-blur-md" onchange="loadCamera(this, {{ $i }})">
                    <option value="">-- Pilih Kamera --</option>
                    @foreach ($cctvs as $index => $cctv)
                        <option value="https://ams.ciamiskab.go.id:5443/LiveApp/streams/{{ $cctv['streamId'] }}.m3u8" 
                            {{ (isset($activeCctvs[$i]) && $activeCctvs[$i]['streamId'] === $cctv['streamId']) ? 'selected' : '' }}
                            data-name="{{ $cctv['name'] }}">
                            {{ $cctv['name'] }} ({{ $cctv['status'] == 'broadcasting' ? 'Live' : 'Offline' }})
                        </option>
                    @endforeach
                </select>
            </div>
            
            <!-- Expand Button Overlay -->
            <div class="absolute top-2 right-2 z-10 opacity-0 group-hover:opacity-100 transition-opacity">
                <button onclick="openModal({{ $i }})" class="bg-black/70 text-white p-1 rounded border border-slate-600 hover:border-indigo-500 hover:text-indigo-400 backdrop-blur-md transition-colors" title="Perbesar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path></svg>
                </button>
            </div>
            
            <div class="video-container flex-grow relative w-full h-full aspect-video">
                <video
                    id="grid-video-{{ $i }}"
                    class="video-js vjs-default-skin vjs-fluid w-full h-full"
                    controls
                    preload="auto"
                    autoplay
                    muted
                    data-setup='{"autoplay": true, "fluid": true, "controlBar": {"fullscreenToggle": true, "pictureInPictureToggle": false}}'
                >
                    @if(isset($activeCctvs[$i]))
                        <source src="https://ams.ciamiskab.go.id:5443/LiveApp/streams/{{ $activeCctvs[$i]['streamId'] }}.m3u8" type="application/x-mpegURL" />
                    @endif
                </video>
            </div>
        </div>
        @endfor
    </div>
</div>

<!-- Video Modal -->
<div id="video-modal" class="fixed inset-0 z-[60] bg-black/90 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="relative w-full max-w-5xl bg-slate-900 rounded-xl overflow-hidden shadow-2xl border border-slate-700">
        <!-- Header Modal -->
        <div class="flex justify-between items-center p-4 border-b border-slate-800 bg-slate-900/50">
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span id="modal-title-text">CCTV Live</span>
            </h3>
            <button onclick="closeModal()" class="text-slate-400 hover:text-white transition-colors p-1 rounded hover:bg-slate-800">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <!-- Player Modal -->
        <div class="aspect-video bg-black relative">
            <video id="modal-video-player" class="video-js vjs-default-skin vjs-fluid w-full h-full" controls preload="none" data-setup='{"fluid": true}'></video>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<style>
    /* Compact videojs controls for grid */
    .video-slot .video-js .vjs-control-bar { opacity: 0; transition: opacity 0.3s; }
    .video-slot:hover .video-js .vjs-control-bar { opacity: 1; }
    .video-slot .vjs-big-play-button { display: none !important; }
    
    /* Sembunyikan pesan error bawaan video.js yang mengganggu */
    .video-js .vjs-error-display,
    .video-js .vjs-modal-dialog { 
        display: none !important; 
    }
</style>

<script>
    // Initialize players array to store video.js instances
    const players = [];
    let modalPlayer = null;
    
    document.addEventListener('DOMContentLoaded', function () {
        // Initialize all 25 video players
        for(let i = 0; i < 25; i++) {
            players[i] = videojs('grid-video-' + i);
        }
        
        // Initialize modal player
        modalPlayer = videojs('modal-video-player');
        
        // Initial layout set to 9 (3x3)
        changeLayout(9);
        
        // Initial filter for dropdowns
        updateDropdownOptions();
        
        // Close modal when clicking on backdrop
        document.getElementById('video-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    });

    function openModal(index) {
        const player = players[index];
        const src = player.currentSrc();
        if (!src) return; // Ignore if no camera selected
        
        // Get title
        const select = document.querySelector(`.video-slot[data-index="${index}"] .camera-select`);
        const option = select.options[select.selectedIndex];
        const title = option ? option.getAttribute('data-name') : 'Kamera CCTV';
        document.getElementById('modal-title-text').innerText = title || 'Kamera CCTV';
        
        const modal = document.getElementById('video-modal');
        modal.classList.remove('hidden');
        
        modalPlayer.src({ type: 'application/x-mpegURL', src: src });
        modalPlayer.play().catch(e => console.log('Autoplay prevented'));
    }

    function closeModal() {
        const modal = document.getElementById('video-modal');
        modal.classList.add('hidden');
        modalPlayer.pause();
        modalPlayer.removeAttribute('src');
        modalPlayer.load();
    }

    function updateDropdownOptions() {
        const selects = document.querySelectorAll('.camera-select');
        const selectedValues = new Set();
        
        // Kumpulkan semua URL/value kamera yang sedang dipilih, TETAPI hanya dari slot yang aktif/terlihat!
        selects.forEach(select => {
            const slot = select.closest('.video-slot');
            // Cek apakah slot tidak disembunyikan
            if (slot && !slot.classList.contains('hidden') && select.value) {
                selectedValues.add(select.value);
            }
        });
        
        // Sembunyikan opsi yang sudah dipilih di slot lain
        selects.forEach(select => {
            Array.from(select.options).forEach(option => {
                if (!option.value) return; // Abaikan placeholder "-- Pilih Kamera --"
                
                if (selectedValues.has(option.value) && select.value !== option.value) {
                    option.style.display = 'none'; // Sembunyikan
                    option.disabled = true;        // Matikan agar tidak bisa dipilih via keyboard
                } else {
                    option.style.display = '';     // Tampilkan kembali
                    option.disabled = false;
                }
            });
        });
    }

    function changeLayout(count) {
        const grid = document.getElementById('video-grid');
        const slots = document.querySelectorAll('.video-slot');
        const btns = document.querySelectorAll('.layout-btn');
        
        // Update Buttons
        btns.forEach(btn => {
            if(parseInt(btn.getAttribute('data-grid')) === count) {
                btn.className = "layout-btn px-3 py-1.5 text-sm font-semibold rounded-md border border-indigo-500 bg-indigo-50 text-indigo-600 transition-colors";
            } else {
                btn.className = "layout-btn px-3 py-1.5 text-sm font-semibold rounded-md border border-slate-300 hover:bg-indigo-50 text-slate-700 transition-colors";
            }
        });

        // Change Grid Columns
        grid.className = "grid flex-grow bg-slate-900/80 backdrop-blur-sm p-2 rounded-xl border border-slate-800 shadow-2xl ring-1 ring-white/5";
        
        if(count === 1) grid.classList.add('grid-cols-1', 'gap-4');
        if(count === 4) grid.classList.add('grid-cols-2', 'gap-3');
        if(count === 9) grid.classList.add('grid-cols-3', 'gap-2');
        if(count === 16) grid.classList.add('grid-cols-4', 'gap-1');
        if(count === 25) grid.classList.add('grid-cols-5', 'gap-1');

        // Show/Hide Slots and manage players
        slots.forEach((slot, index) => {
            if (index < count) {
                slot.classList.remove('hidden');
                // Auto play if it has source and was paused
                if(players[index].currentSrc() && players[index].paused()) {
                    players[index].play().catch(e => console.log('Autoplay prevented'));
                }
            } else {
                slot.classList.add('hidden');
                // Pause player to save bandwidth
                if(!players[index].paused()) {
                    players[index].pause();
                }
            }
        });
        
        // Update dropdown lagi (opsional, jika slot tersembunyi kita biarkan kamera terpakai)
        updateDropdownOptions();
    }

    function loadCamera(selectObj, index) {
        const url = selectObj.value;
        const player = players[index];
        
        if (url) {
            player.src({ type: 'application/x-mpegURL', src: url });
            player.play().catch(e => console.log('Autoplay prevented'));
        } else {
            player.pause();
            player.removeAttribute('src');
            player.load();
        }
        
        // Refresh filter dropdown setiap kali ada pergantian kamera
        updateDropdownOptions();
    }
</script>
@endpush