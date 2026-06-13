@extends('manager.layouts.app')

@section('title', 'Historique IA')

@section('content')
    <div class="py-2">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="mb-6 rounded-lg bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-rose-600">SmartStore AI Assistant</p>
                        <h1 class="mt-1 text-2xl font-bold text-gray-950">Historique des analyses IA</h1>
                        <p class="mt-2 text-sm text-gray-600">Chaque ligne correspond a un instantane conserve pour audit, comparaison et justification des recommandations.</p>
                    </div>
                    <a href="{{ route('manager.ai-assistant.index') }}" class="inline-flex items-center justify-center rounded-md bg-gray-950 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">
                        Nouvelle analyse
                    </a>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Ruptures</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Reappro</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Peremption</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Anomalies</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Alertes IA</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse($snapshots as $snapshot)
                                <tr>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <p class="font-semibold text-gray-950">{{ $snapshot->generated_at->format('d/m/Y H:i') }}</p>
                                        <p class="text-xs text-gray-500">{{ $snapshot->period_days }} jours analyses - cible {{ $snapshot->target_days }} jours</p>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-semibold text-red-600">{{ $snapshot->kpis['critical_stock'] ?? 0 }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $snapshot->kpis['restock_recommendations'] ?? 0 }}</td>
                                    <td class="px-6 py-4 text-sm text-amber-700">{{ $snapshot->kpis['expiry_alerts'] ?? 0 }}</td>
                                    <td class="px-6 py-4 text-sm text-orange-700">{{ $snapshot->kpis['anomalies'] ?? 0 }}</td>
                                    <td class="px-6 py-4 text-sm text-rose-700">{{ $snapshot->kpis['priority_alerts'] ?? 0 }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('manager.ai-assistant.snapshots.show', $snapshot) }}" class="text-sm font-semibold text-rose-600 hover:text-rose-700">
                                            Voir detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-10 text-center text-sm text-gray-500">Aucune analyse IA historisee.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($snapshots->hasPages())
                    <div class="border-t border-gray-100 px-6 py-4">
                        {{ $snapshots->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
