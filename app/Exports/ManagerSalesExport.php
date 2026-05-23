<?php

namespace App\Exports;

use App\Models\Sale;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ManagerSalesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents, WithColumnWidths
{
    protected $managerId;
    protected $sellerId;
    protected $dateFrom;
    protected $dateTo;
    protected $sales;

    public function __construct($managerId, $sellerId = null, $dateFrom = null, $dateTo = null)
    {
        $this->managerId = $managerId;
        $this->sellerId = $sellerId;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    public function collection()
    {
        return $this->sales();
    }

    protected function sales()
    {
        $sellerIds = User::role('seller')
            ->where('created_by', $this->managerId)
            ->pluck('id');

        $query = Sale::with(['seller', 'items.product'])
            ->whereIn('seller_id', $sellerIds);

        if ($this->sellerId) {
            $query->where('seller_id', $this->sellerId);
        }

        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        return $this->sales ??= $query->latest()->get();
    }

    public function headings(): array
    {
        $sales = $this->sales();
        $manager = User::find($this->managerId);
        $total = $sales->sum('total');
        $cashTotal = $sales->where('payment_method', 'cash')->sum('total');
        $mobileTotal = $sales->whereIn('payment_method', ['card', 'mobile_money'])->sum('total');
        $itemsCount = $sales->sum(fn ($sale) => $sale->items->count());

        return [
            [],
            ['', 'SmartStore', '', '', 'Rapport des ventes'],
            [],
            ['', 'Manager', $manager->name ?? 'N/A', 'Genere le', now()->format('d/m/Y H:i'), 'Periode', $this->dateFrom || $this->dateTo ? (($this->dateFrom ?? '...') . ' - ' . ($this->dateTo ?? '...')) : 'Toutes'],
            [],
            ['', 'Ventes', 'Montant total', 'Especes', 'Paiements mobiles', 'Articles'],
            ['', $sales->count(), number_format($total, 0, ',', ' ') . ' FCFA', number_format($cashTotal, 0, ',', ' ') . ' FCFA', number_format($mobileTotal, 0, ',', ' ') . ' FCFA', $itemsCount],
            [],
            [],
            [
                '',
                'N° Facture',
                'Vendeur',
                'Email Vendeur',
                'Nombre d\'articles',
                'Total',
                'Methode de paiement',
                'Montant recu',
                'Monnaie rendue',
                'Date de vente',
            ],
        ];
    }

    public function map($sale): array
    {
        return [
            '',
            $sale->invoice_number,
            $sale->seller->name ?? 'N/A',
            $sale->seller->email ?? 'N/A',
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
            2 => ['font' => ['bold' => true, 'size' => 20, 'color' => ['rgb' => 'E8001C']]],
            6 => ['font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '6B7280']]],
            7 => ['font' => ['bold' => true, 'size' => 13]],
            10 => [
                'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '111827']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 2,
            'B' => 24,
            'C' => 18,
            'D' => 26,
            'E' => 16,
            'F' => 18,
            'G' => 18,
            'H' => 18,
            'I' => 18,
            'J' => 18,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                $sheet->freezePane('B11');
                $sheet->getTabColor()->setRGB('E8001C');
                $sheet->mergeCells('B2:C2');
                $sheet->mergeCells('E2:J2');
                $sheet->getStyle('B3:J3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8001C');
                $sheet->getRowDimension(3)->setRowHeight(7);

                $sheet->getStyle('B2:J10')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('E2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('B6:F7')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
                $sheet->getStyle('B6:F7')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E5E7EB');

                $tableRange = 'B10:J' . max($highestRow, 10);
                $sheet->getStyle($tableRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E5E7EB');
                $sheet->getStyle('B10:J10')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                for ($row = 11; $row <= $highestRow; $row++) {
                    if ($row % 2 === 0) {
                        $sheet->getStyle("B{$row}:J{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FAFAFA');
                    }
                }
            },
        ];
    }
}
