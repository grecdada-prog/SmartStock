<?php

namespace App\Exports;

use App\Models\StockMovement;
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

class RestocksExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents, WithColumnWidths
{
    private $restocks;

    public function __construct(
        private int $managerId,
        private string $dateFrom,
        private ?string $dateTo = null
    ) {
    }

    public function collection()
    {
        return $this->restocks();
    }

    private function restocks()
    {
        return $this->restocks ??= StockMovement::with(['product.category', 'user'])
            ->where('type', 'in')
            ->whereHas('product', fn ($query) => $query->where('created_by', $this->managerId))
            ->whereDate('created_at', '>=', $this->dateFrom)
            ->whereDate('created_at', '<=', $this->dateTo ?: $this->dateFrom)
            ->latest()
            ->get();
    }

    public function headings(): array
    {
        return [
            [],
            ['', 'SmartStore', '', '', 'Historique des approvisionnements'],
            [],
            ['', 'Periode', $this->periodLabel(), 'Total', $this->restocks()->count()],
            [],
            [],
            ['', 'Date', 'Produit', 'Quantite', 'Prix achat', 'Valeur', 'Prix vente', 'Lot restant', 'Code-barres'],
        ];
    }

    public function map($movement): array
    {
        $value = (float) $movement->quantity * (float) ($movement->purchase_price ?? 0);

        return [
            '',
            $movement->created_at->format('d/m/Y H:i'),
            $movement->product->name ?? 'N/A',
            '+'.$movement->quantity.' '.($movement->product->unit ?? ''),
            $movement->purchase_price !== null ? number_format($movement->purchase_price, 0, ',', ' ').' FCFA' : '-',
            number_format($value, 0, ',', ' ').' FCFA',
            $movement->selling_price !== null ? number_format($movement->selling_price, 0, ',', ' ').' FCFA' : '-',
            $movement->remaining_quantity !== null ? $movement->remaining_quantity.' '.($movement->product->unit ?? '') : '-',
            $movement->product->barcode ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            2 => ['font' => ['bold' => true, 'size' => 20, 'color' => ['rgb' => 'E8001C']]],
            4 => ['font' => ['bold' => true, 'size' => 11]],
            7 => [
                'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '111827']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 2,
            'B' => 18,
            'C' => 34,
            'D' => 16,
            'E' => 16,
            'F' => 16,
            'G' => 16,
            'H' => 24,
            'I' => 24,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                $sheet->freezePane('B8');
                $sheet->getTabColor()->setRGB('E8001C');
                $sheet->mergeCells('B2:C2');
                $sheet->mergeCells('E2:I2');
                $sheet->getStyle('B3:I3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8001C');
                $sheet->getRowDimension(3)->setRowHeight(7);
                $sheet->getStyle('B2:I7')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('E2:I2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $tableRange = 'B7:I'.max($highestRow, 7);
                $sheet->getStyle($tableRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E5E7EB');
                $sheet->getStyle('B7:I7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                for ($row = 8; $row <= $highestRow; $row++) {
                    if ($row % 2 === 0) {
                        $sheet->getStyle("B{$row}:I{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FAFAFA');
                    }
                }
            },
        ];
    }

    private function periodLabel(): string
    {
        if (! $this->dateTo || $this->dateTo === $this->dateFrom) {
            return date('d/m/Y', strtotime($this->dateFrom));
        }

        return date('d/m/Y', strtotime($this->dateFrom)).' - '.date('d/m/Y', strtotime($this->dateTo));
    }
}
