<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents, WithColumnWidths, WithCustomCsvSettings
{
    private $products;

    public function __construct(private ?int $managerId = null, private bool $plainCsv = false)
    {
    }

    public function collection()
    {
        return $this->products();
    }

    private function products()
    {
        return $this->products ??= Product::query()
            ->with('category')
            ->when($this->managerId, fn ($query) => $query->where('created_by', $this->managerId))
            ->orderBy('name')
            ->get();
    }

    public function headings(): array
    {
        if ($this->plainCsv) {
            return $this->tableHeadings();
        }

        $products = $this->products();
        $withBarcode = $products->filter(fn ($product) => filled($product->barcode))->count();

        return [
            [],
            ['', 'SmartStore', '', '', 'Export des produits'],
            [],
            ['', 'Genere le', now()->format('d/m/Y H:i'), 'Perimetre', $this->managerId ? 'Produits du gerant' : 'Tous les produits'],
            [],
            ['', 'Produits', 'Avec code-barres'],
            ['', $products->count(), $withBarcode],
            [],
            [],
            ['', ...$this->tableHeadings()],
        ];
    }

    private function tableHeadings(): array
    {
        return [
            'Nom',
            'Code-barres',
            'Description',
            'Unite',
            'Categorie',
            'Seuil de stock',
        ];
    }

    public function map($product): array
    {
        $row = [
            $product->name,
            $product->barcode ?: '-',
            $product->description ?: '-',
            $product->unit ?: '-',
            $product->category?->name ?: '-',
            $product->alert_quantity,
        ];

        return $this->plainCsv ? $row : ['', ...$row];
    }

    public function styles(Worksheet $sheet)
    {
        if ($this->plainCsv) {
            return [];
        }

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
            'B' => 32,
            'C' => 24,
            'D' => 48,
            'E' => 16,
            'F' => 24,
            'G' => 16,
        ];
    }

    public function registerEvents(): array
    {
        if ($this->plainCsv) {
            return [];
        }

        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                $sheet->freezePane('B11');
                $sheet->getTabColor()->setRGB('E8001C');
                $sheet->mergeCells('B2:C2');
                $sheet->mergeCells('E2:E2');
                $sheet->getStyle('B3:G3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8001C');
                $sheet->getRowDimension(3)->setRowHeight(7);
                $sheet->getStyle('B2:G10')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('E2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('B6:C7')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
                $sheet->getStyle('B6:C7')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E5E7EB');

                $tableRange = 'B10:G'.max($highestRow, 10);
                $sheet->getStyle($tableRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E5E7EB');
                $sheet->getStyle('B10:G10')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle('D11:D'.$highestRow)->getAlignment()->setWrapText(true);

                for ($row = 11; $row <= $highestRow; $row++) {
                    if ($row % 2 === 0) {
                        $sheet->getStyle("B{$row}:G{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FAFAFA');
                    }
                }
            },
        ];
    }

    public function getCsvSettings(): array
    {
        return [
            'delimiter' => ';',
            'enclosure' => '"',
            'line_ending' => PHP_EOL,
            'use_bom' => true,
            'output_encoding' => 'UTF-8',
        ];
    }
}
