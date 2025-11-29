<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuration 2FA - SmartStock</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="flex justify-center">
                    <div class="bg-green-600 text-white rounded-full p-4">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                </div>
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                    Configuration de l'authentification à deux facteurs
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    Renforcez la sécurité de votre compte
                </p>
            </div>

            <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    @if (session('success'))
                        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">
                            @foreach ($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    <!-- Étapes de configuration -->
                    <div class="space-y-8">
                        <!-- Étape 1 -->
                        <div>
                            <div class="flex items-center mb-4">
                                <div class="flex-shrink-0 bg-green-600 text-white rounded-full w-8 h-8 flex items-center justify-center font-bold">
                                    1
                                </div>
                                <h3 class="ml-3 text-lg font-medium text-gray-900">
                                    Téléchargez une application d'authentification
                                </h3>
                            </div>
                            <p class="ml-11 text-sm text-gray-600">
                                Installez une application comme <strong>Google Authenticator</strong>, <strong>Authy</strong>, ou <strong>Microsoft Authenticator</strong> sur votre smartphone.
                            </p>
                        </div>

                        <!-- Étape 2 -->
                        <div>
                            <div class="flex items-center mb-4">
                                <div class="flex-shrink-0 bg-green-600 text-white rounded-full w-8 h-8 flex items-center justify-center font-bold">
                                    2
                                </div>
                                <h3 class="ml-3 text-lg font-medium text-gray-900">
                                    Scannez le code QR
                                </h3>
                            </div>
                            <div class="ml-11">
                                <div class="bg-white border-2 border-gray-200 rounded-lg p-6 inline-block">
                                    {!! $QR_Image !!}
                                </div>
                                <p class="mt-4 text-sm text-gray-600">
                                    Ou entrez manuellement ce code :
                                </p>
                                <div class="mt-2 bg-gray-100 px-4 py-3 rounded-md">
                                    <code class="text-sm font-mono text-gray-800">{{ $secret }}</code>
                                </div>
                            </div>
                        </div>

                        <!-- Étape 3 -->
                        <div>
                            <div class="flex items-center mb-4">
                                <div class="flex-shrink-0 bg-green-600 text-white rounded-full w-8 h-8 flex items-center justify-center font-bold">
                                    3
                                </div>
                                <h3 class="ml-3 text-lg font-medium text-gray-900">
                                    Vérifiez le code
                                </h3>
                            </div>
                            <div class="ml-11">
                                <p class="text-sm text-gray-600 mb-4">
                                    Entrez le code à 6 chiffres affiché dans votre application pour activer la 2FA.
                                </p>
                                
                                <form action="{{ route('2fa.enable') }}" method="POST" class="max-w-xs">
                                    @csrf
                                    <div class="mb-4">
                                        <label for="one_time_password" class="block text-sm font-medium text-gray-700 mb-2">
                                            Code de vérification
                                        </label>
                                        <input id="one_time_password" name="one_time_password" type="text" 
                                            pattern="[0-9]{6}" maxlength="6" required autofocus
                                            placeholder="000000"
                                            class="appearance-none block w-full px-3 py-3 border border-gray-300 rounded-md shadow-sm text-center text-2xl tracking-widest placeholder-gray-300 focus:outline-none focus:ring-green-500 focus:border-green-500">
                                    </div>
                                    <button type="submit"
                                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150">
                                        Activer l'authentification à deux facteurs
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer avec avertissement -->
                <div class="bg-yellow-50 border-t border-yellow-200 px-4 py-4 sm:px-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">
                                Important
                            </h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <p>Conservez votre code secret en lieu sûr. Vous en aurez besoin si vous perdez l'accès à votre application d'authentification.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bouton retour -->
            <div class="mt-6 text-center">
                <a href="{{ url()->previous() }}" class="text-sm text-green-600 hover:text-green-500">
                    ← Retour
                </a>
            </div>
        </div>
    </div>

    <script>
        const input = document.getElementById('one_time_password');
        
        input.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html>