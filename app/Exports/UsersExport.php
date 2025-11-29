<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
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
            'ID',
            'Nom',
            'Email',
            'Téléphone',
            'Rôle',
            'Statut',
            'Créé par',
            'Date de création',
            'Dernière mise à jour',
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
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
