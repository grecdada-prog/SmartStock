<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs d'Activité</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #10b981;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        .action-type {
            font-weight: bold;
            color: #1f2937;
        }
    </style>
</head>
<body>
    <h1>Logs d'Activité</h1>
    <p><strong>Date d'export:</strong> {{ now()->format('d/m/Y H:i') }}</p>
    <p><strong>Nombre total d'activités:</strong> {{ $logs->count() }}</p>

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
                    <td class="action-type">{{ $log->action_type }}</td>
                    <td>{{ Str::limit($log->description, 50) }}</td>
                    <td>{{ $log->ip_address ?? 'N/A' }}</td>
                    <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Document généré par SmartStock - {{ now()->format('d/m/Y à H:i') }}</p>
    </div>
</body>
</html>
