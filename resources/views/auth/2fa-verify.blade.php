<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Verification 2FA - SmartStore</title>
    <x-favicon />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    <x-flash-messages />

    <div class="min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <div class="flex justify-center">
                    <div class="bg-rose-600 text-white rounded-full p-4">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                </div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Authentification a deux facteurs
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Entrez le code a 6 chiffres de votre application d'authentification
                </p>
            </div>

            <div class="bg-white py-8 px-4 shadow-lg rounded-lg sm:px-10">
                <form id="twoFactorForm" class="space-y-6" action="{{ route('2fa.verify.post') }}" method="POST">
                    @csrf

                    <div>
                        <label for="one_time_password" class="block text-sm font-medium text-gray-700">
                            Code de verification
                        </label>
                        <div class="mt-1">
                            <input id="one_time_password" name="one_time_password" type="text"
                                inputmode="numeric" autocomplete="one-time-code"
                                pattern="[0-9]{6}" maxlength="6" required autofocus
                                placeholder="000000"
                                class="appearance-none block w-full px-3 py-3 border border-gray-300 rounded-md shadow-sm text-center text-2xl tracking-widest placeholder-gray-300 focus:outline-none focus:ring-rose-500 focus:border-rose-500">
                        </div>
                        <p class="mt-2 text-xs text-gray-500 text-center">
                            Le code change toutes les 30 secondes
                        </p>
                    </div>

                    <div>
                        <button type="submit"
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-rose-600 hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500 transition duration-150">
                            Verifier
                        </button>
                    </div>
                </form>

                <div class="mt-6">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white text-gray-500">
                                Securite renforcee
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">
                            Besoin d'aide ?
                        </h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>Ouvrez votre application d'authentification et entrez le code affiche.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const input = document.getElementById('one_time_password');
        const form = document.getElementById('twoFactorForm');
        let isSubmitting = false;

        async function refreshCsrfToken() {
            try {
                const response = await fetch('{{ route('csrf-token') }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });

                if (!response.ok) {
                    return;
                }

                const data = await response.json();
                const tokenInput = form.querySelector('input[name="_token"]');
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
        }

        form.addEventListener('submit', async function(event) {
            if (isSubmitting) {
                return;
            }

            event.preventDefault();
            isSubmitting = true;
            await refreshCsrfToken();
            form.requestSubmit();
        });

        input.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');

            if (this.value.length === 6) {
                form.requestSubmit();
            }
        });
    </script>
</body>
</html>
