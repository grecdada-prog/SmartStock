@include('errors.smartstore', [
    'code' => '403',
    'title' => 'Acces refuse',
    'message' => $exception->getMessage() ?: 'Vous n etes pas autorise a acceder a cette page.',
    'hint' => 'Verifiez que vous utilisez le bon compte ou revenez au dashboard.',
])
