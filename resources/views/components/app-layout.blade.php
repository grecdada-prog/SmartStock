@php
    $user = auth()->user();
    $isModalFrame = request()->boolean('modal');
@endphp

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
    <title>@yield('title', 'SmartStore')</title>
    <x-favicon />
    <style>[x-cloak]{display:none!important}</style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="flex min-h-screen flex-col" style="{{ $isModalFrame ? '' : 'padding-top: var(--smartstore-navbar-height);' }}">
        @unless($isModalFrame)
        <!-- Navigation -->
        <nav x-data="{ open: false }" class="smartstore-navbar-shell fixed inset-x-0 top-0 z-40 text-gray-800" style="@include('components.navbar-shell-style')">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex h-14 justify-between">
                    <div class="flex min-w-0 flex-1 items-center">
                        <!-- Logo -->
                        <div class="shrink-0 flex items-center">
                            @if($user->hasRole('super_admin'))
                                <a href="{{ route('superadmin.dashboard') }}">
                                    <x-application-mark class="block h-9 w-auto" />
                                </a>
                            @elseif($user->hasRole('manager'))
                                <a href="{{ route('manager.dashboard') }}">
                                    <x-application-mark class="block h-9 w-auto" />
                                </a>
                            @elseif($user->hasRole('seller'))
                                <a href="{{ route('seller.dashboard') }}">
                                    <x-application-mark class="block h-9 w-auto" />
                                </a>
                            @endif
                        </div>

                        <!-- Navigation Links -->
                        <div class="hidden min-w-0 items-center gap-3 overflow-x-auto sm:ml-10 sm:flex">
                            @if($user->hasRole('super_admin'))
                                <x-nav-link href="{{ route('superadmin.dashboard') }}" :active="request()->routeIs('superadmin.dashboard')">
                                    Dashboard
                                </x-nav-link>
                                <x-nav-link href="{{ route('superadmin.users.index') }}" :active="request()->routeIs('superadmin.users.*')">
                                    Utilisateurs
                                </x-nav-link>
                                <x-nav-link href="{{ route('superadmin.products') }}" :active="request()->routeIs('superadmin.products')">
                                    Produits
                                </x-nav-link>
                                <x-nav-link href="{{ route('superadmin.sales') }}" :active="request()->routeIs('superadmin.sales')">
                                    Ventes
                                </x-nav-link>
                                <x-nav-link href="{{ route('superadmin.activity-logs') }}" :active="request()->routeIs('superadmin.activity-logs')">
                                    Logs
                                </x-nav-link>
                            @elseif($user->hasRole('manager'))
                                <x-nav-link href="{{ route('manager.dashboard') }}" :active="request()->routeIs('manager.dashboard')">
                                    Dashboard
                                </x-nav-link>
                                <x-nav-link href="{{ route('manager.sellers.index') }}" :active="request()->routeIs('manager.sellers.*')">
                                    Vendeurs
                                </x-nav-link>
                                <x-nav-link href="{{ route('manager.categories.index') }}" :active="request()->routeIs('manager.categories.*')">
                                    Catégories
                                </x-nav-link>
                                <x-nav-link href="{{ route('manager.products.index') }}" :active="request()->routeIs('manager.products.*')">
                                    Produits
                                </x-nav-link>
                                <x-nav-link href="{{ route('manager.stock.index') }}" :active="request()->routeIs('manager.stock.*')">
                                    Stock
                                </x-nav-link>
                                <x-nav-link href="{{ route('manager.sales') }}" :active="request()->routeIs('manager.sales*')">
                                    Ventes
                                </x-nav-link>
                                <x-nav-link href="{{ route('manager.reports.sales') }}" :active="request()->routeIs('manager.reports.*')">
                                    Rapports
                                </x-nav-link>
                            @elseif($user->hasRole('seller'))
                                <x-nav-link href="{{ route('seller.dashboard') }}" :active="request()->routeIs('seller.dashboard')">
                                    Dashboard
                                </x-nav-link>
                                <x-nav-link href="{{ route('seller.pos.index') }}" :active="request()->routeIs('seller.pos.*')">
                                    Point de Vente
                                </x-nav-link>
                                <x-nav-link href="{{ route('seller.sales.history') }}" :active="request()->routeIs('seller.sales.*')">
                                    Historique
                                </x-nav-link>
                                <x-nav-link href="{{ route('seller.tokens.create') }}" :active="request()->routeIs('seller.tokens.*')">
                                    Services
                                </x-nav-link>
                            @endif
                        </div>
                    </div>

                    <div class="hidden shrink-0 sm:ml-6 sm:flex sm:items-center">
                        <!-- Settings Dropdown -->
                        <div class="ml-3 relative">
                            <x-dropdown align="right" width="48">
                                <x-slot name="trigger">
                                    <button class="flex min-h-8 items-center rounded-md px-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 hover:text-gray-950 focus:outline-none focus:ring-2 focus:ring-[#e80033] focus:ring-offset-2">
                                        <div>{{ Auth::user()->name }}</div>
                                        <div class="ml-1">
                                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </button>
                                </x-slot>

                                <x-slot name="content">
                                    <div class="block px-4 py-2 text-xs text-gray-400">
                                        Gérer le compte
                                    </div>

                                    <x-dropdown-link href="{{ route('account.profile.show') }}">
                                        Profil
                                    </x-dropdown-link>

                                    <div class="border-t border-gray-100"></div>

                                    <form method="POST" action="{{ route('logout') }}" x-data>
                                        @csrf
                                        <x-dropdown-link href="{{ route('logout') }}" @click.prevent="$root.submit();">
                                            Déconnexion
                                        </x-dropdown-link>
                                    </form>
                                </x-slot>
                            </x-dropdown>
                        </div>
                    </div>

                    <!-- Hamburger -->
                    <div class="-mr-2 flex items-center sm:hidden">
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
                    @if($user->hasRole('super_admin'))
                        <x-responsive-nav-link href="{{ route('superadmin.dashboard') }}" :active="request()->routeIs('superadmin.dashboard')">Dashboard</x-responsive-nav-link>
                        <x-responsive-nav-link href="{{ route('superadmin.users.index') }}" :active="request()->routeIs('superadmin.users.*')">Utilisateurs</x-responsive-nav-link>
                        <x-responsive-nav-link href="{{ route('superadmin.products') }}" :active="request()->routeIs('superadmin.products')">Produits</x-responsive-nav-link>
                        <x-responsive-nav-link href="{{ route('superadmin.sales') }}" :active="request()->routeIs('superadmin.sales')">Ventes</x-responsive-nav-link>
                        <x-responsive-nav-link href="{{ route('superadmin.activity-logs') }}" :active="request()->routeIs('superadmin.activity-logs')">Logs</x-responsive-nav-link>
                    @elseif($user->hasRole('manager'))
                        <x-responsive-nav-link href="{{ route('manager.dashboard') }}" :active="request()->routeIs('manager.dashboard')">Dashboard</x-responsive-nav-link>
                        <x-responsive-nav-link href="{{ route('manager.sellers.index') }}" :active="request()->routeIs('manager.sellers.*')">Vendeurs</x-responsive-nav-link>
                        <x-responsive-nav-link href="{{ route('manager.categories.index') }}" :active="request()->routeIs('manager.categories.*')">Catégories</x-responsive-nav-link>
                        <x-responsive-nav-link href="{{ route('manager.products.index') }}" :active="request()->routeIs('manager.products.*')">Produits</x-responsive-nav-link>
                        <x-responsive-nav-link href="{{ route('manager.stock.index') }}" :active="request()->routeIs('manager.stock.*')">Stock</x-responsive-nav-link>
                        <x-responsive-nav-link href="{{ route('manager.sales') }}" :active="request()->routeIs('manager.sales*')">Ventes</x-responsive-nav-link>
                        <x-responsive-nav-link href="{{ route('manager.reports.sales') }}" :active="request()->routeIs('manager.reports.*')">Rapports</x-responsive-nav-link>
                    @elseif($user->hasRole('seller'))
                        <x-responsive-nav-link href="{{ route('seller.dashboard') }}" :active="request()->routeIs('seller.dashboard')">Dashboard</x-responsive-nav-link>
                        <x-responsive-nav-link href="{{ route('seller.pos.index') }}" :active="request()->routeIs('seller.pos.*')">Point de Vente</x-responsive-nav-link>
                        <x-responsive-nav-link href="{{ route('seller.sales.history') }}" :active="request()->routeIs('seller.sales.*')">Historique</x-responsive-nav-link>
                        <x-responsive-nav-link href="{{ route('seller.tokens.create') }}" :active="request()->routeIs('seller.tokens.*')">Services</x-responsive-nav-link>
                    @endif
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
                        <x-responsive-nav-link href="{{ route('account.profile.show') }}" :active="request()->routeIs('account.profile.show')">
                            Profil
                        </x-responsive-nav-link>

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

        <!-- Page Heading -->
        @if (! $isModalFrame && isset($header))
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endif

        @unless($isModalFrame)
            <x-flash-messages />
        @endunless

        <!-- Page Content -->
        <main class="flex-1">
            {{ $slot }}
        </main>
        @unless($isModalFrame)
            <x-dashboard-footer />
        @endunless
    </div>
</body>
</html>
