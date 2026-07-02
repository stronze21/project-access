@include('dashboards.role-dashboard', [
    'title' => 'Mayor Dashboard',
    'subtitle' => 'City-level updates and priority concerns.',
    'cards' => [
        ['title' => 'City Snapshot', 'description' => 'View key reports and complaint trends.'],
        ['title' => 'Priority Issues', 'description' => 'Track urgent cases requiring executive action.'],
        ['title' => 'Public Updates', 'description' => 'Review announcements and community notices.'],
    ],
])
