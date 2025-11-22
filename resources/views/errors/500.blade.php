<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erreur Serveur - SmartStock</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-red-600 to-red-900 min-h-screen flex items-center justify-center">
    <div class="text-center text-white px-4">
        <div class="mb-8">
            <svg class="mx-auto h-24 w-24 text-red-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4v2m0 4v2M6.343 3.665c-1.946-1.031-3.526-.632-4.365.997-.839 1.63-.215 4.084 1.731 5.115m0 0c1.946 1.031 3.526.632 4.365-.997m-4.365.997l4.365-.997m0 0c-.338-.204-.677-.388-1.02-.55m0 0C9.806 8.255 9.648 6.487 10.5 5"/>
            </svg>
        </div>
        <h1 class="text-5xl font-bold mb-4">500</h1>
        <h2 class="text-3xl font-bold mb-4">Erreur Serveur</h2>
        <p class="text-red-100 text-lg mb-8">
            Une erreur est survenue. Nos équipes ont été notifiées et travaillent à la résoudre.
        </p>

        <div class="bg-red-800 bg-opacity-50 rounded-lg p-6 mb-8 max-w-md mx-auto">
            <p class="text-red-100 text-sm">
                🛠️ <strong>Conseils :</strong>
            </p>
            <ul class="text-red-100 text-sm mt-2 text-left">
                <li>• Rafraîchissez la page</li>
                <li>• Réessayez plus tard</li>
                <li>• Contactez le support si le problème persiste</li>
            </ul>
        </div>

        <div class="space-y-4">
            <button onclick="location.reload()" class="inline-block bg-white text-red-600 px-8 py-3 rounded-lg font-bold hover:bg-red-50 transition">
                Rafraîchir la page
            </button>
            <br>
            <a href="{{ url('/') }}" class="inline-block bg-red-500 hover:bg-red-400 text-white px-8 py-3 rounded-lg font-bold transition">
                Retour à l'accueil
            </a>
        </div>

        <div class="mt-12 text-red-100 text-sm">
            <p>Identifiant d'erreur: {{ env('APP_NAME') }}-{{ date('YmdHis') }}</p>
        </div>
    </div>
</body>
</html>