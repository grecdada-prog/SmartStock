<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Connexion Super Admin - SmartStore</title>
    <x-favicon />
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#111216] font-sans">
    <x-flash-messages />

    <div class="min-h-screen flex items-center justify-center py-10 px-4 sm:px-6 lg:px-8">
        <div class="max-w-sm w-full space-y-6">
            <div>
                <div class="flex justify-center">
                    <x-smartstore-logo size="md" tone="dark" />
                </div>
                <p class="mt-1 text-center text-xs font-medium text-gray-500">
                    Connexion Super Administrateur
                </p>
            </div>

            <div class="rounded-xl border border-gray-800 bg-[#1c1f2a] px-7 py-7 shadow-sm">
                <form method="POST" action="{{ route('superadmin.login') }}" class="space-y-6">
                    @csrf

                    <div>
                        <label for="email" class="block text-xs font-bold text-purple-500">Email Super Admin</label>
                        <input type="email" 
                               name="email" 
                               id="email" 
                               required 
                               autofocus
                               value="{{ old('email') }}"
                               class="mt-2 block w-full rounded-md border border-gray-700 bg-[#302f2c] px-3 py-2.5 text-sm font-semibold text-white shadow-sm placeholder:text-gray-400 focus:outline-none focus:ring-1 focus:ring-purple-500 focus:border-purple-500 @error('email') border-red-500 @enderror">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-xs font-bold text-purple-500">Mot de passe</label>
                        <div class="relative mt-1">
                            <input type="password"
                                   name="password"
                                   id="password"
                                   required
                                   class="mt-2 block w-full rounded-md border border-gray-700 bg-[#302f2c] px-3 py-2.5 pr-11 text-sm font-semibold text-white shadow-sm placeholder:text-gray-400 focus:outline-none focus:ring-1 focus:ring-purple-500 focus:border-purple-500 @error('password') border-red-500 @enderror">
                            <button type="button"
                                    id="toggleSuperAdminPassword"
                                    class="flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-purple-500"
                                    style="position: absolute; top: 50%; right: 0.55rem; width: 1.75rem; height: 1.75rem; transform: translateY(-50%);"
                                    aria-label="Afficher le mot de passe"
                                    aria-pressed="false">
                                <svg id="superAdminEyeIcon" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12zm10 3a3 3 0 100-6 3 3 0 000 6z" />
                                </svg>
                                <svg id="superAdminEyeOffIcon" class="hidden h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-5 0-9-4-10-7 0.4-1.2 1.2-2.3 2.2-3.3m3-2A9.8 9.8 0 0112 5c5 0 9 4 10 7a11.7 11.7 0 01-4.1 5.1M3 3l18 18" />
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" 
                               name="remember" 
                               id="remember" 
                               class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-600 bg-[#1c1f2a] rounded">
                        <label for="remember" class="ml-2 block text-xs font-medium text-gray-500">
                            Se souvenir de moi
                        </label>
                    </div>

                    <button type="submit" 
                            class="w-full flex justify-center rounded-md border border-gray-500 bg-transparent px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:border-purple-400 hover:bg-purple-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                        Se connecter
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <a href="{{ route('login') }}" class="text-sm font-semibold text-purple-500 hover:text-purple-400">
                        <- Connexion Manager / Vendeur
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Décompte pour le throttle
        @if(session('lockout_seconds'))
        let seconds = {{ session('lockout_seconds') }};
        const submitBtn = document.querySelector('button[type="submit"]');
        const loginForm = document.querySelector('form');
        const errorMessage = document.querySelector('.error-text');
        
        // Désactiver le formulaire
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
        loginForm.querySelectorAll('input').forEach(input => input.disabled = true);
        
        const countdown = setInterval(() => {
            seconds--;
            if (errorMessage) {
                errorMessage.textContent = `Trop de tentatives de connexion. Veuillez réessayer dans ${seconds} secondes.`;
            }
            
            if (seconds <= 0) {
                clearInterval(countdown);
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                loginForm.querySelectorAll('input').forEach(input => input.disabled = false);
                const errorDiv = document.querySelector('.bg-red-50');
                if (errorDiv) errorDiv.remove();
            }
        }, 1000);
        @endif

        const superAdminPasswordInput = document.getElementById('password');
        const superAdminPasswordToggle = document.getElementById('toggleSuperAdminPassword');
        const superAdminEyeIcon = document.getElementById('superAdminEyeIcon');
        const superAdminEyeOffIcon = document.getElementById('superAdminEyeOffIcon');

        superAdminPasswordToggle?.addEventListener('click', () => {
            const shouldShowPassword = superAdminPasswordInput.type === 'password';

            superAdminPasswordInput.type = shouldShowPassword ? 'text' : 'password';
            superAdminPasswordToggle.setAttribute('aria-label', shouldShowPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
            superAdminPasswordToggle.setAttribute('aria-pressed', shouldShowPassword ? 'true' : 'false');
            superAdminEyeIcon.classList.toggle('hidden', shouldShowPassword);
            superAdminEyeOffIcon.classList.toggle('hidden', !shouldShowPassword);
            superAdminPasswordInput.focus();
        });

        let refreshingCsrf = false;
        const csrfLoginForm = document.querySelector('form');

        csrfLoginForm?.addEventListener('submit', async (event) => {
            if (refreshingCsrf) {
                return;
            }

            event.preventDefault();
            refreshingCsrf = true;

            try {
                const response = await fetch('{{ route('csrf-token') }}', {
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    cache: 'no-store',
                });
                const data = await response.json();
                const tokenInput = csrfLoginForm.querySelector('input[name="_token"]');
                const tokenMeta = document.querySelector('meta[name="csrf-token"]');

                if (data.token && tokenInput) {
                    tokenInput.value = data.token;
                }

                if (data.token && tokenMeta) {
                    tokenMeta.setAttribute('content', data.token);
                }
            } catch (error) {
                // Submit normally if the token refresh endpoint cannot be reached.
            }

            csrfLoginForm.requestSubmit();
        });
    </script>

</body>
</html>
