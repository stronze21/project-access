@include('dashboards.role-dashboard', [
    'title' => 'Citizen Dashboard',
    'subtitle' => 'Your reports, updates, and community services.',
    'cards' => [
        ['title' => 'My Reports', 'description' => 'Track the status of your submitted concerns.'],
        ['title' => 'Submit Concern', 'description' => 'Create a new concern for city action.'],
        ['title' => 'Community Updates', 'description' => 'Read recent announcements and advisories.'],
    ],
])
