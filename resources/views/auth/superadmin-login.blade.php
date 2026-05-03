<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Super Admin - SmartStock</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-900">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-white">
                    SmartStock
                </h2>
                <p class="mt-2 text-center text-sm text-gray-400">
                    Connexion Super Administrateur
                </p>
            </div>

            <div class="bg-white shadow-md rounded-lg px-8 py-8">
                @if (request()->boolean('inactive'))
                    <div class="mb-4 bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded relative" data-auto-dismiss>
                        <span class="block sm:inline">Vous avez ete deconnecte automatiquement pour inactivite.</span>
                        <button type="button" class="absolute top-0 bottom-0 right-0 px-4 py-3" onclick="this.parentElement.remove()">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

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

                @if ($errors->any())
                    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">
                        <ul class="list-disc list-inside text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

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
    </script>

</body>
</html>
