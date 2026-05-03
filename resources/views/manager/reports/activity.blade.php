@extends('manager.layouts.app')

@section('title', 'Rapport activite')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900">Rapport activite</h1>
        <p class="mt-1 text-sm text-gray-600">Historique de vos actions dans SmartStock.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="bg-white p-4 rounded-md shadow-sm border"><p class="text-sm text-gray-500">Total</p><p class="mt-1 text-2xl font-semibold">{{ $stats['total_activities'] }}</p></div>
        <div class="bg-white p-4 rounded-md shadow-sm border"><p class="text-sm text-gray-500">Aujourd'hui</p><p class="mt-1 text-2xl font-semibold">{{ $stats['today_activities'] }}</p></div>
        <div class="bg-white p-4 rounded-md shadow-sm border"><p class="text-sm text-gray-500">Activites du mois</p><p class="mt-1 text-2xl font-semibold">{{ $stats['this_month_activities'] }}</p></div>
    </div>

    <form method="GET" data-auto-filter class="bg-white p-4 rounded-md shadow-sm border grid grid-cols-1 gap-4 md:grid-cols-4">
        <select name="action" class="rounded-md border-gray-300">
            <option value="">Toutes les actions</option>
            @foreach($actionTypes as $action)
                <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
            @endforeach
        </select>
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="rounded-md border-gray-300">
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="rounded-md border-gray-300">
    </form>

    <div class="bg-white rounded-md shadow-sm border overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($activities as $activity)
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $activity->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $activity->action }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $activity->description }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $activity->ip_address ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">Aucune activite trouvee.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $activities->links() }}
</div>
@endsection
