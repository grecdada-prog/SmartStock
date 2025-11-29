<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - SmartStock</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    SmartStock
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Connexion à votre compte
                </p>
            </div>

            <div class="bg-white shadow-md rounded-lg px-8 py-8">
                @if (session('message'))
                    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded relative" data-auto-dismiss>
                        <span class="block sm:inline">{{ session('message') }}</span>
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

                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf

                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700">Vous êtes</label>
                        <select name="role" id="role" required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500">
                            <option value="manager" {{ old('role') == 'manager' ? 'selected' : '' }}>Gérant</option>
                            <option value="seller" {{ old('role') == 'seller' ? 'selected' : '' }}>Vendeur</option>
                        </select>
                        @error('role')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" id="email" required autofocus
                               value="{{ old('email') }}"
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 @error('email') border-red-500 @enderror">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Mot de passe</label>
                        <input type="password" name="password" id="password" required
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 @error('password') border-red-500 @enderror">
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input type="checkbox" name="remember" id="remember" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                            <label for="remember" class="ml-2 block text-sm text-gray-900">
                                Se souvenir de moi
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Se connecter
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <a href="{{ route('superadmin.login') }}" class="text-sm text-green-600 hover:text-green-500">
                        Connexion Super Admin
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Script pour gérer la sélection visuelle des rôles
        document.querySelectorAll('input[name="role"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('label').forEach(label => {
                    label.classList.remove('border-green-600', 'bg-green-50');
                    label.classList.add('border-gray-300');
                    label.querySelector('svg').classList.remove('text-green-600');
                    label.querySelector('svg').classList.add('text-gray-400');
                    label.querySelector('span').classList.remove('text-green-600');
                    label.querySelector('span').classList.add('text-gray-700');
                });
                
                this.parentElement.classList.add('border-green-600', 'bg-green-50');
                this.parentElement.classList.remove('border-gray-300');
                this.parentElement.querySelector('svg').classList.add('text-green-600');
                this.parentElement.querySelector('svg').classList.remove('text-gray-400');
                this.parentElement.querySelector('span').classList.add('text-green-600');
                this.parentElement.querySelector('span').classList.remove('text-gray-700');
            });
        });

        // Décompte pour le throttle
        @if(session('lockout_seconds'))
        let seconds = {{ session('lockout_seconds') }};
        const submitBtn = document.getElementById('submitBtn');
        const loginForm = document.getElementById('loginForm');
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
                document.getElementById('error-message').remove();
            }
        }, 1000);
        @endif
    </script>
</body>
</html>


