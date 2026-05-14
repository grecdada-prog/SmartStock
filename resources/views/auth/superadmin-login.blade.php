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
                        <input type="password" 
                               name="password" 
                               id="password" 
                               required
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500 @error('password') border-red-500 @enderror">
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
