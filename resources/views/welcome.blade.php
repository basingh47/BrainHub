<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'BrainHub') }} — Coming Soon</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-100 text-gray-900">
        <main class="min-h-screen flex flex-col items-center justify-center px-6 text-center">
            <h1 class="text-5xl sm:text-6xl font-bold tracking-tight">
                {{ config('app.name', 'BrainHub') }}
            </h1>
            <p class="mt-4 text-xl sm:text-2xl font-semibold text-gray-500">
                Coming Soon
            </p>
        </main>
    </body>
</html>
