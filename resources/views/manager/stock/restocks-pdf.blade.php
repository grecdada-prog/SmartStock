<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Historique des approvisionnements</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #111827; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #d1d5db; padding: 6px; text-align: left; }
        th { background: #ff0033; color: #ffffff; }
        tr:nth-child(even) { background: #f9fafb; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>
    @php
        $period = $dateTo && $dateTo !== $dateFrom
            ? \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') . ' - ' . \Carbon\Carbon::parse($dateTo)->format('d/m/Y')
            : \Carbon\Carbon::parse($dateFrom)->format('d/m/Y');
    @endphp

    <x-smartstore-pdf-logo
        title="Historique des approvisionnements"
        :subtitle="'Manager: ' . $manager->name . ' - Periode: ' . $period . ' - Export: ' . now()->format('d/m/Y H:i')"
    />

    @php($totalValue = $restocks->sum(fn ($movement) => (float) $movement->quantity * (float) ($movement->purchase_price ?? 0)))
    <p class="muted">Total: {{ $restocks->count() }} approvisionnement(s) - Valeur: {{ number_format($totalValue, 0, ',', ' ') }} FCFA</p>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Produit</th>
                <th>Quantite</th>
                <th>Prix achat</th>
                <th>Valeur</th>
                <th>Prix vente</th>
                <th>Lot restant</th>
                <th>Code-barres</th>
            </tr>
        </thead>
        <tbody>
            @forelse($restocks as $movement)
                @php($movementValue = (float) $movement->quantity * (float) ($movement->purchase_price ?? 0))
                <tr>
                    <td>{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $movement->product->name ?? 'N/A' }}</td>
                    <td>+{{ $movement->quantity }} {{ $movement->product->unit ?? '' }}</td>
                    <td>{{ $movement->purchase_price !== null ? number_format($movement->purchase_price, 0, ',', ' ') . ' FCFA' : '-' }}</td>
                    <td>{{ number_format($movementValue, 0, ',', ' ') }} FCFA</td>
                    <td>{{ $movement->selling_price !== null ? number_format($movement->selling_price, 0, ',', ' ') . ' FCFA' : '-' }}</td>
                    <td>{{ $movement->remaining_quantity !== null ? $movement->remaining_quantity . ' ' . ($movement->product->unit ?? '') : '-' }}</td>
                    <td>{{ $movement->product->barcode ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">Aucun approvisionnement trouve pour cette periode.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" style="text-align: right; font-weight: bold;">Total valeur</td>
                <td style="font-weight: bold;">{{ number_format($totalValue, 0, ',', ' ') }} FCFA</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
