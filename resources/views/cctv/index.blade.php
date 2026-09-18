@extends('layouts.app')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Video Section -->
    <div class="lg:col-span-2 flex flex-col gap-4">
        
        <!-- Banner Teks Dinamis -->
        <div class="mb-2">
            <h2 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 mb-1">
                Live Monitoring ATCS
            </h2>
            <p class="text-slate-500 text-sm font-medium">Pantau arus lalu lintas Kabupaten Ciamis secara real-time.</p>
        </div>

        @if (!empty($cctvs) && isset($cctvs[0]))
        <div class="relative bg-black rounded-xl overflow-hidden shadow-2xl ring-1 ring-white/10 group aspect-video">
            <!-- Efek Glow di belakang video -->
            <div class="absolute -inset-1 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl blur opacity-25 group-hover:opacity-40 transition duration-1000 group-hover:duration-200 pointer-events-none"></div>
            
            <video
                id="main-video"
                class="video-js vjs-default-skin vjs-big-play-centered w-full h-full relative z-10"
                controls
                preload="auto"
                autoplay
                muted
                poster="{{ asset('img/CCTV-tower.jpg') }}"
                data-setup='{"autoplay": true, "fluid": true}'
            >
                <source src="https://ams.ciamiskab.go.id:5443/LiveApp/streams/{{ $cctvs[0]['streamId'] }}.m3u8" type="application/x-mpegURL" />
            </video>
        </div>
        
        <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
            <div class="flex justify-between items-start">
                <div>
                    <h2 id="video-title" class="text-2xl font-bold text-slate-800 mb-2">
                        {{ $cctvs[0]['name'] }}
                    </h2>
                    <div class="flex items-center gap-3 text-sm text-slate-500 font-medium">
                        <span id="main-status-badge" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-red-50 text-red-600">
                            <span class="relative flex h-2 w-2">
                              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                              <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                            </span>
                            LIVE
                        </span>
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            <span id="viewer-count-main">{{ $cctvs[0]['hlsViewerCount'] }}</span> Viewers
                        </span>
                    </div>
                </div>
            </div>
        </div>
        @else
        <div class="rounded-xl overflow-hidden shadow-lg bg-slate-900 aspect-video flex flex-col items-center justify-center text-slate-400">
            <svg class="w-16 h-16 mb-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
            <p class="text-lg font-semibold">{{ $errorMessage ?? 'Tidak ada siaran CCTV aktif saat ini.' }}</p>
        </div>
        @endif
    </div>

    <!-- Sidebar List -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 h-[calc(100vh-8rem)] flex flex-col sticky top-24">
            <div class="p-4 border-b border-slate-200 bg-slate-50 rounded-t-xl">
                <h3 class="font-bold text-slate-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                    Daftar Kamera CCTV
                </h3>
            </div>
            
            <div class="p-4 overflow-y-auto flex-grow flex flex-col gap-3 custom-scrollbar">
                @forelse ($cctvs as $cctv)
                <button 
                    type="button"
                    class="cctv-card group flex gap-3 p-2.5 rounded-lg border text-left transition-all duration-300 hover:-translate-y-1 hover:shadow-lg {{ $loop->first ? 'border-indigo-500 bg-gradient-to-r from-indigo-50/80 to-purple-50/80 ring-1 ring-indigo-500 shadow-md' : 'border-slate-200/60 hover:border-indigo-300 bg-white/50 backdrop-blur-sm' }}"
                    data-video-url="https://ams.ciamiskab.go.id:5443/LiveApp/streams/{{ $cctv['streamId'] }}.m3u8"
                    data-stream-id="{{ $cctv['streamId'] }}"
                >
                    <div class="relative w-24 h-16 rounded-md overflow-hidden flex-shrink-0 bg-slate-900">
                        <img src="{{ asset('img/CCTV-tower.jpg') }}" alt="Thumb" class="w-full h-full object-cover opacity-80 group-hover:opacity-100 transition-opacity">
                        <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                            <svg class="w-8 h-8 text-white drop-shadow-md" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                        </div>
                    </div>
                    <div class="flex flex-col justify-center overflow-hidden w-full">
                        <h4 class="font-semibold text-sm text-slate-800 truncate cctv-title group-hover:text-indigo-600 transition-colors">
                            {{ $cctv['name'] }}
                        </h4>
                        <div class="flex items-center justify-between mt-1">
                            @if(($cctv['status'] ?? '') === 'broadcasting')
                                <span class="text-xs font-medium text-emerald-600 flex items-center gap-1 cctv-badge" data-id="{{ $cctv['streamId'] }}">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Live
                                </span>
                            @else
                                <span class="text-xs font-medium text-slate-500 flex items-center gap-1 cctv-badge" data-id="{{ $cctv['streamId'] }}">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Offline
                                </span>
                            @endif
                            <span class="text-xs text-slate-500 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                <span class="viewer-count" data-id="{{ $cctv['streamId'] }}">{{ $cctv['hlsViewerCount'] }}</span>
                            </span>
                        </div>
                    </div>
                </button>
                @empty
                <div class="text-center py-10 px-4 text-slate-500">
                    <p class="text-sm">Belum ada CCTV yang tersedia.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<style>
    /* Custom Scrollbar for Sidebar */
    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
    .dark .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #475569; }

    /* Sembunyikan pesan error bawaan video.js yang mengganggu */
    .video-js .vjs-error-display,
    .video-js .vjs-modal-dialog { 
        display: none !important; 
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    @if (!empty($cctvs) && isset($cctvs[0]))
    const player = videojs('main-video');
    let activeStreamId = "{{ $cctvs[0]['streamId'] }}";

    // Ganti video saat klik list CCTV
    document.querySelectorAll('.cctv-card').forEach(card => {
        card.addEventListener('click', function() {
            // Hapus style active dari semua card
            document.querySelectorAll('.cctv-card').forEach(c => {
                c.classList.remove('border-indigo-500', 'bg-gradient-to-r', 'from-indigo-50/80', 'to-purple-50/80', 'dark:from-indigo-900/30', 'dark:to-purple-900/30', 'ring-1', 'ring-indigo-500', 'shadow-md');
                c.classList.add('border-slate-200/60', 'dark:border-slate-700/60', 'bg-white/50', 'dark:bg-slate-800/50', 'backdrop-blur-sm');
            });
            
            // Tambahkan style active ke card yang diklik
            this.classList.remove('border-slate-200/60', 'dark:border-slate-700/60', 'bg-white/50', 'dark:bg-slate-800/50', 'backdrop-blur-sm');
            this.classList.add('border-indigo-500', 'bg-gradient-to-r', 'from-indigo-50/80', 'to-purple-50/80', 'dark:from-indigo-900/30', 'dark:to-purple-900/30', 'ring-1', 'ring-indigo-500', 'shadow-md');

            const videoUrl = this.getAttribute('data-video-url');
            const streamId = this.getAttribute('data-stream-id');
            const title = this.querySelector('.cctv-title').innerText;

            if (videoUrl) {
                player.src({ type: 'application/x-mpegURL', src: videoUrl });
                player.play();
                
                activeStreamId = streamId;
                document.getElementById('video-title').innerText = title;
                
                // Sinkronisasi view count utama segera setelah diklik
                const currentViewCount = this.querySelector('.viewer-count').innerText;
                document.getElementById('viewer-count-main').innerText = currentViewCount;
            }
        });
    });

    // Polling Viewer Stats dari Backend Internal (bukan AMS langsung)
    function fetchStats() {
        fetch('/api/cctv/stats')
            .then(res => res.json())
            .then(data => {
                if (data && !data.error) {
                    // Update semua angka viewers & status di list
                    document.querySelectorAll('.viewer-count').forEach(el => {
                        const id = el.getAttribute('data-id');
                        if (data[id] !== undefined) {
                            el.innerText = data[id].viewers;
                            
                            // Update Badge Status
                            const badge = document.querySelector(`.cctv-badge[data-id="${id}"]`);
                            if (badge) {
                                if (data[id].status === 'broadcasting') {
                                    badge.innerHTML = `<span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Live`;
                                    badge.className = "text-xs font-medium text-emerald-600 flex items-center gap-1 cctv-badge";
                                } else {
                                    badge.innerHTML = `<span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Offline`;
                                    badge.className = "text-xs font-medium text-slate-500 flex items-center gap-1 cctv-badge";
                                }
                            }

                            // Jika ini kamera yang sedang aktif, update juga angka utama
                            if (id === activeStreamId) {
                                document.getElementById('viewer-count-main').innerText = data[id].viewers;
                                
                                const mainBadge = document.getElementById('main-status-badge');
                                if (mainBadge) {
                                    if (data[id].status === 'broadcasting') {
                                        mainBadge.innerHTML = `<span class="relative flex h-2 w-2"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span></span> LIVE`;
                                        mainBadge.className = "inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-red-50 text-red-600";
                                    } else {
                                        mainBadge.innerHTML = `<span class="relative flex h-2 w-2"><span class="relative inline-flex rounded-full h-2 w-2 bg-slate-500"></span></span> OFFLINE`;
                                        mainBadge.className = "inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-slate-100 text-slate-600";
                                    }
                                }
                            }
                        }
                    });
                }
            })
            .catch(err => console.error("Gagal polling stats:", err));
    }

    // Polling setiap 10 detik
    setInterval(fetchStats, 10000);
    @endif
});
</script>
@endpush
