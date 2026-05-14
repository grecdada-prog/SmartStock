<?php

namespace App\Exports;

use App\Models\Sale;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $sellerId;
    protected $dateFrom;
    protected $dateTo;
    protected $paymentMethod;
    protected $search;

    public function __construct($sellerId = null, $dateFrom = null, $dateTo = null, $paymentMethod = null, $search = null)
    {
        $this->sellerId = $sellerId;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->paymentMethod = $paymentMethod;
        $this->search = $search;
    }

    public function collection()
    {
        $query = Sale::with(['seller', 'items.product']);

        if ($this->sellerId) {
            $query->where('seller_id', $this->sellerId);
        }

        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        if ($this->paymentMethod) {
            $query->where('payment_method', $this->paymentMethod);
        }

        if ($this->search) {
            $query->where('invoice_number', 'like', '%' . $this->search . '%');
        }

        return $query->latest()->get();
    }

    public function headings(): array
    {
        return [
            'ID Vente',
            'Vendeur',
            'Nombre d\'articles',
            'Total',
            'Paiement',
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
            $sale->items->count(),
            number_format($sale->total, 0, ',', ' ') . ' FCFA',
            $sale->payment_method_label,
            number_format($sale->amount_received ?? 0, 0, ',', ' ') . ' FCFA',
            number_format($sale->change_given ?? 0, 0, ',', ' ') . ' FCFA',
            $sale->created_at->format('d/m/Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
