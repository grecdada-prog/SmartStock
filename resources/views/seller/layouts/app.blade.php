<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @unless(request()->boolean('modal'))
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="smartstore-session-timeout" content="{{ config('session.lifetime', 10) * 60 }}">
        <meta name="smartstore-logout-url" content="{{ route('logout') }}">
        <meta name="smartstore-login-url" content="{{ route('login') }}">
    @endunless
    <title>@yield('title', 'Dashboard') - SmartStore</title>
    <x-favicon />
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="font-sans antialiased bg-gray-50">
    @php($isModalFrame = request()->boolean('modal'))
    <div class="flex min-h-screen flex-col" style="{{ $isModalFrame ? '' : 'padding-top: 4rem;' }}">
        @unless($isModalFrame)
        <!-- Navigation -->
        <nav x-data="{ open: false }" class="smartstore-navbar-shell fixed inset-x-0 top-0 z-40 text-gray-800" style="@include('components.navbar-shell-style')">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <!-- Logo -->
                        <div class="shrink-0 flex items-center">
                            <a href="{{ route('seller.dashboard') }}">
                                <x-application-mark class="block h-9 w-auto" />
                            </a>
                        </div>

                        <!-- Navigation Links -->
                        <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                            <x-nav-link href="{{ route('seller.dashboard') }}" :active="request()->routeIs('seller.dashboard')">
                                Dashboard
                            </x-nav-link>
                            <x-nav-link href="{{ route('seller.pos.index') }}" :active="request()->routeIs('seller.pos.*')">
                                Point de Vente
                            </x-nav-link>
                            <x-nav-link href="{{ route('seller.sales.history') }}" :active="request()->routeIs('seller.sales.*')">
                                Historique des ventes
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
                    <x-responsive-nav-link href="{{ route('seller.dashboard') }}" :active="request()->routeIs('seller.dashboard')">
                        Dashboard
                    </x-responsive-nav-link>
                    <x-responsive-nav-link href="{{ route('seller.pos.index') }}" :active="request()->routeIs('seller.pos.*')">
                        Point de Vente
                    </x-responsive-nav-link>
                    <x-responsive-nav-link href="{{ route('seller.sales.history') }}" :active="request()->routeIs('seller.sales.*')">
                        Historique des ventes
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
        <main class="mx-auto w-full max-w-7xl flex-1 py-6 px-4 sm:px-6 lg:px-8">
            @yield('content')
        </main>
        @unless($isModalFrame)
            <x-dashboard-footer />
        @endunless
    </div>
    <script>
        window.SmartStoreAutoFilters = window.SmartStoreAutoFilters || {
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

        document.addEventListener('DOMContentLoaded', () => window.SmartStoreAutoFilters.init());
        window.addEventListener('pageshow', () => window.SmartStoreAutoFilters.init());
    </script>
</body>
</html>
