<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rapport des Ventes - Manager</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .info-section h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #333;
        }
        .info-row {
            display: flex;
            margin-bottom: 5px;
        }
        .info-label {
            font-weight: bold;
            width: 150px;
        }
        .stats {
            display: table;
            width: 100%;
            margin-bottom: 30px;
            border-collapse: collapse;
        }
        .stat-box {
            display: table-cell;
            padding: 15px;
            text-align: center;
            background-color: #f0f0f0;
            border: 1px solid #ddd;
        }
        .stat-value {
            font-size: 20px;
            font-weight: bold;
            color: #16a34a;
            margin: 5px 0;
        }
        .stat-label {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background-color: #16a34a;
            color: white;
            padding: 10px 8px;
            text-align: left;
            font-size: 11px;
            font-weight: bold;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
            font-size: 11px;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }
    </style>
</head>
<body>
    <!-- En-tête -->
    <div class="header">
        <h1>SmartStock - Rapport des Ventes</h1>
        <p><strong>Manager:</strong> {{ $manager->name }}</p>
        <p><strong>Email:</strong> {{ $manager->email }}</p>
        <p><strong>Généré le:</strong> {{ now()->format('d/m/Y à H:i') }}</p>
    </div>

    <!-- Filtres appliqués -->
    @if(count($filters) > 0)
        <div class="info-section">
            <h3>Filtres appliqués</h3>
            @if(isset($filters['seller_id']) && $filters['seller_id'])
                <div class="info-row">
                    <div class="info-label">Vendeur:</div>
                    <div>ID #{{ $filters['seller_id'] }}</div>
                </div>
            @endif
            @if(isset($filters['date_from']) && $filters['date_from'])
                <div class="info-row">
                    <div class="info-label">Date de début:</div>
                    <div>{{ \Carbon\Carbon::parse($filters['date_from'])->format('d/m/Y') }}</div>
                </div>
            @endif
            @if(isset($filters['date_to']) && $filters['date_to'])
                <div class="info-row">
                    <div class="info-label">Date de fin:</div>
                    <div>{{ \Carbon\Carbon::parse($filters['date_to'])->format('d/m/Y') }}</div>
                </div>
            @endif
        </div>
    @endif

    <!-- Statistiques -->
    <div class="stats">
        <div class="stat-box">
            <div class="stat-label">Nombre de ventes</div>
            <div class="stat-value">{{ number_format($stats['total_sales']) }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Recette filtree</div>
            <div class="stat-value">{{ number_format($stats['filtered_revenue'], 0, ',', ' ') }} FCFA</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Total articles vendus</div>
            <div class="stat-value">{{ number_format($stats['total_items']) }}</div>
        </div>
    </div>

    <!-- Tableau des ventes -->
    @if($sales->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Vendeur</th>
                    <th class="text-center">Articles</th>
                    <th class="text-right">Montant</th>
                    <th>Paiement</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sales as $sale)
                    <tr>
                        <td><strong>#{{ $sale->id }}</strong></td>
                        <td>{{ $sale->seller->name ?? 'N/A' }}</td>
                        <td class="text-center">{{ $sale->items->count() }}</td>
                        <td class="text-right"><strong>{{ number_format($sale->total, 0, ',', ' ') }} FCFA</strong></td>
                        <td>{{ $sale->payment_method_label }}</td>
                        <td>{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background-color: #e8f5e9; font-weight: bold;">
                    <td colspan="3" class="text-right"><strong>TOTAL:</strong></td>
                    <td class="text-right"><strong>{{ number_format($sales->sum('total'), 0, ',', ' ') }} FCFA</strong></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    @else
        <div class="no-data">
            <p><strong>Aucune vente trouvée</strong></p>
            <p>Il n'y a aucune vente correspondant aux filtres sélectionnés.</p>
        </div>
    @endif

    <!-- Pied de page -->
    <div class="footer">
        <p>SmartStock - Système de Gestion de Stock</p>
        <p>Ce document a été généré automatiquement le {{ now()->format('d/m/Y à H:i') }}</p>
    </div>
</body>
</html>
