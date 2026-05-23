@include('errors.smartstore', [
    'code' => '429',
    'title' => 'Trop de tentatives',
    'message' => 'Vous avez effectue trop d actions en peu de temps.',
    'hint' => 'Patientez quelques secondes avant de reessayer. Si vous veniez de cliquer plusieurs fois, une seule tentative suffit.',
])
