<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @unless(request()->boolean('modal'))
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="smartstock-session-timeout" content="{{ config('session.lifetime', 10) * 60 }}">
        <meta name="smartstock-logout-url" content="{{ route('logout') }}">
        <meta name="smartstock-login-url" content="{{ route('login') }}">
    @endunless
    <title>@yield('title', 'Dashboard') - SmartStock</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="font-sans antialiased bg-gray-50">
    @php($isModalFrame = request()->boolean('modal'))
    <div class="flex min-h-screen flex-col" style="{{ $isModalFrame ? '' : 'padding-top: 4rem;' }}">
        @unless($isModalFrame)
        <!-- Navigation -->
        <nav x-data="{ open: false }" class="fixed inset-x-0 top-0 z-40 border-b shadow-sm text-gray-800" style="@include('components.navbar-shell-style')">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <!-- Logo -->
                        <div class="shrink-0 flex items-center">
                            <a href="{{ route('manager.dashboard') }}">
                                <x-application-mark class="block h-9 w-auto" />
                            </a>
                        </div>

<!-- Navigation Links -->
                        <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
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
                        </div>
                    </div>

                    <div class="hidden sm:flex sm:items-center sm:ml-6">
                        <!-- Settings Dropdown -->
                        <div class="ml-3 relative">
                            <x-dropdown align="right" width="48">
                                <x-slot name="trigger">
                                    <button class="flex items-center text-sm font-medium text-gray-600 hover:text-gray-900 focus:outline-none focus:text-gray-900 transition duration-150 ease-in-out">
                                        <div>{{ Auth::user()->name }}</div>
                                        <div class="ml-1">
                                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </button>
                                </x-slot>

                                <x-slot name="content">
                                    <!-- Account Management -->
                                    <div class="block px-4 py-2 text-xs text-gray-400">
                                        Gérer le compte
                                    </div>

                                    <x-dropdown-link href="{{ route('account.profile.show') }}">
                                        Profil
                                    </x-dropdown-link>

                                    <div class="border-t border-gray-100"></div>

                                    <!-- Authentication -->
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
                        <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-green-50 focus:outline-none focus:bg-green-50 focus:text-gray-700 transition duration-150 ease-in-out">
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
                    <x-responsive-nav-link href="{{ route('manager.dashboard') }}" :active="request()->routeIs('manager.dashboard')">
                        Dashboard
                    </x-responsive-nav-link>
                    <x-responsive-nav-link href="{{ route('manager.sellers.index') }}" :active="request()->routeIs('manager.sellers.*')">
                        Vendeurs
                    </x-responsive-nav-link>
                    <x-responsive-nav-link href="{{ route('manager.categories.index') }}" :active="request()->routeIs('manager.categories.*')">
                        Catégories
                    </x-responsive-nav-link>
                    <x-responsive-nav-link href="{{ route('manager.products.index') }}" :active="request()->routeIs('manager.products.*')">
                        Produits
                    </x-responsive-nav-link>
                    <x-responsive-nav-link href="{{ route('manager.stock.index') }}" :active="request()->routeIs('manager.stock.*')">
                        Stock
                    </x-responsive-nav-link>
                    <x-responsive-nav-link href="{{ route('manager.sales') }}" :active="request()->routeIs('manager.sales*')">
                        Ventes
                    </x-responsive-nav-link>
                    <x-responsive-nav-link href="{{ route('manager.reports.sales') }}" :active="request()->routeIs('manager.reports.*')">
                        Rapports
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

        <!-- Page Content -->
        <main class="mx-auto w-full max-w-7xl flex-1 py-6 px-4 sm:px-6 lg:px-8">
            <!-- Messages flash avec auto-dismiss -->
            @if (session('success'))
                <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded relative" data-auto-dismiss>
                    <span class="block sm:inline">{{ session('success') }}</span>
                    <button type="button" class="absolute top-0 bottom-0 right-0 px-4 py-3" onclick="this.parentElement.remove()">
                        <svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                            <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                        </svg>
                    </button>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded relative" data-auto-dismiss>
                    <span class="block sm:inline">{{ session('error') }}</span>
                    <button type="button" class="absolute top-0 bottom-0 right-0 px-4 py-3" onclick="this.parentElement.remove()">
                        <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                            <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                        </svg>
                    </button>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
        @unless($isModalFrame)
            <x-dashboard-footer />
        @endunless
    </div>
    <script>
        window.SmartStockAutoFilters = window.SmartStockAutoFilters || {
            debounce(callback, delay = 450) {
                let timeoutId;

                return (...args) => {
                    clearTimeout(timeoutId);
                    timeoutId = setTimeout(() => callback(...args), delay);
                };
            },

            init(root = document) {
                root.querySelectorAll('form[data-auto-filter]:not([data-auto-filter-ready])').forEach((form) => {
                    form.dataset.autoFilterReady = 'true';

                    const submit = this.debounce(() => {
                        form.setAttribute('aria-busy', 'true');
                        form.dataset.filtering = 'true';
                        form.classList.add('opacity-60');

                        if (typeof form.requestSubmit === 'function') {
                            form.requestSubmit();
                        } else {
                            form.submit();
                        }
                    }, Number(form.dataset.autoFilterDelay || 450));

                    form.querySelectorAll('input, select, textarea').forEach((field) => {
                        const eventName = field.tagName === 'SELECT' || field.type === 'date' ? 'change' : 'input';
                        field.addEventListener(eventName, submit);
                    });
                });
            },
        };

        document.addEventListener('DOMContentLoaded', () => window.SmartStockAutoFilters.init());
        window.addEventListener('pageshow', () => window.SmartStockAutoFilters.init());
    </script>
</body>
</html>
