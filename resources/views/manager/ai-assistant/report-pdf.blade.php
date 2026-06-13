<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport IA SmartStore</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #111827; }
        h1 { margin: 0 0 4px; font-size: 20px; color: #111827; }
        h2 { margin: 22px 0 8px; font-size: 14px; color: #111827; }
        p { line-height: 1.45; }
        .muted { color: #6b7280; }
        .grid { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .grid td { width: 25%; padding: 8px; border: 1px solid #e5e7eb; vertical-align: top; }
        .kpi-label { color: #6b7280; font-size: 10px; }
        .kpi-value { font-size: 18px; font-weight: bold; color: #111827; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #d1d5db; padding: 6px; text-align: left; vertical-align: top; }
        th { background: #111827; color: #ffffff; }
        tr:nth-child(even) { background: #f9fafb; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 999px; font-size: 10px; font-weight: bold; }
        .critical { background: #fee2e2; color: #991b1b; }
        .high { background: #ffedd5; color: #9a3412; }
        .medium { background: #fef3c7; color: #92400e; }
        .stable { background: #dcfce7; color: #166534; }
        .limits { margin-top: 12px; padding: 8px; border: 1px solid #f59e0b; background: #fffbeb; color: #78350f; }
    </style>
</head>
<body>
    <h1>Rapport IA SmartStore</h1>
    <p class="muted">
        Manager: {{ $manager->name }} |
        Analyse: {{ $analysis['generated_at']->format('d/m/Y H:i') }} |
        Snapshot #{{ $snapshot->id }}
    </p>

    <p>{{ $analysis['narrative'] }}</p>

    <table class="grid">
        <tr>
            <td><div class="kpi-label">Ruptures critiques</div><div class="kpi-value">{{ $analysis['kpis']['critical_stock'] }}</div></td>
            <td><div class="kpi-label">Reapprovisionnements</div><div class="kpi-value">{{ $analysis['kpis']['restock_recommendations'] }}</div></td>
            <td><div class="kpi-label">Lots proches expiration</div><div class="kpi-value">{{ $analysis['kpis']['expiry_alerts'] }}</div></td>
            <td><div class="kpi-label">Anomalies</div><div class="kpi-value">{{ $analysis['kpis']['anomalies'] }}</div></td>
        </tr>
    </table>

    <h2>Methode IA explicable</h2>
    <ul>
        @foreach($analysis['methodology'] as $method)
            <li>{{ $method }}</li>
        @endforeach
    </ul>
    <div class="limits">
        Les recommandations sont consultatives. Leur fiabilite depend de la qualite des ventes enregistrees, de la justesse du stock saisi et de la regularite de l historique.
    </div>

    <h2>Produits recommandes pour action</h2>
    <table>
        <thead>
            <tr>
                <th>Produit</th>
                <th>Stock</th>
                <th>Rupture estimee</th>
                <th>Priorite</th>
                <th>Confiance</th>
                <th>Conseil</th>
            </tr>
        </thead>
        <tbody>
            @forelse($analysis['stock_predictions'] as $prediction)
                <tr>
                    <td>{{ $prediction['product']->name }}</td>
                    <td>{{ $prediction['product']->quantity }} {{ $prediction['product']->unit }}</td>
                    <td>{{ $prediction['days_to_stockout'] === null ? 'Non estimee' : $prediction['days_to_stockout'].' j' }}</td>
                    <td>{{ $prediction['priority_score'] }}/100</td>
                    <td>{{ $prediction['confidence'] }}</td>
                    <td>{{ $prediction['action'] }} Recommandation: {{ $prediction['recommended_quantity'] }} {{ $prediction['product']->unit }}.</td>
                </tr>
            @empty
                <tr><td colspan="6">Aucune recommandation critique.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Anomalies detectees</h2>
    <table>
        <thead>
            <tr>
                <th>Niveau</th>
                <th>Type</th>
                <th>Message</th>
                <th>Recommandation</th>
            </tr>
        </thead>
        <tbody>
            @forelse($analysis['anomalies'] as $anomaly)
                <tr>
                    <td><span class="badge {{ $anomaly['severity'] }}">{{ $anomaly['severity'] }}</span></td>
                    <td>{{ $anomaly['title'] }}</td>
                    <td>{{ $anomaly['message'] }}</td>
                    <td>{{ $anomaly['recommendation'] }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Aucune anomalie detectee.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Lots proches de la peremption</h2>
    <table>
        <thead>
            <tr>
                <th>Produit</th>
                <th>Lot</th>
                <th>Quantite restante</th>
                <th>Expiration</th>
                <th>Signal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($analysis['expiry_alerts'] as $alert)
                <tr>
                    <td>{{ $alert['product']->name }}</td>
                    <td>{{ $alert['movement']->batch_code ?? 'LOT-'.$alert['movement']->id }}</td>
                    <td>{{ $alert['movement']->remaining_quantity }} {{ $alert['product']->unit }}</td>
                    <td>{{ $alert['movement']->expiration_date?->format('d/m/Y') }}</td>
                    <td>{{ $alert['message'] }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Aucun lot proche de la peremption.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
