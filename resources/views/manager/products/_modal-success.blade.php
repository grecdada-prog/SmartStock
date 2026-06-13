<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modification enregistree</title>
</head>
<body>
    <p>{{ $message }}</p>
    <script>
        window.parent?.postMessage({
            type: 'smartstore:modal-success',
            message: @json($message),
            redirectUrl: @json($redirectUrl),
        }, window.location.origin);
    </script>
</body>
</html>
