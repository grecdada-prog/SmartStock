<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès Refusé - SmartStock</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-red-600 to-red-900 min-h-screen flex items-center justify-center">
    <div class="text-center text-white px-4">
        <div class="mb-8">
            <svg class="mx-auto h-24 w-24 text-red-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0 4v2M6.343 3.665c-1.946-1.031-3.526-.632-4.365.997-.839 1.63-.215 4.084 1.731 5.115m0 0c1.946 1.031 3.526.632 4.365-.997m-4.365.997l4.365-.997m0 0c-.338-.204-.677-.388-1.02-.55m0 0C9.806 8.255 9.648 6.487 10.5 5"/>
            </svg>
        </div>
        <h1 class="text-5xl font-bold mb-4">403</h1>
        <h2 class="text-3xl font-bold mb-4">Accès Refusé</h2>
        <p class="text-red-100 text-lg mb-8">
            Vous n'avez pas la permission d'accéder à cette page.
        </p>

        <div class="bg-red-800 bg-opacity-50 rounded-lg p-6 mb-8 max-w-md mx-auto">
            <p class="text-red-100 text-sm mb-2">
                ⚠️ <strong>Important :</strong> Cette tentative d'accès non autorisé a été enregistrée et signalée aux administrateurs.
            </p>
        </div>

        <div class="space-y-4">
            <a href="{{ route('dashboard') }}" class="inline-block bg-white text-red-600 px-8 py-3 rounded-lg font-bold hover:bg-red-50 transition">
                Retour au Dashboard
            </a>
            <br>
            <a href="{{ url('/') }}" class="inline-block bg-red-500 hover:bg-red-400 text-white px-8 py-3 rounded-lg font-bold transition">
                Retour à l'accueil
            </a>
        </div>

        <div class="mt-12 text-red-100 text-sm">
            <p>Si vous pensez que c'est une erreur, contactez l'administrateur du système.</p>
        </div>
    </div>
</body>
</html>