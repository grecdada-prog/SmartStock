<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Expirée - SmartStock</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-yellow-600 to-orange-900 min-h-screen flex items-center justify-center">
    <div class="text-center text-white px-4">
        <div class="mb-8">
            <svg class="mx-auto h-24 w-24 text-yellow-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h1 class="text-5xl font-bold mb-4">419</h1>
        <h2 class="text-3xl font-bold mb-4">Session Expirée</h2>
        <p class="text-yellow-100 text-lg mb-8">
            Votre session a expiré. Veuillez vous reconnecter pour continuer.
        </p>

        <div class="bg-yellow-800 bg-opacity-50 rounded-lg p-6 mb-8 max-w-md mx-auto">
            <p class="text-yellow-100 text-sm">
                💡 <strong>Conseil :</strong> Pour des raisons de sécurité, les sessions expirent après 120 minutes d'inactivité.
            </p>
        </div>

        <div class="space-y-4">
            <a href="{{ route('login') }}" class="inline-block bg-white text-orange-600 px-8 py-3 rounded-lg font-bold hover:bg-yellow-50 transition">
                Se reconnecter
            </a>
            <br>
            <a href="{{ url('/') }}" class="inline-block bg-orange-500 hover:bg-orange-400 text-white px-8 py-3 rounded-lg font-bold transition">
                Retour à l'accueil
            </a>
        </div>

        <div class="mt-12 text-yellow-100 text-sm">
            <p>Vous serez automatiquement redirigé dans 10 secondes...</p>
        </div>
    </div>

    <script>
        setTimeout(() => {
            window.location.href = "{{ route('login') }}";
        }, 10000);
    </script>
</body>
</html>