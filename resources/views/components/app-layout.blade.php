@php
    $user = auth()->user();
@endphp

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SmartStock')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
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
                        <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                            @if($user->hasRole('super_admin'))
                                <x-nav-link href="{{ route('superadmin.dashboard') }}" :active="request()->routeIs('superadmin.dashboard')">
                                    Dashboard
                                </x-nav-link>
                                <x-nav-link href="{{ route('superadmin.users.index') }}" :active="request()->routeIs('superadmin.users.*')">
                                    Utilisateurs
                                </x-nav-link>
                                <x-nav-link href="{{ route('superadmin.managers.index') }}" :active="request()->routeIs('superadmin.managers.*')">
                                    Gérants
                                </x-nav-link>
                                <x-nav-link href="{{ route('superadmin.sellers.index') }}" :active="request()->routeIs('superadmin.sellers.*')">
                                    Vendeurs
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
                                    Mes Ventes
                                </x-nav-link>
                                <x-nav-link href="{{ route('seller.products') }}" :active="request()->routeIs('seller.products*')">
                                    Produits
                                </x-nav-link>
                                <x-nav-link href="{{ route('seller.my-stats') }}" :active="request()->routeIs('seller.my-stats')">
                                    Statistiques
                                </x-nav-link>
                            @endif
                        </div>
                    </div>

                    <div class="hidden sm:flex sm:items-center sm:ml-6">
                        <!-- Settings Dropdown -->
                        <div class="ml-3 relative">
                            <x-dropdown align="right" width="48">
                                <x-slot name="trigger">
                                    <button class="flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out">
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

                                    <x-dropdown-link href="{{ route('profile.show') }}">
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
                        <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
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
                        <x-responsive-nav-link href="{{ route('superadmin.managers.index') }}" :active="request()->routeIs('superadmin.managers.*')">Gérants</x-responsive-nav-link>
                        <x-responsive-nav-link href="{{ route('superadmin.sellers.index') }}" :active="request()->routeIs('superadmin.sellers.*')">Vendeurs</x-responsive-nav-link>
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
                        <x-responsive-nav-link href="{{ route('seller.sales.history') }}" :active="request()->routeIs('seller.sales.*')">Mes Ventes</x-responsive-nav-link>
                        <x-responsive-nav-link href="{{ route('seller.products') }}" :active="request()->routeIs('seller.products*')">Produits</x-responsive-nav-link>
                        <x-responsive-nav-link href="{{ route('seller.my-stats') }}" :active="request()->routeIs('seller.my-stats')">Statistiques</x-responsive-nav-link>
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
                        <x-responsive-nav-link href="{{ route('profile.show') }}" :active="request()->routeIs('profile.show')">
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

        <!-- Page Heading -->
        @if (isset($header))
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endif

        <!-- Page Content -->
        <main>
            <!-- Messages flash avec auto-dismiss -->
            @if (session('success'))
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded relative" data-auto-dismiss>
                        <span class="block sm:inline">{{ session('success') }}</span>
                        <button type="button" class="absolute top-0 bottom-0 right-0 px-4 py-3" onclick="this.parentElement.remove()">
                            <svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded relative" data-auto-dismiss>
                        <span class="block sm:inline">{{ session('error') }}</span>
                        <button type="button" class="absolute top-0 bottom-0 right-0 px-4 py-3" onclick="this.parentElement.remove()">
                            <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            @endif

            @if ($errors->any())
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>
</body>
</html>