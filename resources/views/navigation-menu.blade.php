<nav x-data="{ open: false }" class="fixed inset-x-0 top-0 z-40 border-b shadow-sm text-gray-800" style="@include('components.navbar-shell-style')">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    @if(auth()->user()->hasRole('super_admin'))
                        <a href="{{ route('superadmin.dashboard') }}">
                            <x-application-mark class="block h-9 w-auto" />
                        </a>
                    @elseif(auth()->user()->hasRole('manager'))
                        <a href="{{ route('manager.dashboard') }}">
                            <x-application-mark class="block h-9 w-auto" />
                        </a>
                    @elseif(auth()->user()->hasRole('seller'))
                        <a href="{{ route('seller.dashboard') }}">
                            <x-application-mark class="block h-9 w-auto" />
                        </a>
                    @endif
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    @if(auth()->user()->hasRole('super_admin'))
                        <x-nav-link href="{{ route('superadmin.dashboard') }}" :active="request()->routeIs('superadmin.dashboard')">
                            Dashboard
                        </x-nav-link>
                        <x-nav-link href="{{ route('superadmin.statistics') }}" :active="request()->routeIs('superadmin.statistics')">
                            Statistiques
                        </x-nav-link>
                    @elseif(auth()->user()->hasRole('manager'))
                        <x-nav-link href="{{ route('manager.dashboard') }}" :active="request()->routeIs('manager.dashboard')">
                            Dashboard
                        </x-nav-link>
                    @elseif(auth()->user()->hasRole('seller'))
                        <x-nav-link href="{{ route('seller.dashboard') }}" :active="request()->routeIs('seller.dashboard')">
                            Dashboard
                        </x-nav-link>
                        <x-nav-link href="{{ route('seller.pos.index') }}" :active="request()->routeIs('seller.pos.*')">
                            Point de Vente
                        </x-nav-link>
                        <x-nav-link href="{{ route('seller.sales.history') }}" :active="request()->routeIs('seller.sales.*')">
                            Historique des ventes
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <!-- Settings Dropdown -->
                <div class="ms-3 relative">
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                                <button class="flex text-sm border-2 border-transparent rounded-full focus:outline-none focus:border-gray-300 transition">
                                    <img class="h-8 w-8 rounded-full object-cover" src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" />
                                </button>
                            @else
                                <span class="inline-flex rounded-md">
                                    <button type="button" class="inline-flex items-center rounded-md border border-transparent px-3 py-2 text-sm font-medium leading-4 text-gray-600 hover:text-gray-900 focus:outline-none focus:text-gray-900 active:text-gray-900 transition ease-in-out duration-150">
                                        {{ Auth::user()->name }}

                                        <svg class="ms-2 -me-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                        </svg>
                                    </button>
                                </span>
                            @endif
                        </x-slot>

                        <x-slot name="content">
                            <!-- Account Management -->
                            <div class="block px-4 py-2 text-xs text-gray-400">
                                Gérer le compte
                            </div>

                            <x-dropdown-link href="{{ route('account.profile.show') }}">
                                Mon Profil
                            </x-dropdown-link>

                            @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
                                <x-dropdown-link href="{{ route('api-tokens.index') }}">
                                    API Tokens
                                </x-dropdown-link>
                            @endif

                            <div class="border-t border-gray-200"></div>

                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}" x-data>
                                @csrf

                                <x-dropdown-link href="{{ route('logout') }}"
                                         @click.prevent="$root.submit();">
                                    Déconnexion
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
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
            @if(auth()->user()->hasRole('super_admin'))
                <x-responsive-nav-link href="{{ route('superadmin.dashboard') }}" :active="request()->routeIs('superadmin.dashboard')">
                    Dashboard
                </x-responsive-nav-link>
                <x-responsive-nav-link href="{{ route('superadmin.statistics') }}" :active="request()->routeIs('superadmin.statistics')">
                    Statistiques
                </x-responsive-nav-link>
            @elseif(auth()->user()->hasRole('manager'))
                <x-responsive-nav-link href="{{ route('manager.dashboard') }}" :active="request()->routeIs('manager.dashboard')">
                    Dashboard
                </x-responsive-nav-link>
            @elseif(auth()->user()->hasRole('seller'))
                <x-responsive-nav-link href="{{ route('seller.dashboard') }}" :active="request()->routeIs('seller.dashboard')">
                    Dashboard
                </x-responsive-nav-link>
                <x-responsive-nav-link href="{{ route('seller.pos.index') }}" :active="request()->routeIs('seller.pos.*')">
                    Point de Vente
                </x-responsive-nav-link>
                <x-responsive-nav-link href="{{ route('seller.sales.history') }}" :active="request()->routeIs('seller.sales.*')">
                    Historique des ventes
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="flex items-center px-4">
                @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                    <div class="shrink-0 me-3">
                        <img class="h-10 w-10 rounded-full object-cover" src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" />
                    </div>
                @endif

                <div>
                    <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                    <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <!-- Account Management -->
                <x-responsive-nav-link href="{{ route('account.profile.show') }}" :active="request()->routeIs('account.profile.show')">
                    Mon Profil
                </x-responsive-nav-link>

                @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
                    <x-responsive-nav-link href="{{ route('api-tokens.index') }}" :active="request()->routeIs('api-tokens.index')">
                        API Tokens
                    </x-responsive-nav-link>
                @endif

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}" x-data>
                    @csrf

                    <x-responsive-nav-link href="{{ route('logout') }}"
                                   @click.prevent="$root.submit();">
                        Déconnexion
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
