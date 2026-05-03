<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport des ventes</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #d1d5db; padding: 6px; text-align: left; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h1>Rapport des ventes</h1>
    <p>Manager: {{ $manager->name }} - Export: {{ now()->format('d/m/Y H:i') }}</p>
    <p>
        Ventes: {{ $stats['total_sales'] }} |
        Recette filtree: {{ number_format($stats['filtered_revenue'], 0, ',', ' ') }} FCFA |
        Panier moyen: {{ number_format($stats['average_sale'] ?? 0, 0, ',', ' ') }} FCFA
    </p>

    <table>
        <thead>
            <tr>
                <th>Facture</th>
                <th>Vendeur</th>
                <th>Articles</th>
                <th>Paiement</th>
                <th>Total</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sales as $sale)
                <tr>
                    <td>{{ $sale->invoice_number }}</td>
                    <td>{{ $sale->seller->name ?? '-' }}</td>
                    <td>{{ $sale->items->count() }}</td>
                    <td>{{ $sale->payment_method_label }}</td>
                    <td>{{ number_format($sale->total, 0, ',', ' ') }} FCFA</td>
                    <td>{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
