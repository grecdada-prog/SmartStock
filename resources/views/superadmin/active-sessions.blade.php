<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Sessions Actives') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6" x-data="{ showCleanup: false }">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Toutes les Sessions Actives</h3>
                            <p class="text-sm text-gray-500 mt-1">{{ $activeSessions->count() }} session(s) active(s)</p>
                        </div>
                        <form method="POST"
                              action="{{ route('superadmin.sessions.cleanup') }}"
                              x-on:modal-confirmed-cleanup-sessions.window="$el.submit()">
                            @csrf
                            <button type="button"
                                    @click="$dispatch('open-modal-cleanup-sessions')"
                                    class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition">
                                Nettoyer Sessions Expirées
                            </button>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Utilisateur</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rôle</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Navigateur</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dernière activité</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($activeSessions as $session)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="h-10 w-10 rounded-full bg-rose-100 flex items-center justify-center">
                                                    <span class="text-rose-600 font-medium text-sm">
                                                        {{ strtoupper(substr($session->user->name, 0, 2)) }}
                                                    </span>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">{{ $session->user->name }}</div>
                                                    <div class="text-sm text-gray-500">{{ $session->user->email }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($session->user->hasRole('super_admin'))
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">Super Admin</span>
                                            @elseif($session->user->hasRole('manager'))
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Gérant</span>
                                            @else
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-rose-100 text-rose-800">Vendeur</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $session->ip_address }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                            {{ $session->user_agent }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div class="flex items-center">
                                                <span class="h-2 w-2 rounded-full bg-rose-400 mr-2"></span>
                                                {{ $session->last_activity->diffForHumans() }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            @if($session->user_id !== auth()->id())
                                                <div x-data="{ disconnectSessionId: null }">
                                                    <form method="POST"
                                                          action="{{ route('superadmin.sessions.destroy', $session) }}"
                                                          x-on:modal-confirmed-disconnect-session.window="if(disconnectSessionId === {{ $session->id }}) $el.submit()">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button"
                                                                @click="disconnectSessionId = {{ $session->id }}; $dispatch('open-modal-disconnect-session')"
                                                                class="text-red-600 hover:text-red-900">
                                                            Déconnecter
                                                        </button>
                                                    </form>
                                                </div>
                                            @else
                                                <span class="text-gray-400">Votre session</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                            Aucune session active
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<!-- Modal de confirmation cleanup -->
<x-modal-confirm
    id="cleanup-sessions"
    title="Nettoyer les sessions expirées"
    message="Voulez-vous vraiment déconnecter toutes les sessions expirées ? Cette action affectera tous les utilisateurs dont la session a expiré."
    confirmText="Oui, nettoyer"
    cancelText="Annuler"
    type="warning" />

<!-- Modal de confirmation déconnexion -->
<x-modal-confirm
    id="disconnect-session"
    title="Déconnecter l'utilisateur"
    message="Voulez-vous vraiment déconnecter cet utilisateur ? Il devra se reconnecter pour accéder à nouveau au système."
    confirmText="Oui, déconnecter"
    cancelText="Annuler"
    type="danger" />