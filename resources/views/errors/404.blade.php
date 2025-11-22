<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Non Trouvée - SmartStock</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-purple-600 to-purple-900 min-h-screen flex items-center justify-center">
    <div class="text-center text-white px-4">
        <div class="mb-8">
            <svg class="mx-auto h-24 w-24 text-purple-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h1 class="text-6xl font-bold mb-4">404</h1>
        <h2 class="text-3xl font-bold mb-4">Page Non Trouvée</h2>
        <p class="text-purple-100 text-lg mb-8">
            La page que vous recherchez n'existe pas ou a été supprimée.
        </p>

        <div class="bg-purple-800 bg-opacity-50 rounded-lg p-6 mb-8 max-w-md mx-auto">
            <p class="text-purple-100 text-sm">
                🔍 <strong>Vérifiez :</strong> L'URL ou les permissions d'accès à cette ressource.
            </p>
        </div>

        <div class="space-y-4">
            <a href="{{ route('dashboard') }}" class="inline-block bg-white text-purple-600 px-8 py-3 rounded-lg font-bold hover:bg-purple-50 transition">
                Retour au Dashboard
            </a>
            <br>
            <a href="{{ url('/') }}" class="inline-block bg-purple-500 hover:bg-purple-400 text-white px-8 py-3 rounded-lg font-bold transition">
                Retour à l'accueil
            </a>
        </div>

        <div class="mt-12 text-purple-100 text-sm">
            <p>Si vous pensez que c'est une erreur, veuillez contacter le support.</p>
        </div>
    </div>
</body>
</html>