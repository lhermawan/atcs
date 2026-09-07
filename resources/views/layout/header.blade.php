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
      <link href="https://vjs.zencdn.net/7.21.1/video-js.css" rel="stylesheet" />
      <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/clappr/0.4.3/clappr.min.css">
        <script src="{{ asset('vidoe/js/videojs-contrib-hls.js')}}"></script>
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
