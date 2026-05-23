<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Rapport des ventes - Manager</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; margin: 20px; color: #111827; }
        .header { text-align: center; margin-bottom: 26px; border-bottom: 2px solid #ff0033; padding-bottom: 12px; }
        .header p { margin: 5px 0; color: #4b5563; }
        .info-section { margin-bottom: 20px; }
        .info-section h3 { margin: 0 0 10px 0; font-size: 14px; color: #111827; }
        .info-row { display: table; width: 100%; margin-bottom: 5px; }
        .info-label { display: table-cell; width: 150px; font-weight: bold; }
        .info-value { display: table-cell; }
        .stats { display: table; width: 100%; margin-bottom: 30px; border-collapse: collapse; }
        .stat-box { display: table-cell; padding: 15px; text-align: center; background-color: #f9fafb; border: 1px solid #e5e7eb; }
        .stat-value { font-size: 20px; font-weight: bold; color: #ff0033; margin: 5px 0; }
        .stat-label { font-size: 11px; color: #6b7280; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background-color: #ff0033; color: white; padding: 10px 8px; text-align: left; font-size: 11px; font-weight: bold; }
        td { padding: 8px; border-bottom: 1px solid #ddd; font-size: 11px; }
        tr:nth-child(even) { background-color: #f9fafb; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #666; border-top: 1px solid #ddd; padding-top: 10px; }
        .no-data { text-align: center; padding: 40px; color: #999; }
    </style>
</head>
<body>
    <div class="header">
        <x-smartstore-pdf-logo title="Rapport des ventes" :subtitle="'Genere le: ' . now()->format('d/m/Y H:i')" />
        <p><strong>Manager:</strong> {{ $manager->name }}</p>
        <p><strong>Email:</strong> {{ $manager->email }}</p>
    </div>

    @if(count($filters) > 0)
        <div class="info-section">
            <h3>Filtres appliques</h3>
            @if(isset($filters['seller_id']) && $filters['seller_id'])
                <div class="info-row">
                    <div class="info-label">Vendeur:</div>
                    <div class="info-value">ID #{{ $filters['seller_id'] }}</div>
                </div>
            @endif
            @if(isset($filters['date_from']) && $filters['date_from'])
                <div class="info-row">
                    <div class="info-label">Date de debut:</div>
                    <div class="info-value">{{ \Carbon\Carbon::parse($filters['date_from'])->format('d/m/Y') }}</div>
                </div>
            @endif
            @if(isset($filters['date_to']) && $filters['date_to'])
                <div class="info-row">
                    <div class="info-label">Date de fin:</div>
                    <div class="info-value">{{ \Carbon\Carbon::parse($filters['date_to'])->format('d/m/Y') }}</div>
                </div>
            @endif
        </div>
    @endif

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
                <tr style="background-color: #fee2e2; font-weight: bold;">
                    <td colspan="3" class="text-right"><strong>TOTAL:</strong></td>
                    <td class="text-right"><strong>{{ number_format($sales->sum('total'), 0, ',', ' ') }} FCFA</strong></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    @else
        <div class="no-data">
            <p><strong>Aucune vente trouvee</strong></p>
            <p>Il n'y a aucune vente correspondant aux filtres selectionnes.</p>
        </div>
    @endif

    <div class="footer">
        <p>SmartStore - Systeme de gestion de stock</p>
        <p>Document genere automatiquement le {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
