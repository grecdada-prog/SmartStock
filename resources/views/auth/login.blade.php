<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartStock - Connexion</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-600 to-blue-900 min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="w-full max-w-md">
        <!-- Logo/Titre -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center h-12 w-12 rounded-md bg-white">
                <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <h1 class="mt-4 text-3xl font-bold text-white">SmartStock</h1>
            <p class="mt-2 text-blue-100">Gestion de stocks, ventes et approvisionnements</p>
        </div>

        <!-- Sélection du type de compte -->
        <div class="mb-6 space-y-3" id="roleSelection">
            <!-- Bouton Admin/Gérant -->
            <button onclick="showLoginForm('gerant')" class="w-full bg-white hover:bg-blue-50 text-blue-900 font-bold py-3 px-4 rounded-lg transition shadow-lg">
                <div class="flex items-center justify-center">
                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <span>Connexion Gérant/Admin</span>
                </div>
            </button>

            <!-- Bouton Vendeur -->
            <button onclick="showLoginForm('vendeur')" class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-4 rounded-lg transition shadow-lg">
                <div class="flex items-center justify-center">
                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    <span>Connexion Vendeur</span>
                </div>
            </button>
        </div>

        <!-- Carte de connexion (cachée au départ) -->
        <div id="loginFormContainer" style="display: none;">
            <!-- Bouton retour -->
            <button onclick="backToSelection()" class="mb-4 text-blue-100 hover:text-white flex items-center transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Retour
            </button>

            <div class="bg-white rounded-lg shadow-xl p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Connexion</h2>

                <!-- Messages d'erreur -->
                @if ($errors->any())
                    <div class="mb-4 bg-red-50 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                        <button type="button" onclick="this.parentElement.style.display='none'" class="absolute top-0 right-0 p-4">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                        <div class="font-bold mb-2">Erreur de connexion :</div>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li class="text-sm">• {{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Message de succès -->
                @if (session('success'))
                    <div class="mb-4 bg-green-50 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                        <button type="button" onclick="this.parentElement.style.display='none'" class="absolute top-0 right-0 p-4">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                        <div class="font-bold">✓ {{ session('success') }}</div>
                    </div>
                @endif

                <!-- Alerte générale -->
                @if (session('error'))
                    <div class="mb-4 bg-red-50 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded">
                        <button type="button" onclick="this.parentElement.style.display='none'" class="float-right">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                        <strong>⚠️ Erreur :</strong>
                        <p class="text-sm mt-1">{{ session('error') }}</p>
                    </div>
                @endif

                <!-- Formulaire -->
                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    
                    <!-- Type de rôle caché -->
                    <input type="hidden" id="roleType" name="role_type" value="">

                    <!-- Email -->
                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Adresse email</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 transition @error('email') border-red-500 @enderror">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Mot de passe -->
                    <div class="mb-6">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Mot de passe</label>
                        <div class="relative">
                            <input type="password" name="password" id="password" required
                                class="w-full px-4 py-2 pr-12 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 transition @error('password') border-red-500 @enderror">
                            <button type="button" onclick="togglePasswordVisibility()" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none">
                                <svg id="eyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Se souvenir de moi -->
                    <div class="mb-6 flex items-center">
                        <input type="checkbox" name="remember" id="remember" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="remember" class="ml-2 block text-sm text-gray-700">Se souvenir de moi</label>
                    </div>

                    <!-- Bouton de connexion -->
                    <button type="submit" id="submitBtn" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                        Se connecter
                    </button>
                </form>

                <!-- Mot de passe oublié -->
                @if (Route::has('password.request'))
                    <div class="mt-4 text-center">
                        <a href="{{ route('password.request') }}" class="text-sm text-blue-600 hover:text-blue-800">
                            Mot de passe oublié ?
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Infos comptes de test -->
        <div id="testAccounts" class="mt-6 bg-blue-50 rounded-lg p-4 text-blue-900 text-sm">
            <p class="font-bold mb-2">📌 Comptes de test :</p>
            <div class="space-y-2">
                <div>
                    <p class="font-semibold">Gérant:</p>
                    <p class="font-mono text-xs">Email: gerant@smartstock.com</p>
                    <p class="font-mono text-xs">Mot de passe: password</p>
                </div>
                <div class="mt-2">
                    <p class="font-semibold">Vendeur:</p>
                    <p class="font-mono text-xs">Email: vendeur@smartstock.com</p>
                    <p class="font-mono text-xs">Mot de passe: password</p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <p class="mt-8 text-center text-blue-100 text-sm">
            © 2025 SmartStock. Tous droits réservés.
        </p>
    </div>

    <script>
        function showLoginForm(role) {
            document.getElementById('roleSelection').style.display = 'none';
            document.getElementById('loginFormContainer').style.display = 'block';
            document.getElementById('testAccounts').style.display = 'none';
            document.getElementById('roleType').value = role;
            
            // Changer la couleur du bouton selon le rôle
            const submitBtn = document.getElementById('submitBtn');
            if (role === 'gerant') {
                submitBtn.className = 'w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200';
            } else {
                submitBtn.className = 'w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200';
            }
            
            document.getElementById('email').focus();
        }

        function backToSelection() {
            document.getElementById('roleSelection').style.display = 'block';
            document.getElementById('loginFormContainer').style.display = 'none';
            document.getElementById('testAccounts').style.display = 'block';
            document.getElementById('roleType').value = '';
        }

        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>';
            } else {
                passwordInput.type = 'password';
                eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
            }
        }
    </script>
</body>
</html>