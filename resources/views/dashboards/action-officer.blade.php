@include('dashboards.role-dashboard', [
    'title' => 'Action Officer Dashboard',
    'subtitle' => 'Assigned field work and action tracking.',
    'cards' => [
        ['title' => 'My Assignments', 'description' => 'See all concerns assigned to you today.'],
        ['title' => 'Pending Updates', 'description' => 'Submit status updates for ongoing actions.'],
        ['title' => 'Completed Actions', 'description' => 'Review recently closed activities.'],
    ],
])
