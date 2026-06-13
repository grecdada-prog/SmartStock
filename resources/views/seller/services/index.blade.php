@extends('seller.layouts.app')

@section('title', 'Services — Dépôt / Retrait')

@section('content')
<div class="px-4 sm:px-6 lg:px-8" x-data="{
    operation: '{{ old('operation', 'depot') }}',
    amount: '{{ old('amount', '') }}',
    get label() { return this.operation === 'depot' ? 'Dépôt' : 'Retrait' },
    get totalFormatted() {
        const n = parseFloat(this.amount);
        if (!n || n <= 0) return '—';
        return new Intl.NumberFormat('fr-FR').format(n) + ' FCFA';
    }
}">

    <!-- Header + soldes (sticky) -->
    <div class="smartstore-sticky-zone -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="smartstore-sticky-inner">
            <div class="sm:flex sm:items-center sm:justify-between">
                <div class="sm:flex-auto">
                    <h1 class="text-2xl font-semibold text-gray-900">Dépôt / Retrait MOMO</h1>
                    <p class="mt-2 text-sm text-gray-700">Opérations MOMO/OM pour les clients</p>
                </div>
                <div class="mt-4 sm:mt-0">
                    <a href="{{ route('seller.services.history') }}"
                       class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                        <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        Historique
                    </a>
                </div>
            </div>

            <!-- Soldes -->
            <div class="smartstore-sticky-cards grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <p class="text-sm font-medium text-gray-500">Caisse Cash</p>
                        <p class="mt-2 text-3xl font-semibold text-gray-900">{{ number_format($cashBalance, 0, ',', ' ') }} FCFA</p>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <p class="text-sm font-medium text-gray-500">Caisse MOMO/OM</p>
                        <p class="mt-2 text-3xl font-semibold text-gray-900">{{ number_format($mobileMoneyBalance, 0, ',', ' ') }} FCFA</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulaire -->
    <div class="mt-6 max-w-lg">
        <div class="bg-white shadow-sm sm:rounded-lg">
            <div class="px-6 py-5 border-b border-gray-100">
                <h2 class="text-base font-semibold text-gray-900" x-text="label + ' MOMO/OM'"></h2>
            </div>

            <form method="POST" action="{{ route('seller.services.store') }}" class="px-6 py-5 space-y-5">
                @csrf

                @if ($errors->any())
                    <div class="rounded-md bg-red-50 border border-red-200 p-4">
                        <ul class="text-sm text-red-700 space-y-1 list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Type d'opération -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Type d'opération</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="relative flex cursor-pointer rounded-lg border p-4"
                               :class="operation === 'depot' ? 'border-rose-500 bg-rose-50 ring-2 ring-rose-500' : 'border-gray-300 bg-white hover:bg-gray-50'">
                            <input type="radio" name="operation" value="depot" x-model="operation" class="sr-only">
                            <div class="flex flex-col gap-1">
                                <span class="flex items-center gap-2 text-sm font-semibold"
                                      :class="operation === 'depot' ? 'text-rose-700' : 'text-gray-900'">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                    Dépôt
                                </span>
                                <span class="text-xs text-gray-500">Client donne cash<br>Cash ↑ &nbsp;/&nbsp; MOMO ↓</span>
                            </div>
                        </label>

                        <label class="relative flex cursor-pointer rounded-lg border p-4"
                               :class="operation === 'retrait' ? 'border-rose-500 bg-rose-50 ring-2 ring-rose-500' : 'border-gray-300 bg-white hover:bg-gray-50'">
                            <input type="radio" name="operation" value="retrait" x-model="operation" class="sr-only">
                            <div class="flex flex-col gap-1">
                                <span class="flex items-center gap-2 text-sm font-semibold"
                                      :class="operation === 'retrait' ? 'text-rose-700' : 'text-gray-900'">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" /></svg>
                                    Retrait
                                </span>
                                <span class="text-xs text-gray-500">Client veut cash<br>Cash ↓ &nbsp;/&nbsp; MOMO ↑</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Montant -->
                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700">Montant (FCFA)</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <input type="number" id="amount" name="amount" min="1" step="1"
                               x-model="amount" placeholder="Ex : 5000"
                               class="block w-full rounded-md border-gray-300 pr-16 focus:border-rose-500 focus:ring-rose-500 sm:text-sm @error('amount') border-red-300 @enderror"
                               required>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">FCFA</span>
                        </div>
                    </div>
                    @error('amount')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Motif -->
                <div>
                    <label for="reason" class="block text-sm font-medium text-gray-700">
                        Motif <span class="text-gray-400 font-normal">(facultatif)</span>
                    </label>
                    <input type="text" id="reason" name="reason" value="{{ old('reason') }}"
                           maxlength="255" placeholder="Ex : Nom du client"
                           class="mt-1 block w-full rounded-md border-gray-300 focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
                </div>

                <!-- Récapitulatif dynamique -->
                <div class="rounded-lg bg-gray-50 border border-gray-200 p-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Opération</span>
                        <span class="font-semibold text-gray-900" x-text="label"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Montant</span>
                        <span class="font-semibold text-gray-900" x-text="totalFormatted"></span>
                    </div>
                    <div class="border-t border-gray-200 pt-2 space-y-1">
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-500">Caisse Cash</span>
                            <span :class="operation === 'depot' ? 'text-green-600 font-medium' : 'text-red-600 font-medium'"
                                  x-text="operation === 'depot' ? '+ ' + totalFormatted : '− ' + totalFormatted"></span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-500">Caisse MOMO/OM</span>
                            <span :class="operation === 'depot' ? 'text-red-600 font-medium' : 'text-green-600 font-medium'"
                                  x-text="operation === 'depot' ? '− ' + totalFormatted : '+ ' + totalFormatted"></span>
                        </div>
                    </div>
                </div>

                <button type="submit"
                        class="w-full inline-flex justify-center items-center rounded-md border border-transparent bg-rose-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2">
                    Valider l'opération
                </button>
            </form>
        </div>
    </div>

</div>
@endsection
