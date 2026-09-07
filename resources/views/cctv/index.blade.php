@include('layout.header')



         <div id="content-wrapper">
            <div class="container-fluid pb-0">
               <div class="video-block-right-list section-padding">
                  <div class="row mb-4">
                  <div class="col-md-8">
                       {{-- Single Video Player --}}
                <div class="single-video">
                    @if (!empty($cctvs) && isset($cctvs[0]))
                    <video
    id="main-video"
    class="video-js vjs-default-skin"
    controls
    preload="auto"
    autoplay
    muted
    style="width: 100% !important; height: 500px;"
    poster="{{ asset('img/CCTV-tower.jpg') }}"
    data-setup='{"autoplay": true}'
>


        <source src="https://ams.ciamiskab.go.id:5443/LiveApp/streams/{{ $cctvs[0]['streamId'] }}.m3u8" type="application/x-mpegURL" />
    </video>
@else
    <p class="text-red-500">Tidak ada siaran CCTV yang aktif saat ini.</p>
@endif
                    
                    </video>
                </div>
                  </div>
                  <div class="col-md-4">
                        {{-- Daftar CCTV --}}
<div class="video-slider-right-list">

    @forelse ($cctvs as $cctv)
    <div
        class="video-card video-card-list {{ $loop->first ? 'active' : '' }}"
        style="cursor:pointer"
        data-video-url="https://ams.ciamiskab.go.id:5443/LiveApp/streams/{{ $cctv['streamId'] }}.m3u8"
    >
        <div class="video-card-image position-relative">
            <a class="play-icon" href="javascript:void(0);"><i class="fas fa-play-circle"></i></a>
            <a href="javascript:void(0);">
                <img class="img-fluid" src="{{ asset('img/CCTV-tower.jpg')}}" alt="Thumbnail">
            </a>
            <div class="time">
                {{ $cctv['status'] === 'broadcasting' ? 'Live' : 'Offline' }}
            </div>
        </div>
        <div class="video-card-body">
            <div class="video-title">
                <a href="javascript:void(0);">{{ $cctv['name'] }}</a>
            </div>
            <div class="video-page text-success">
                Streaming
                <a title="" data-placement="top" data-toggle="tooltip" href="#" data-original-title="Verified">
                    <i class="fas fa-check-circle text-success"></i>
                </a>
            </div>
            <div class="video-view">
                <span class="viewer-count-list" data-stream-id="{{ $cctv['streamId'] }}">
                    {{ $cctv['hlsViewerCount'] }} Views&nbsp;
                </span><i class="fas fa-eye"></i>
            </div>
        </div>
    </div>
@empty
    <div class="alert alert-warning">
        Tidak ada CCTV yang tersedia atau sedang siaran saat ini.
    </div>
@endforelse

</div>
                  </div>
                  </div>
               </div>
               <div class="video-block section-padding">
                  <div class="row">
                     <div class="col-md-8">
                        <div class="single-video-left">
                           <div class="single-video-title box mb-3">
   @if (!empty($cctvs) && isset($cctvs[0]))
    <h2 id="video-title"><a href="#">{{ $cctvs[0]['name'] }}</a></h2>
    <p class="mb-0"><i class="fas fa-eye"></i> <span id="viewer-count-main">{{ $cctvs[0]['hlsViewerCount'] }} Views</span></p>
@else
    <h2 id="video-title"><a href="#">Tidak ada CCTV aktif</a></h2>
    <p class="mb-0"><i class="fas fa-eye"></i> <span id="viewer-count-main">0 Views</span></p>
@endif
</div>
                           <div class="single-video-author box mb-3">
                              <div class="float-right"><a href="https://www.instagram.com/atcs.ciamis/" target='_blank'><button class="btn btn-danger" type="button"> Follow me On <i class="fa-brands fa-instagram"></i><strong></strong></button></a> </div>
                              <img class="img-fluid" src="{{ asset('img/logo.jpg')}}" alt="">
                              <p><a href="https://www.instagram.com/atcs.ciamis/"><strong>ATCS DISHUB </strong></a> <span title="" data-placement="top" data-toggle="tooltip" data-original-title="Verified"><i class="fas fa-check-circle text-success"></i></span></p>
                              <small>KABUPATEN CIAMIS</small>
                           </div>


                        </div>
                     </div>
                     <div class="col-md-4">
                        <div class="single-video-right">
                           <div class="row">
                              <div class="col-md-12">
                                 {{-- <div class="adblock">
                                    <div class="img">
                                       Google AdSense<br>
                                       336 x 280
                                    </div>
                                 </div> --}}

                              </div>

                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
            <!-- /.container-fluid -->
            <!-- Sticky Footer -->
            {{-- <footer class="sticky-footer">
               <div class="container">
                  <div class="row no-gutters">
                     <div class="col-lg-6 col-sm-6">
                        <p class="mt-1 mb-0">&copy; Copyright 2018 <strong class="text-dark">Vidoe</strong>. All Rights Reserved<br>
                           <small class="mt-0 mb-0">Made with <i class="fas fa-heart text-danger"></i> by <a class="text-primary" target="_blank" href="https://askbootstrap.com/">Ask Bootstrap</a>
                           </small>
                        </p>
                     </div>
                     <div class="col-lg-6 col-sm-6 text-right">
                        <div class="app">
                           <a href="#"><img alt="" src="img/google.png"></a>
                           <a href="#"><img alt="" src="img/apple.png"></a>
                        </div>
                     </div>
                  </div>
               </div>
            </footer> --}}
         </div>
         <!-- /.content-wrapper -->
      </div>
      <!-- /#wrapper -->
      <!-- Scroll to Top Button-->
      <a class="scroll-to-top rounded" href="#page-top">
      <i class="fas fa-angle-up"></i>
      </a>
      <!-- Logout Modal-->
      {{-- <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
         <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
               <div class="modal-header">
                  <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                  <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">×</span>
                  </button>
               </div>
               <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
               <div class="modal-footer">
                  <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                  <a class="btn btn-primary" href="login.html">Logout</a>
               </div>
            </div>
         </div>
      </div> --}}

