@extends('superadmin.layouts.app')

@section('title', 'Centre des Anomalies')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center sm:justify-between">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Centre des anomalies</h1>
            <p class="mt-2 text-sm text-gray-700">Toutes les anomalies prioritaires du reseau vendeur et gérant au meme endroit.</p>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <div class="overflow-hidden rounded-lg bg-white shadow">
            <div class="p-5">
                <dt class="text-sm font-medium text-gray-500">Total</dt>
                <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ $summary['total'] }}</dd>
            </div>
        </div>
        <div class="overflow-hidden rounded-lg bg-white shadow">
            <div class="p-5">
                <dt class="text-sm font-medium text-gray-500">Critiques</dt>
                <dd class="mt-1 text-3xl font-semibold text-red-600">{{ $summary['critical'] }}</dd>
            </div>
        </div>
        <div class="overflow-hidden rounded-lg bg-white shadow">
            <div class="p-5">
                <dt class="text-sm font-medium text-gray-500">A surveiller</dt>
                <dd class="mt-1 text-3xl font-semibold text-amber-600">{{ $summary['warning'] }}</dd>
            </div>
        </div>
        <div class="overflow-hidden rounded-lg bg-white shadow">
            <div class="p-5">
                <dt class="text-sm font-medium text-gray-500">Informations</dt>
                <dd class="mt-1 text-3xl font-semibold text-blue-600">{{ $summary['info'] }}</dd>
            </div>
        </div>
    </div>

    <div class="mt-6 rounded-lg bg-white p-4 shadow">
        <form method="GET" data-auto-filter action="{{ route('superadmin.anomalies') }}" class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
                <label for="severity" class="block text-sm font-medium text-gray-700">Severite</label>
                <select name="severity" id="severity" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
                    <option value="">Toutes</option>
                    <option value="danger" {{ request('severity') === 'danger' ? 'selected' : '' }}>Critique</option>
                    <option value="warning" {{ request('severity') === 'warning' ? 'selected' : '' }}>A surveiller</option>
                    <option value="info" {{ request('severity') === 'info' ? 'selected' : '' }}>Information</option>
                </select>
            </div>
            <div>
                <label for="type" class="block text-sm font-medium text-gray-700">Type</label>
                <select name="type" id="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
                    <option value="">Tous</option>
                    <option value="pending_cash_closure" {{ request('type') === 'pending_cash_closure' ? 'selected' : '' }}>Caisses en attente</option>
                    <option value="critical_low_stock" {{ request('type') === 'critical_low_stock' ? 'selected' : '' }}>Stock faible</option>
                    <option value="no_online_seller" {{ request('type') === 'no_online_seller' ? 'selected' : '' }}>Vendeurs hors ligne</option>
                    <option value="closure_open_too_long" {{ request('type') === 'closure_open_too_long' ? 'selected' : '' }}>Caisse fermée trop longtemps</option>
                    <option value="inactive_seller_sales" {{ request('type') === 'inactive_seller_sales' ? 'selected' : '' }}>Vente inactive</option>
                </select>
            </div>
            <div>
                <label for="manager_id" class="block text-sm font-medium text-gray-700">Gérant</label>
                <select name="manager_id" id="manager_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
                    <option value="">Tous</option>
                    @foreach($managers as $manager)
                        <option value="{{ $manager->id }}" {{ (string) request('manager_id') === (string) $manager->id ? 'selected' : '' }}>
                            {{ $manager->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    <div class="mt-6 space-y-4">
        @forelse($anomalies as $anomaly)
            @php
                $palette = match($anomaly['severity']) {
                    'danger' => 'border-red-200 bg-red-50',
                    'warning' => 'border-amber-200 bg-amber-50',
                    default => 'border-blue-200 bg-blue-50',
                };
                $badge = match($anomaly['severity']) {
                    'danger' => 'bg-red-100 text-red-800',
                    'warning' => 'bg-amber-100 text-amber-800',
                    default => 'bg-blue-100 text-blue-800',
                };
            @endphp
            <div class="rounded-lg border p-5 shadow-sm {{ $palette }}">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $badge }}">
                                {{ $anomaly['severity'] === 'danger' ? 'Critique' : ($anomaly['severity'] === 'warning' ? 'A surveiller' : 'Info') }}
                            </span>
                            <span class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ str_replace('_', ' ', $anomaly['type']) }}</span>
                        </div>
                        <h3 class="mt-3 text-lg font-semibold text-gray-900">{{ $anomaly['title'] }}</h3>
                        <p class="mt-2 text-sm text-gray-700">{{ $anomaly['message'] }}</p>
                        <div class="mt-3 text-sm text-gray-500">
                            Gérant: <span class="font-medium text-gray-700">{{ $anomaly['manager_name'] ?? 'N/A' }}</span>
                        </div>
                        <div class="mt-2 rounded-md bg-white/70 p-3 text-sm text-gray-700">
                            <span class="font-medium text-gray-900">Action conseillee:</span>
                            {{ $anomaly['recommendation'] }}
                        </div>
                    </div>
                    <div class="shrink-0">
                        <a href="{{ $anomaly['route'] }}" class="inline-flex items-center rounded-md border border-transparent bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-gray-800">
                            {{ $anomaly['cta'] }}
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-lg border border-rose-200 bg-rose-50 p-8 text-center text-rose-800 shadow-sm">
                Aucune anomalie pour ce filtre.
            </div>
        @endforelse
    </div>
</div>
@endsection
