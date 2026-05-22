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
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />

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

            @php($managerClosureNotice = (! $isModalFrame && Auth::check() && Auth::user()->hasRole('seller')) ? \Illuminate\Support\Facades\Cache::get('seller_cash_register_closed_notice:'.Auth::id()) : null)
            @if($managerClosureNotice)
                <div
                    x-data="{ open: true, acknowledging: false, close() { this.acknowledging = true; fetch('{{ route('seller.dashboard.manager-closure-notice.ack') }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '', 'Accept': 'application/json' } }).finally(() => { this.open = false; this.acknowledging = false; }); } }"
                    x-show="open"
                    x-transition.opacity
                    class="fixed inset-0 z-[90] flex items-center justify-center bg-slate-950/60 px-4"
                >
                    <div class="w-full max-w-md rounded-lg bg-white shadow-2xl">
                        <div class="border-b border-rose-100 bg-rose-50 px-6 py-5">
                            <h2 class="text-lg font-extrabold text-gray-950">Caisse cloturee</h2>
                        </div>
                        <div class="px-6 py-5">
                            <p class="text-sm font-medium text-gray-700">{{ $managerClosureNotice }}</p>
                        </div>
                        <div class="flex justify-end border-t border-gray-100 bg-gray-50 px-6 py-4">
                            <button
                                type="button"
                                @click="close()"
                                :disabled="acknowledging"
                                class="inline-flex items-center justify-center rounded-md bg-rose-600 px-5 py-2 text-sm font-bold text-white transition hover:bg-rose-700 disabled:cursor-wait disabled:opacity-70"
                            >
                                J'ai compris
                            </button>
                        </div>
                    </div>
                </div>
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
