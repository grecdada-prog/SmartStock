<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Stock faible et ruptures</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111827; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #d1d5db; padding: 6px; text-align: left; }
        th { background: #ff0033; color: #ffffff; }
        tr:nth-child(even) { background: #f9fafb; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>
    <x-smartstore-pdf-logo
        title="Stock faible et ruptures"
        :subtitle="'Manager: ' . $manager->name . ' - Export: ' . now()->format('d/m/Y H:i')"
    />

    <p class="muted">
        Total: {{ $products->count() }} produit(s) a surveiller
    </p>

    <table>
        <thead>
            <tr>
                <th>Produit</th>
                <th>Code-barres</th>
                <th>Stock</th>
                <th>Dernier prix d'achat</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $product)
                <tr>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->barcode ?: '-' }}</td>
                    <td>{{ $product->quantity }} {{ $product->unit }}</td>
                    <td>{{ number_format($product->latestPurchaseMovement->purchase_price ?? $product->purchase_price ?? 0, 0, ',', ' ') }} FCFA</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">Aucun produit en stock faible.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
