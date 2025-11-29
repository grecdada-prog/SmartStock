<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès Refusé - SmartStock</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full text-center">
            <div class="flex justify-center">
                <div class="bg-red-100 text-red-600 rounded-full p-6">
                    <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
            </div>

            <h1 class="mt-6 text-3xl font-extrabold text-gray-900">
                Accès Refusé
            </h1>

            <p class="mt-2 text-sm text-gray-600">
                {{ $exception->getMessage() ?: 'Vous n\'êtes pas autorisé à accéder à cette ressource.' }}
            </p>

            <div class="mt-8 space-y-3">
                @auth
                    <a href="{{ url()->previous() }}" 
                       class="inline-block w-full px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 transition duration-150">
                        Retour
                    </a>
                    
                    @if(auth()->user()->hasRole('super_admin'))
                        <a href="{{ route('superadmin.dashboard') }}" 
                           class="inline-block w-full px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition duration-150">
                            Retour au Dashboard
                        </a>
                    @elseif(auth()->user()->hasRole('manager'))
                        <a href="{{ route('manager.dashboard') }}" 
                           class="inline-block w-full px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition duration-150">
                            Retour au Dashboard
                        </a>
                    @elseif(auth()->user()->hasRole('seller'))
                        <a href="{{ route('seller.dashboard') }}" 
                           class="inline-block w-full px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition duration-150">
                            Retour au Dashboard
                        </a>
                    @endif
                @else
                    <a href="{{ route('login') }}" 
                       class="inline-block w-full px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 transition duration-150">
                        Se connecter
                    </a>
                @endauth
            </div>

            <div class="mt-8 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">
                            Raisons possibles :
                        </h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <ul class="list-disc list-inside space-y-1">
                                <li>Accès direct par URL non autorisé</li>
                                <li>Permissions insuffisantes</li>
                                <li>Session expirée</li>
                                <li>Compte désactivé</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>