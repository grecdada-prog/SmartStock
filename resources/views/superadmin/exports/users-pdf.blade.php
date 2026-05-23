<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des utilisateurs</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111827; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #ff0033; color: white; }
        tr:nth-child(even) { background-color: #f9fafb; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #666; }
        .status-active { color: #16a34a; font-weight: bold; }
        .status-inactive { color: #ef4444; font-weight: bold; }
    </style>
</head>
<body>
    <x-smartstore-pdf-logo title="Liste des utilisateurs" :subtitle="'Export: ' . now()->format('d/m/Y H:i')" />
    <p><strong>Date d'export:</strong> {{ now()->format('d/m/Y H:i') }}</p>
    <p><strong>Nombre total:</strong> {{ $users->count() }} utilisateur(s)</p>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Email</th>
                <th>Telephone</th>
                <th>Role</th>
                <th>Statut</th>
                <th>Date creation</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->phone ?? 'N/A' }}</td>
                    <td>{{ $user->roles->first()->name ?? 'N/A' }}</td>
                    <td class="{{ $user->is_active ? 'status-active' : 'status-inactive' }}">
                        {{ $user->is_active ? 'Actif' : 'Inactif' }}
                    </td>
                    <td>{{ $user->created_at->format('d/m/Y') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Document genere par SmartStore - {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
