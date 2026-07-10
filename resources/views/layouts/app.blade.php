<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'PT Dimas Love Medika') }}</title>

        <link rel="icon" href="{{ asset('img/favicon.png') }}" type="image/png">
        <link rel="apple-touch-icon" href="{{ asset('img/favicon.png') }}">

        <!-- Fonts: Figtree (body) + Orbitron (judul/brand) -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&family=orbitron:600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-100 text-gray-800">
        <div x-data="{ sidebarOpen: false }" class="min-h-screen">
            <!-- Overlay (mobile) -->
            <div x-show="sidebarOpen" x-cloak
                 @click="sidebarOpen = false"
                 class="fixed inset-0 z-30 bg-black/50 lg:hidden"></div>

            <!-- Sidebar -->
            @include('layouts.sidebar')

            <!-- Konten utama -->
            <div class="lg:pl-64">
                <!-- Top bar -->
                <header class="sticky top-0 z-20 flex h-16 items-center gap-4 border-b border-gray-200 bg-white px-4 shadow-sm sm:px-6">
                    <button @click="sidebarOpen = true" class="text-gray-500 lg:hidden" aria-label="Buka menu">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    <div class="flex-1">
                        @isset($header)
                            <h1 class="text-lg font-semibold text-navy">{{ $header }}</h1>
                        @endisset
                    </div>

                    <!-- User dropdown -->
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="flex items-center gap-2 rounded-full py-1.5 pl-2 pr-3 text-sm text-gray-700 hover:bg-gray-100">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gold-gradient text-sm font-bold text-navy-dark">
                                {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                            </span>
                            <span class="hidden text-left sm:block">
                                <span class="block font-medium leading-tight">{{ Auth::user()->name ?? 'User' }}</span>
                                <span class="block text-xs leading-tight text-gold-dark">
                                    {{ Auth::user()?->getRoleNames()->first() ?? 'Staff' }}
                                </span>
                            </span>
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-cloak @click.outside="open = false"
                             class="absolute right-0 mt-2 w-48 rounded-lg border border-gray-100 bg-white py-1 shadow-lg">
                            <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Profil</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-gray-50">Keluar</button>
                            </form>
                        </div>
                    </div>
                </header>

                <!-- Page content -->
                <main class="p-4 sm:p-6">
                    @if (session('status'))
                        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                             class="mb-4 flex items-start gap-2 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                            <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>{{ session('status') }}</span>
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="mb-4 flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>{{ session('error') }}</span>
                        </div>
                    @endif
                    {{ $slot }}
                </main>
            </div>
        </div>
        @stack('scripts')
    </body>
</html>
