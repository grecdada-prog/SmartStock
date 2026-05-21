<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Connexion Super Admin - SmartStore</title>
    <x-favicon />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-900">
    <x-flash-messages />

    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-white">
                    SmartStore
                </h2>
                <p class="mt-2 text-center text-sm text-gray-400">
                    Connexion Super Administrateur
                </p>
            </div>

            <div class="bg-white shadow-md rounded-lg px-8 py-8">
                <form method="POST" action="{{ route('superadmin.login') }}" class="space-y-6">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email Super Admin</label>
                        <input type="email" 
                               name="email" 
                               id="email" 
                               required 
                               autofocus
                               value="{{ old('email') }}"
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500 @error('email') border-red-500 @enderror">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Mot de passe</label>
                        <div class="relative mt-1">
                            <input type="password"
                                   name="password"
                                   id="password"
                                   required
                                   class="block w-full rounded-md border border-gray-300 px-3 py-2 pr-11 shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500 @error('password') border-red-500 @enderror">
                            <button type="button"
                                    id="toggleSuperAdminPassword"
                                    class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-md text-gray-400 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-purple-500"
                                    aria-label="Afficher le mot de passe"
                                    aria-pressed="false">
                                <svg id="superAdminEyeIcon" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12zm10 3a3 3 0 100-6 3 3 0 000 6z" />
                                </svg>
                                <svg id="superAdminEyeOffIcon" class="hidden h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                               class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                        <label for="remember" class="ml-2 block text-sm text-gray-900">
                            Se souvenir de moi
                        </label>
                    </div>

                    <button type="submit" 
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                        Se connecter
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <a href="{{ route('login') }}" class="text-sm text-purple-600 hover:text-purple-500">
                        ← Connexion Manager / Vendeur
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
