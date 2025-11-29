@extends('superadmin.layouts.app')

@section('title', 'Logs d\'Activité')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center sm:justify-between">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Logs d'Activité</h1>
            <p class="mt-2 text-sm text-gray-700">Historique complet des actions dans le système</p>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16">
            <x-export-buttons
                :excelRoute="route('superadmin.activity-logs.export.excel', request()->query())"
                :pdfRoute="route('superadmin.activity-logs.export.pdf', request()->query())" />
        </div>
    </div>

    <!-- Filtres -->
    <div class="mt-6 bg-white shadow rounded-lg p-4">
        <form method="GET" action="{{ route('superadmin.activity-logs') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-5">
            <div>
                <label for="user_id" class="block text-sm font-medium text-gray-700">Utilisateur</label>
                <select name="user_id" id="user_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm">
                    <option value="">Tous</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="action" class="block text-sm font-medium text-gray-700">Action</label>
                <select name="action" id="action" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm">
                    <option value="">Toutes</option>
                    <option value="login" {{ request('action') == 'login' ? 'selected' : '' }}>Connexion</option>
                    <option value="logout" {{ request('action') == 'logout' ? 'selected' : '' }}>Déconnexion</option>
                    <option value="user_created" {{ request('action') == 'user_created' ? 'selected' : '' }}>Création utilisateur</option>
                    <option value="user_updated" {{ request('action') == 'user_updated' ? 'selected' : '' }}>Modification utilisateur</option>
                    <option value="user_deleted" {{ request('action') == 'user_deleted' ? 'selected' : '' }}>Suppression utilisateur</option>
                </select>
            </div>
            <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700">Date début</label>
                <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm">
            </div>
            <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700">Date fin</label>
                <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    Filtrer
                </button>
            </div>
        </form>
    </div>

    <!-- Liste des logs -->
    <div class="mt-8 bg-white shadow overflow-hidden sm:rounded-md">
        <ul class="divide-y divide-gray-200">
            @forelse($logs as $log)
                <li class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="h-10 w-10 rounded-full bg-gray-100 flex items-center justify-center">
                                    <svg class="h-6 w-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-900">
                                    {{ $log->user->name ?? 'Système' }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    {{ $log->description }}
                                </p>
                                <p class="text-xs text-gray-400 mt-1">
                                    IP: {{ $log->ip_address }}
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-900">
                                {{ $log->created_at->format('d/m/Y') }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ $log->created_at->format('H:i:s') }}
                            </p>
                        </div>
                    </div>
                </li>
            @empty
                <li class="px-6 py-8 text-center text-sm text-gray-500">
                    Aucun log trouvé
                </li>
            @endforelse
        </ul>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $logs->links() }}
    </div>
</div>
@endsection