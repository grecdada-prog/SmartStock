<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport des Ventes</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        .summary {
            background-color: #f3f4f6;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .summary-item {
            display: inline-block;
            margin-right: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #10b981;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .total-row {
            font-weight: bold;
            background-color: #e5e7eb;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <h1>Rapport des Ventes</h1>
    <p><strong>Date d'export:</strong> {{ now()->format('d/m/Y H:i') }}</p>

    <div class="summary">
        <div class="summary-item">
            <strong>Nombre de ventes:</strong> {{ $sales->count() }}
        </div>
        <div class="summary-item">
            <strong>Chiffre d'affaires total:</strong> {{ number_format($totalRevenue, 0, ',', ' ') }} FCFA
        </div>
        <div class="summary-item">
            <strong>Vente moyenne:</strong> {{ number_format($averageSale, 0, ',', ' ') }} FCFA
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Vendeur</th>
                <th>Articles</th>
                <th>Total</th>
                <th>Paiement</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sales as $sale)
                <tr>
                    <td>{{ $sale->id }}</td>
                    <td>{{ $sale->seller->name ?? 'N/A' }}</td>
                    <td>{{ $sale->saleItems->count() }}</td>
                    <td>{{ number_format($sale->total, 0, ',', ' ') }} FCFA</td>
                    <td>{{ number_format($sale->payment_method ?? 0, 0, ',', ' ') }} FCFA</td>
                    <td>{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="3">TOTAL</td>
                <td>{{ number_format($totalRevenue, 0, ',', ' ') }} FCFA</td>
                <td>-</td>
                <td>-</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>Document généré par SmartStock - {{ now()->format('d/m/Y à H:i') }}</p>
    </div>
</body>
</html>
