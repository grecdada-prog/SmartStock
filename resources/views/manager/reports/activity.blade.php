@extends('manager.layouts.app')

@section('title', 'Rapport activité')

@section('content')
<div class="reports-module">
    <div class="reports-header">
        <div>
            <h1 class="reports-header__title">Rapport d'activité</h1>
            <p class="reports-header__subtitle">Historique de vos actions dans SmartStore.</p>
        </div>
    </div>

    <div class="reports-kpi-grid" style="grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr));">
        <x-reports.kpi-card label="Total" :value="number_format($stats['total_activities'])" />
        <x-reports.kpi-card label="Aujourd'hui" :value="number_format($stats['today_activities'])" />
        <x-reports.kpi-card label="Activités du mois" :value="number_format($stats['this_month_activities'])" />
    </div>

    <form method="GET" action="{{ route('manager.reports.activity') }}" data-auto-filter class="reports-filters" style="grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr));">
        <div class="reports-field">
            <label for="action">Action</label>
            <select name="action" id="action">
                <option value="">Toutes les actions</option>
                @foreach ($actionTypes as $action)
                    <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
                @endforeach
            </select>
        </div>
        <div class="reports-field">
            <label for="date_from">Date début</label>
            <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}">
        </div>
        <div class="reports-field">
            <label for="date_to">Date fin</label>
            <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}">
        </div>
        <div class="reports-filters__footer">
            <a href="{{ route('manager.reports.activity') }}" class="reports-btn">Réinitialiser</a>
        </div>
    </form>

    <x-reports.panel title="Journal d'activité" :meta="$activities->count() . ' entrée(s)'">
        <table class="reports-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Action</th>
                    <th>Description</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($activities as $activity)
                    <tr>
                        <td class="reports-table__muted">{{ $activity->created_at->format('d/m/Y H:i') }}</td>
                        <td class="font-semibold">{{ $activity->action }}</td>
                        <td>{{ $activity->description }}</td>
                        <td class="reports-table__muted">{{ $activity->ip_address ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="reports-empty">Aucune activité trouvée.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-reports.panel>

</div>
@endsection
