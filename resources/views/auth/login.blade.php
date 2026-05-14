<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Connexion - SmartStore</title>
    <x-favicon />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">
    <x-flash-messages />

    <div class="flex min-h-screen items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
        <div class="w-full max-w-md space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-rose-600">
                    SmartStore
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Connexion Manager / Vendeur
                </p>
            </div>

            <div class="rounded-lg bg-white px-8 py-8 shadow-md">
                <form method="POST" action="{{ route('login') }}" id="loginForm" class="space-y-6">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email"
                               name="email"
                               id="email"
                               required
                               autofocus
                               autocomplete="email"
                               value="{{ old('email') }}"
                               class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-rose-500 focus:outline-none focus:ring-rose-500 @error('email') border-red-500 @enderror">
                        @error('email')
                            <p class="error-text mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Mot de passe</label>
                        <div class="relative mt-1" style="position: relative;">
                            <input type="password"
                                   name="password"
                                   id="password"
                                   required
                                   autocomplete="current-password"
                                   class="block w-full rounded-md border border-gray-300 px-3 py-2 pr-12 shadow-sm focus:border-rose-500 focus:outline-none focus:ring-rose-500 @error('password') border-red-500 @enderror">
                            <button
                                type="button"
                                id="togglePasswordVisibility"
                                class="flex items-center justify-center text-gray-400 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-rose-500"
                                style="position: absolute; right: 0.5rem; top: 50%; width: 2rem; height: 2rem; transform: translateY(-50%);"
                                aria-label="Afficher le mot de passe"
                                aria-pressed="false"
                            >
                                <svg id="passwordEyeIcon" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12zm10 3a3 3 0 100-6 3 3 0 000 6z" />
                                </svg>
                                <svg id="passwordEyeOffIcon" class="hidden h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
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
                               class="h-4 w-4 rounded border-gray-300 text-rose-600 focus:ring-rose-500">
                        <label for="remember" class="ml-2 block text-sm text-gray-900">
                            Se souvenir de moi
                        </label>
                    </div>

                    <button type="submit"
                            id="submitBtn"
                            class="flex w-full justify-center rounded-md border border-transparent bg-rose-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2">
                        Se connecter
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <a href="{{ route('superadmin.login') }}" class="text-sm text-rose-600 hover:text-rose-500">
                        Connexion Super Admin
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        @if(session('lockout_seconds'))
        let seconds = {{ session('lockout_seconds') }};
        const submitBtn = document.getElementById('submitBtn');
        const loginForm = document.getElementById('loginForm');
        const errorMessage = document.querySelector('.error-text');

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
            }
        }, 1000);
        @endif

        const rememberedEmailKey = 'smartstore_remembered_email';
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const togglePasswordVisibility = document.getElementById('togglePasswordVisibility');
        const passwordEyeIcon = document.getElementById('passwordEyeIcon');
        const passwordEyeOffIcon = document.getElementById('passwordEyeOffIcon');
        const rememberInput = document.getElementById('remember');
        const loginFormElement = document.getElementById('loginForm');
        const rememberedEmail = localStorage.getItem(rememberedEmailKey);

        togglePasswordVisibility?.addEventListener('click', () => {
            const shouldShowPassword = passwordInput.type === 'password';

            passwordInput.type = shouldShowPassword ? 'text' : 'password';
            togglePasswordVisibility.setAttribute('aria-label', shouldShowPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
            togglePasswordVisibility.setAttribute('aria-pressed', shouldShowPassword ? 'true' : 'false');
            passwordEyeIcon.classList.toggle('hidden', shouldShowPassword);
            passwordEyeOffIcon.classList.toggle('hidden', !shouldShowPassword);
            if (shouldShowPassword) {
                window.dispatchEvent(new CustomEvent('smartstore:refresh-now', { detail: { force: true } }));
            }
            passwordInput.focus();
        });

        if (rememberedEmail && emailInput && rememberInput && !emailInput.value) {
            emailInput.value = rememberedEmail;
            rememberInput.checked = true;
        }

        let refreshingCsrf = false;

        loginFormElement?.addEventListener('submit', async (event) => {
            if (!refreshingCsrf) {
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
                    const tokenInput = loginFormElement.querySelector('input[name="_token"]');
                    const tokenMeta = document.querySelector('meta[name="csrf-token"]');

                    if (data.token && tokenInput) {
                        tokenInput.value = data.token;
                    }

                    if (data.token && tokenMeta) {
                        tokenMeta.setAttribute('content', data.token);
                    }
                } catch (error) {
                    // If refresh fails, submit normally so Laravel can show the real error.
                }

                loginFormElement.requestSubmit();
                return;
            }

            if (rememberInput.checked && emailInput.value) {
                localStorage.setItem(rememberedEmailKey, emailInput.value);
                return;
            }

            localStorage.removeItem(rememberedEmailKey);
        });
    </script>
</body>
</html>
