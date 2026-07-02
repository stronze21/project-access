@include('dashboards.role-dashboard', [
    'title' => 'Anonymous Dashboard',
    'subtitle' => 'Anonymous reporting tools and updates.',
    'cards' => [
        ['title' => 'Anonymous Reports', 'description' => 'Submit concerns without showing identity.'],
        ['title' => 'Track Ticket', 'description' => 'Check progress using your reference number.'],
        ['title' => 'Safety Tips', 'description' => 'Access quick safety and response guides.'],
    ],
])
