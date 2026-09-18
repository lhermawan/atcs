<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-50 text-gray-900">
        <div class="flex h-screen overflow-hidden">
            
            <!-- Sidebar -->
            <aside class="w-64 bg-slate-900 text-white flex-shrink-0 flex flex-col hidden md:flex">
                <div class="h-16 flex items-center px-6 border-b border-slate-800 bg-slate-950">
                    <span class="text-lg font-bold tracking-wider text-indigo-400">ATCS ADMIN</span>
                </div>
                
                <nav class="flex-1 overflow-y-auto py-4">
                    <ul class="space-y-1 px-3">
                        <li>
                            <a href="{{ route('dashboard') }}" class="flex items-center px-3 py-2 rounded-lg {{ request()->routeIs('dashboard') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                                Dashboard
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('analytics.index') }}" class="flex items-center px-3 py-2 rounded-lg {{ request()->routeIs('analytics.index') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                Live Analytics
                            </a>
                        </li>
                        <li>
                            <a href="/" target="_blank" class="flex items-center px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white mt-8 border border-slate-700">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                Lihat Halaman Publik
                            </a>
                        </li>
                    </ul>
                </nav>
                
                <!-- User Footer Sidebar -->
                <div class="p-4 border-t border-slate-800">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-full bg-indigo-500 flex items-center justify-center text-white font-bold">
                            {{ substr(Auth::user()?->name ?? 'A', 0, 1) }}
                        </div>
                        <div class="ml-3 truncate">
                            <p class="text-sm font-medium text-white">{{ Auth::user()?->name ?? 'Admin' }}</p>
                            <form method="POST" action="{{ route('logout') }}" class="mt-1">
                                @csrf
                                <button type="submit" class="text-xs text-slate-400 hover:text-rose-400">Log Out</button>
                            </form>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main Content Area -->
            <div class="flex-1 flex flex-col h-screen overflow-hidden">
                <!-- Mobile Header -->
                <header class="md:hidden bg-white shadow-sm h-16 flex items-center px-4 justify-between">
                    <span class="text-lg font-bold text-indigo-600">ATCS ADMIN</span>
                    <!-- Mobile Hamburger (simplified) -->
                    <button class="text-slate-500 hover:text-slate-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                </header>
                
                <!-- Page Heading (Optional) -->
                @isset($header)
                    <header class="bg-white shadow-sm border-b border-gray-200">
                        <div class="px-6 py-4">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Main Scrollable Content -->
                <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-6">
                    {{ $slot ?? '' }}
                    @yield('content')
                </main>
            </div>
        </div>
        
        <!-- Video.js Script for Live Stream -->
        <script src="https://vjs.zencdn.net/7.21.1/video.min.js"></script>
    </body>
</html>
