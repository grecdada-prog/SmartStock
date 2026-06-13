<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @unless(request()->boolean('modal'))
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="smartstore-session-timeout" content="900">
        <meta name="smartstore-logout-url" content="{{ route('logout') }}">
        <meta name="smartstore-login-url" content="{{ route('login') }}">
    @endunless
    <title>@yield('title', 'Soldes') - SmartStore</title>
    <x-favicon />
    <style>[x-cloak]{display:none!important}</style>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="font-sans antialiased bg-gray-50">
    @php($isModalFrame = request()->boolean('modal'))
    <div class="flex min-h-screen flex-col" style="{{ $isModalFrame ? '' : 'padding-top: var(--smartstore-navbar-height);' }}">
        @unless($isModalFrame)
        <!-- Navigation -->
        <nav x-data="{ open: false }" class="smartstore-navbar-shell fixed inset-x-0 top-0 z-40 text-gray-800" style="@include('components.navbar-shell-style')">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex h-14 items-center justify-between">
                    <!-- Logo Section (Left) -->
                    <div class="shrink-0 flex items-center">
                        <a href="{{ route('seller.dashboard') }}">
                            <x-application-mark class="block h-9 w-auto" />
                        </a>
                    </div>

                    <!-- Navigation Links Section (Center) -->
                    <div class="hidden flex-1 justify-center sm:flex">
                        <div class="flex items-center gap-1">
                            <x-nav-link href="{{ route('seller.pos.index') }}" :active="request()->routeIs('seller.pos.*')">
                                Point de Vente
                            </x-nav-link>

                            {{-- Dropdown Services — même pattern que x-dropdown du profil --}}
                            <div class="relative" x-data="{ openServices: false }" @click.away="openServices = false" @keydown.escape.window="openServices = false">
                                <button
                                    @click="openServices = !openServices"
                                    class="flex min-h-8 items-center gap-1 rounded-md px-2.5 text-sm font-semibold transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#e80033] focus:ring-offset-2
                                        {{ request()->routeIs('seller.tokens.*') || request()->routeIs('seller.services.*')
                                            ? 'text-gray-950'
                                            : 'text-gray-700 hover:text-gray-950' }}"
                                >
                                    Services
                                    <svg class="h-4 w-4 transition-transform duration-150" :class="openServices ? 'rotate-180' : ''" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>

                                <div
                                    x-show="openServices"
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="transform opacity-0 scale-95"
                                    x-transition:enter-end="transform opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="transform opacity-100 scale-100"
                                    x-transition:leave-end="transform opacity-0 scale-95"
                                    class="absolute left-0 top-full z-[60] mt-2 w-auto min-w-max rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                                    style="display:none;"
                                >
                                    {{-- Label groupe --}}
                                    <div class="px-3 pb-1 pt-2">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Services</p>
                                    </div>

                                    <a href="{{ route('seller.tokens.create') }}"
                                       @click="openServices = false"
                                       class="block whitespace-nowrap px-4 py-2.5 text-sm transition
                                           {{ request()->routeIs('seller.tokens.*')
                                               ? 'bg-rose-50 text-rose-700 font-semibold'
                                               : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900' }}">
                                        Token Énergie
                                    </a>

                                    <a href="{{ route('seller.services.index') }}"
                                       @click="openServices = false"
                                       class="block whitespace-nowrap px-4 py-2.5 text-sm transition
                                           {{ request()->routeIs('seller.services.*')
                                               ? 'bg-rose-50 text-rose-700 font-semibold'
                                               : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900' }}">
                                        Dépôt / Retrait MOMO
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Profile Section (Right) -->
                    <div class="hidden shrink-0 sm:flex sm:items-center">
                        <x-dropdown align="right" width="48" content-classes="py-1 bg-white" dropdown-classes="z-[60]">
                            <x-slot name="trigger">
                                <button class="flex min-h-8 items-center gap-1 rounded-md px-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 hover:text-gray-950 focus:outline-none focus:ring-2 focus:ring-[#e80033] focus:ring-offset-2">
                                    {{ Auth::user()->name }}
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                {{-- Groupe Navigation --}}
                                <div class="px-3 pb-1 pt-2">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Navigation</p>
                                </div>
                                <x-dropdown-link href="{{ route('seller.sales.history') }}"
                                    class="whitespace-nowrap py-2.5 {{ request()->routeIs('seller.sales.*') ? 'bg-rose-50 !text-rose-700 font-semibold' : '' }}">
                                    Historique
                                </x-dropdown-link>
                                <x-dropdown-link href="{{ route('seller.dashboard') }}"
                                    class="whitespace-nowrap py-2.5 {{ request()->routeIs('seller.dashboard') ? 'bg-rose-50 !text-rose-700 font-semibold' : '' }}">
                                    Soldes
                                </x-dropdown-link>
                                <x-dropdown-link href="{{ route('seller.direct-restock.create') }}"
                                    class="whitespace-nowrap py-2.5 {{ request()->routeIs('seller.direct-restock.*') ? 'bg-rose-50 !text-rose-700 font-semibold' : '' }}">
                                    Appro direct
                                </x-dropdown-link>

                                <div class="my-1 border-t border-gray-100"></div>

                                {{-- Groupe Caisse --}}
                                <div class="px-3 pb-1 pt-2">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Caisse</p>
                                </div>
                                @php($cashIsOpen = app(\App\Services\CashRegisterService::class)->isOpenForSeller(Auth::user()))
                                @if($cashIsOpen)
                                    <form method="POST" action="{{ route('seller.dashboard.close-cash-register') }}" x-data>
                                        @csrf
                                        <x-dropdown-link href="{{ route('seller.dashboard.close-cash-register') }}"
                                            class="whitespace-nowrap py-2.5"
                                            @click.prevent="$root.submit();">
                                            Fermer la caisse
                                        </x-dropdown-link>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('seller.dashboard.open-cash-register') }}" x-data>
                                        @csrf
                                        <x-dropdown-link href="{{ route('seller.dashboard.open-cash-register') }}"
                                            class="whitespace-nowrap py-2.5"
                                            @click.prevent="$root.submit();">
                                            Ouvrir la caisse
                                        </x-dropdown-link>
                                    </form>
                                @endif

                                <div class="my-1 border-t border-gray-100"></div>

                                {{-- Groupe Compte --}}
                                <div class="px-3 pb-1 pt-2">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Compte</p>
                                </div>
                                <x-dropdown-link href="{{ route('account.profile.show') }}"
                                    class="whitespace-nowrap py-2.5 {{ request()->routeIs('account.profile.show') ? 'bg-rose-50 !text-rose-700 font-semibold' : '' }}">
                                    Profil
                                </x-dropdown-link>

                                <div class="my-1 border-t border-gray-100"></div>

                                <form method="POST" action="{{ route('logout') }}" x-data>
                                    @csrf
                                    <x-dropdown-link href="{{ route('logout') }}"
                                        class="whitespace-nowrap py-2.5 !text-red-600 hover:bg-red-50 hover:!text-red-700"
                                        @click.prevent="$root.submit();">
                                        Déconnexion
                                    </x-dropdown-link>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>

                    <!-- Hamburger (Mobile) -->
                    <div class="flex items-center sm:hidden">
                        <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-rose-50 focus:outline-none focus:bg-rose-50 focus:text-gray-700 transition duration-150 ease-in-out">
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Responsive Navigation Menu -->
            <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
                <div class="pt-2 pb-3 space-y-1">
                    <x-responsive-nav-link href="{{ route('seller.pos.index') }}" :active="request()->routeIs('seller.pos.*')">
                        Point de Vente
                    </x-responsive-nav-link>
                    <x-responsive-nav-link href="{{ route('seller.tokens.create') }}" :active="request()->routeIs('seller.tokens.*')">
                        Services — Token Énergie
                    </x-responsive-nav-link>
                    <x-responsive-nav-link href="{{ route('seller.services.index') }}" :active="request()->routeIs('seller.services.*')">
                        Services — Dépôt / Retrait MOMO
                    </x-responsive-nav-link>
                </div>

                <!-- Responsive Settings Options -->
                <div class="pt-4 pb-1 border-t border-gray-200">
                    <div class="flex items-center px-4">
                        <div>
                            <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                            <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
                        </div>
                    </div>

                    <div class="mt-3 space-y-1">
                        <x-responsive-nav-link href="{{ route('seller.sales.history') }}" :active="request()->routeIs('seller.sales.*')">
                            Historique
                        </x-responsive-nav-link>

                        <x-responsive-nav-link href="{{ route('seller.dashboard') }}" :active="request()->routeIs('seller.dashboard')">
                            Soldes
                        </x-responsive-nav-link>

                        <x-responsive-nav-link href="{{ route('seller.direct-restock.create') }}" :active="request()->routeIs('seller.direct-restock.*')">
                            Appro direct
                        </x-responsive-nav-link>

                        <!-- Fermer / Ouvrir la caisse -->
                        @if($cashIsOpen)
                            <form method="POST" action="{{ route('seller.dashboard.close-cash-register') }}" x-data>
                                @csrf
                                <x-responsive-nav-link href="{{ route('seller.dashboard.close-cash-register') }}" @click.prevent="$root.submit();">
                                    Fermer la caisse
                                </x-responsive-nav-link>
                            </form>
                        @else
                            <form method="POST" action="{{ route('seller.dashboard.open-cash-register') }}" x-data>
                                @csrf
                                <x-responsive-nav-link href="{{ route('seller.dashboard.open-cash-register') }}" @click.prevent="$root.submit();">
                                    Ouvrir la caisse
                                </x-responsive-nav-link>
                            </form>
                        @endif

                        <x-responsive-nav-link href="{{ route('account.profile.show') }}" :active="request()->routeIs('account.profile.show')">
                            Profil
                        </x-responsive-nav-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}" x-data>
                            @csrf
                            <x-responsive-nav-link href="{{ route('logout') }}" @click.prevent="$root.submit();">
                                Déconnexion
                            </x-responsive-nav-link>
                        </form>
                    </div>
                </div>
            </div>
        </nav>
        @endunless

        @unless($isModalFrame)
            <x-flash-messages />
        @endunless

        @php($managerClosureNotice = $isModalFrame ? null : \Illuminate\Support\Facades\Cache::get('seller_cash_register_closed_notice:'.Auth::id()))
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
        <main class="mx-auto w-full max-w-7xl flex-1 py-4 px-2 sm:px-4 lg:px-6">
            @yield('content')
        </main>
        @unless($isModalFrame)
            <x-dashboard-footer />
        @endunless
    </div>
</body>
</html>
