@extends('manager.layouts.app')

@section('title', 'Rapports')

@section('content')
<div class="reports-module">
    <div class="reports-header">
        <div>
            <h1 class="reports-header__title">Rapports</h1>
        </div>
    </div>

    <div class="reports-kpi-grid">
        <x-reports.kpi-card label="Total vendeurs" :value="number_format($stats['total_sellers'])" />
        <x-reports.kpi-card label="Ventes enregistrées" :value="number_format($stats['total_sales'])" />
        <x-reports.kpi-card
            label="Recette du jour"
            :value="number_format($stats['total_current_day_revenue'], 0, ',', ' ') . ' FCFA'"
            :hint="number_format($stats['today_sales']) . ' vente(s)'"
        />
        <x-reports.kpi-card
            label="Solde cash"
            :value="number_format($stats['total_cash_balance'], 0, ',', ' ') . ' FCFA'"
        />
        <x-reports.kpi-card
            label="Paiements mobiles"
            :value="number_format($stats['total_mobile_money_balance'], 0, ',', ' ') . ' FCFA'"
        />
    </div>

    <div class="reports-hub">
        <a href="{{ route('manager.reports.sales') }}" class="reports-hub-card group">
            <h2 class="reports-hub-card__title">Rapport des ventes</h2>
            <p class="reports-hub-card__text">Ventes détaillées, top produits, performance vendeurs et exports.</p>
            <span class="reports-hub-card__link">Consulter</span>
        </a>

        <a href="{{ route('manager.reports.activity') }}" class="reports-hub-card group">
            <h2 class="reports-hub-card__title">Rapport d'activité</h2>
            <p class="reports-hub-card__text">Historique complet de vos actions dans le système.</p>
            <span class="reports-hub-card__link" style="background:#2563eb;">Consulter</span>
        </a>

        <a href="{{ route('manager.reports.stock') }}" class="reports-hub-card group">
            <h2 class="reports-hub-card__title">Rapport de stock</h2>
            <p class="reports-hub-card__text">État de l'inventaire, alertes et valeur totale.</p>
            <span class="reports-hub-card__link" style="background:#ea580c;">Consulter</span>
        </a>
    </div>
</div>
@endsection
