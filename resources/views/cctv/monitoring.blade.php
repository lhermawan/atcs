<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="utf-8">
      <meta http-equiv="X-UA-Compatible" content="IE=edge">
      <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
      <meta name="description" content="Askbootstrap">
      <meta name="author" content="Askbootstrap">
      <title>ATCS Kabupaten Ciamis</title>
      <!-- Favicon Icon -->
      <link rel="icon" type="image/png" href="{{ asset('img/logo.jpg')}}">
      <!-- Bootstrap core CSS-->
      <link href="{{ asset('vidoe/vendor/bootstrap/css/bootstrap.min.css')}}" rel="stylesheet">
      <!-- Custom fonts for this template-->
      <link href="{{ asset('vidoe/vendor/fontawesome-free/css/all.min.css')}}" rel="stylesheet" type="text/css">
      <!-- Custom styles for this template-->
      <link href="{{ asset('vidoe/css/osahan.css')}}" rel="stylesheet">
      <!-- Owl Carousel -->
      <link rel="stylesheet" href="{{ asset('vidoe/vendor/owl-carousel/owl.carousel.css')}}">
      <link rel="stylesheet" href="{{ asset('vidoe/vendor/owl-carousel/owl.theme.css')}}">
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/clappr/0.4.3/clappr.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/videojs-contrib-hls/5.15.1/videojs-contrib-hls.min.js"></script>
<link href="https://vjs.zencdn.net/7.20.3/video-js.css" rel="stylesheet">
   </head>
   <body id="page-top">
      <nav class="navbar navbar-expand navbar-light bg-white static-top osahan-nav sticky-top">
         &nbsp;&nbsp;
         <button class="btn btn-link btn-sm text-secondary order-1 order-sm-0" id="sidebarToggle">
         <i class="fas fa-bars"></i>
         </button> &nbsp;&nbsp;
         <a class="navbar-brand mr-1" href=""><img class="img-fluid" alt="" style="width: 25px; height: 35px; margin-right: 10px;" src="{{ asset('img/logo.png')}}">ATCS KAB. CIAMIS</a>
         <!-- Navbar Search -->
         {{-- <form class="d-none d-md-inline-block form-inline ml-auto mr-0 mr-md-5 my-2 my-md-0 osahan-navbar-search">
            <div class="input-group">
               <input type="text" class="form-control" placeholder="Search for...">
               <div class="input-group-append">
                  <button class="btn btn-light" type="button">
                  <i class="fas fa-search"></i>
                  </button>
               </div>
            </div>
         </form> --}}
         <!-- Navbar -->

      </nav>
      <div id="wrapper">
         <!-- Sidebar -->
         <ul class="sidebar navbar-nav">
            <li class="nav-item {{ request()->routeIs('home') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('home') }}">
                    <i class="fas fa-fw fa-home"></i>
                    <span>Home</span>
                </a>
            </li>
            <li class="nav-item {{ request()->routeIs('monitoring') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('monitoring') }}">
                    <i class="fas fa-fw fa-video"></i>
                    <span>Monitoring</span>
                </a>
            </li>

                     </ul>

<style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        background-color: #f5f5f5;
    }

    /* Ensure consistent box sizing */
*,
*::before,
*::after {
    box-sizing: border-box;
}

/* Grid Layout with Fallback */
.cctv-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); /* Ensure at least 300px width for each video container */
    gap: 0; /* Remove the gap between the items */
    padding: 0; /* Remove padding */
    margin: 0;
    max-width: 100%; /* Ensure it doesn't overflow */
    box-sizing: border-box;
    grid-auto-rows: auto; /* Ensure the rows adjust based on content height */
}

/* Ensure the video containers fill properly */
.cctv-card {
    background: none; /* No background */
    border: none; /* No border */
    box-shadow: none; /* No shadow */
    overflow: hidden;
    width: 100%;
    margin: 0;
}

.video-container {
    position: relative;
    padding-bottom: 81.5%; /* 4:3 Aspect Ratio (height is 75% of width) */
    height: 0;
    overflow: hidden;
    width: 100%;
}

/* Ensure video player takes full container space */
.video-js {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover; /* Ensure the video covers the container while maintaining aspect ratio */
}

/* Fallback for older browsers */
@supports not (display: grid) {
    .cctv-container {
        display: flex;
        flex-wrap: wrap;
    }

    .cctv-card {
        flex: 1 0 300px; /* Allow cards to take at least 300px width */
        margin: 10px;
    }
}
</style>

<div id="content-wrapper">
    <div class="container-fluid pb-0">
        <div class="cctv-container">



            @forelse ($cctvs as $cctv)
                <div class="cctv-card">
                    <div class="video-container">
                        <video
      id="video-{{ $cctv['streamId'] }}"
      class="video-js vjs-default-skin"
      controls
      preload="auto"
      autoplay
      muted
      width="100%"



      data-setup='{"autoplay": true}'
    >
      <source src="https://ams.ciamiskab.go.id:5443/LiveApp/streams/{{ $cctv['streamId'] }}.m3u8" type="application/x-mpegURL" />
      Your browser does not support the video tag.
    </video>
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

<!-- Video.js Assets -->
    <script src="https://vjs.zencdn.net/7.20.3/video.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/videojs-contrib-hls/5.15.1/videojs-contrib-hls.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
<script>
document.querySelectorAll('video').forEach(video => {
    const src = video.querySelector('source').src;

    if (Hls.isSupported()) {
        const hls = new Hls();
        hls.loadSource(src);
        hls.attachMedia(video);
    } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
        video.src = src;
    }
});
</script>
<script>
    document.querySelectorAll('.video-js').forEach(player => {
        videojs(player, {
            controls: true,
            autoplay: true,
            muted: true,
            preload: 'auto',
            techOrder: ['html5']
        });
    });
</script>
<script src="{{ asset('vidoe/vendor/jquery/jquery.min.js') }}"></script>
      <script src="{{ asset('vidoe/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
      <!-- Core plugin JavaScript-->
      <script src="{{ asset('vidoe/vendor/jquery-easing/jquery.easing.min.js') }}"></script>
      <!-- Owl Carousel -->
      <script src="{{ asset('vidoe/vendor/owl-carousel/owl.carousel.js') }}"></script>
      <!-- Custom scripts for all pages-->
      <script src="{{ asset('vidoe/js/custom.js') }}"></script>
