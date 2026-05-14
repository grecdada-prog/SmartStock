<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @unless(request()->boolean('modal'))
            <meta name="smartstore-session-timeout" content="{{ config('session.lifetime', 10) * 60 }}">
            <meta name="smartstore-logout-url" content="{{ route('logout') }}">
            <meta name="smartstore-login-url" content="{{ route('login') }}">
        @endunless

        <title>{{ config('app.name', 'Laravel') }}</title>
        <x-favicon />

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
    </head>
    <body class="font-sans antialiased">
        @php($isModalFrame = request()->boolean('modal'))
        @unless($isModalFrame)
            <x-banner />
        @endunless

        <div class="flex min-h-screen flex-col bg-gray-100" style="{{ $isModalFrame ? '' : 'padding-top: 4rem;' }}">
            @unless($isModalFrame)
                @livewire('navigation-menu')
            @endunless

            <!-- Page Heading -->
            @if (! $isModalFrame && isset($header))
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main class="flex-1">
                {{ $slot }}
            </main>
            @unless($isModalFrame)
                <x-dashboard-footer />
            @endunless
        </div>

        @stack('modals')

        @livewireScripts
    </body>
</html>
