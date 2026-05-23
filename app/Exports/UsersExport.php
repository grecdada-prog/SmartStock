<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UsersExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $role;
    protected $status;
    protected $search;

    public function __construct($role = null, $status = null, $search = null)
    {
        $this->role = $role;
        $this->status = $status;
        $this->search = $search;
    }

    public function collection()
    {
        $query = User::with(['roles', 'creator']);

        if ($this->role) {
            $query->role($this->role);
        }

        if ($this->status === 'active') {
            $query->where('is_active', true);
        } elseif ($this->status === 'inactive') {
            $query->where('is_active', false);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        return $query->latest()->get();
    }

    public function headings(): array
    {
        return [
            ['SmartStore'],
            ['Export des utilisateurs'],
            ['Genere le ' . now()->format('d/m/Y H:i')],
            [],
            [
                'ID',
                'Nom',
                'Email',
                'Telephone',
                'Role',
                'Statut',
                'Cree par',
                'Date de creation',
                'Derniere mise a jour',
            ],
        ];
    }

    public function map($user): array
    {
        return [
            $user->id,
            $user->name,
            $user->email,
            $user->phone ?? 'N/A',
            $user->roles->first()->name ?? 'N/A',
            $user->is_active ? 'Actif' : 'Inactif',
            $user->creator->name ?? 'N/A',
            $user->created_at->format('d/m/Y H:i'),
            $user->updated_at->format('d/m/Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => 'FF0033']]],
            2 => ['font' => ['bold' => true, 'size' => 13]],
            5 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FF0033']],
            ],
        ];
    }
}
