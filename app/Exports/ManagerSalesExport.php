<?php

namespace App\Exports;

use App\Models\Sale;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ManagerSalesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $managerId;
    protected $sellerId;
    protected $dateFrom;
    protected $dateTo;

    public function __construct($managerId, $sellerId = null, $dateFrom = null, $dateTo = null)
    {
        $this->managerId = $managerId;
        $this->sellerId = $sellerId;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    public function collection()
    {
        // Récupérer les IDs des vendeurs du manager
        $sellerIds = User::role('seller')
            ->where('created_by', $this->managerId)
            ->pluck('id');

        $query = Sale::with(['seller', 'items.product'])
            ->whereIn('seller_id', $sellerIds);

        // Filtrer par vendeur spécifique si fourni
        if ($this->sellerId) {
            $query->where('seller_id', $this->sellerId);
        }

        // Filtrer par date de début
        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        // Filtrer par date de fin
        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        return $query->latest()->get();
    }

    public function headings(): array
    {
        return [
            'ID Vente',
            'Vendeur',
            'Email Vendeur',
            'Nombre d\'articles',
            'Total',
            'Méthode de paiement',
            'Montant reçu',
            'Monnaie rendue',
            'Date de vente',
        ];
    }

    public function map($sale): array
    {
        return [
            $sale->id,
            $sale->seller->name ?? 'N/A',
            $sale->seller->email ?? 'N/A',
            $sale->items->count(),
            number_format($sale->total, 0, ',', ' ') . ' FCFA',
            ucfirst($sale->payment_method ?? 'N/A'),
            number_format($sale->amount_received ?? 0, 0, ',', ' ') . ' FCFA',
            number_format($sale->change_given ?? 0, 0, ',', ' ') . ' FCFA',
            $sale->created_at->format('d/m/Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F5E9']]],
        ];
    }
}
