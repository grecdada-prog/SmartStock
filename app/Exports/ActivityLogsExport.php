<?php

namespace App\Exports;

use App\Models\ActivityLog;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ActivityLogsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $userId;
    protected $actionType;
    protected $dateFrom;
    protected $dateTo;

    public function __construct($userId = null, $actionType = null, $dateFrom = null, $dateTo = null)
    {
        $this->userId = $userId;
        $this->actionType = $actionType;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    public function collection()
    {
        $query = ActivityLog::with('user');

        if ($this->userId) {
            $query->where('user_id', $this->userId);
        }

        if ($this->actionType) {
            $query->where('action_type', $this->actionType);
        }

        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        return $query->latest()->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Utilisateur',
            'Type d\'action',
            'Description',
            'Adresse IP',
            'Navigateur',
            'Date',
        ];
    }

    public function map($log): array
    {
        return [
            $log->id,
            $log->user->name ?? 'N/A',
            $log->action_type,
            $log->description,
            $log->ip_address ?? 'N/A',
            $log->user_agent ?? 'N/A',
            $log->created_at->format('d/m/Y H:i:s'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
