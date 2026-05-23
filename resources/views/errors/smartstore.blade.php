@php
    $code = $code ?? 'Erreur';
    $title = $title ?? 'Une erreur est survenue';
    $message = $message ?? 'Nous n avons pas pu terminer cette action.';
    $hint = $hint ?? 'Vous pouvez revenir a la page precedente et reessayer.';
@endphp

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $code }} - SmartStore</title>
    <x-favicon />
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-50">
    <main class="flex min-h-screen items-center justify-center px-4 py-10 sm:px-6 lg:px-8">
        <section class="w-full max-w-lg overflow-hidden rounded-lg bg-white shadow-xl">
            <div class="bg-rose-600 px-6 py-5 text-white">
                <div class="flex items-center gap-3">
                    <div>
                        <x-smartstore-logo size="sm" tone="dark" />
                        <h1 class="text-xl font-semibold">{{ $title }}</h1>
                    </div>
                </div>
            </div>

            <div class="px-6 py-7">
                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-rose-50 text-rose-600">
                        <span class="text-sm font-bold">{{ $code }}</span>
                    </div>
                    <div>
                        <p class="text-base font-semibold text-gray-900">{{ $message }}</p>
                        <p class="mt-2 text-sm leading-6 text-gray-600">{{ $hint }}</p>
                    </div>
                </div>

                <div class="mt-7 grid gap-2 sm:grid-cols-2">
                    <button
                        type="button"
                        onclick="history.length > 1 ? history.back() : window.location.assign('{{ route('login') }}')"
                        class="inline-flex min-h-11 items-center justify-center rounded-md border border-transparent bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2"
                    >
                        Retour
                    </button>

                    <a
                        href="{{ auth()->check() ? route('dashboard') : route('login') }}"
                        class="inline-flex min-h-11 items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2"
                    >
                        {{ auth()->check() ? 'Dashboard' : 'Connexion' }}
                    </a>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