{{-- Style Highlight Active Card --}}
{{-- <style>
    .video-card.video-card-list {
        border: 1px solid transparent;
        transition: border-color 0.3s ease, box-shadow 0.3s ease;
        margin-bottom: 15px;
        border-radius: 6px;
        overflow: hidden;
    }
    .video-card.video-card-list:hover {
        border-color: #007bff;
        box-shadow: 0 0 10px rgba(0,123,255,0.3);
    }
    .video-card.video-card-list.active {
        border-color: #007bff;
        box-shadow: 0 0 15px rgba(0,123,255,0.6);
        background-color: #f0f8ff;
    }
</style> --}}

{{-- Script untuk ganti video dan highlight active card --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const player = videojs('main-video');
    let activeStreamId = "{{ !empty($cctvs) && isset($cctvs[0]) ? $cctvs[0]['streamId'] : '' }}";

    function updateViewerCount(streamId, callback) {
        fetch(`https://ams.ciamiskab.go.id:5443/LiveApp/rest/v2/broadcasts/${streamId}`)
            .then(res => res.json())
            .then(data => {
                if (data && data.hlsViewerCount !== undefined) {
                    if (typeof callback === 'function') callback(data.hlsViewerCount);
                }
            })
            .catch(err => console.error("Gagal ambil viewer:", err));
    }

    function updateAllViewerCounts() {
        const viewerSpans = document.querySelectorAll('.viewer-count-list');
        viewerSpans.forEach(span => {
            const streamId = span.getAttribute('data-stream-id');
            if (streamId) {
                updateViewerCount(streamId, (count) => {
                    span.textContent = `${count} Views`;
                });
            }
        });
    }

    function updateMainViewerCount() {
        updateViewerCount(activeStreamId, (count) => {
            document.getElementById('viewer-count-main').textContent = `${count} Views`;
        });
    }

    // Auto update setiap 5 detik
    setInterval(() => {
        updateAllViewerCounts();
        updateMainViewerCount();
    }, 5000);

    // Pertama kali jalan
    updateAllViewerCounts();
    updateMainViewerCount();

    // Saat klik ganti video
    document.querySelectorAll('.video-card.video-card-list').forEach(card => {
        card.addEventListener('click', () => {
            document.querySelectorAll('.video-card.video-card-list').forEach(c => c.classList.remove('active'));
            card.classList.add('active');

            const videoUrl = card.getAttribute('data-video-url');
            const streamId = new URL(videoUrl).pathname.split('/').pop().replace('.m3u8', '');
            const videoTitle = card.querySelector('.video-title a')?.textContent || 'Judul tidak ditemukan';

            if (videoUrl && videoUrl !== '#') {
                player.src({ type: 'application/x-mpegURL', src: videoUrl });
                player.play();

                activeStreamId = streamId;
                document.getElementById('video-title').innerHTML = `<a href="#">${videoTitle}</a>`;
                updateMainViewerCount();
            }
        });
    });
});
</script>
<script src="https://vjs.zencdn.net/7.21.1/video.min.js"></script>
      <!-- Bootstrap core JavaScript-->
      <script src="{{ asset('vidoe/vendor/jquery/jquery.min.js') }}"></script>
      <script src="{{ asset('vidoe/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
      <!-- Core plugin JavaScript-->
      <script src="{{ asset('vidoe/vendor/jquery-easing/jquery.easing.min.js') }}"></script>
      <!-- Owl Carousel -->
      <script src="{{ asset('vidoe/vendor/owl-carousel/owl.carousel.js') }}"></script>
      <!-- Custom scripts for all pages-->
      <script src="{{ asset('vidoe/js/custom.js') }}"></script>
   </body>
</html>
