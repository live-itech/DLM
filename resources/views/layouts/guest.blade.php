<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'PT Dimas Love Medika') }}</title>

        <link rel="icon" href="{{ asset('img/favicon.png') }}" type="image/png">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&family=orbitron:600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="flex min-h-screen flex-col items-center justify-center bg-navy-dark px-4 py-8"
             style="background-image: radial-gradient(circle at 50% 0%, rgba(205,164,94,0.15), transparent 60%);">
            <div class="mb-6 flex flex-col items-center">
                <div class="flex h-24 w-24 items-center justify-center rounded-2xl bg-white/5 ring-1 ring-gold/30">
                    <img src="{{ asset('img/logo.png') }}" alt="PT Dimas Love Medika" class="h-20 w-20 object-contain">
                </div>
                <h1 class="mt-4 font-display text-xl font-bold tracking-wide text-gold">PT DIMAS LOVE MEDIKA</h1>
                <p class="text-sm text-gray-400">Sistem Bisnis Internal</p>
            </div>

            <div class="w-full overflow-hidden bg-white px-6 py-6 shadow-xl sm:max-w-md sm:rounded-2xl">
                {{ $slot }}
            </div>

            <p class="mt-6 text-xs text-gray-500">&copy; {{ date('Y') }} PT Dimas Love Medika &middot; Distribusi Alat Kesehatan</p>
        </div>
    </body>
</html>
