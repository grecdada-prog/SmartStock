<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Logs d'activite</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #111827; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #ff0033; color: white; }
        tr:nth-child(even) { background-color: #f9fafb; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #666; }
        .action-type { font-weight: bold; color: #1f2937; }
    </style>
</head>
<body>
    <x-smartstore-pdf-logo title="Logs d'activite" :subtitle="'Export: ' . now()->format('d/m/Y H:i')" />
    <p><strong>Date d'export:</strong> {{ now()->format('d/m/Y H:i') }}</p>
    <p><strong>Nombre total d'activites:</strong> {{ $logs->count() }}</p>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Utilisateur</th>
                <th>Action</th>
                <th>Description</th>
                <th>IP</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $log)
                <tr>
                    <td>{{ $log->id }}</td>
                    <td>{{ $log->user->name ?? 'N/A' }}</td>
                    <td class="action-type">{{ $log->action }}</td>
                    <td>{{ Str::limit($log->description, 50) }}</td>
                    <td>{{ $log->ip_address ?? 'N/A' }}</td>
                    <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Document genere par SmartStore - {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
