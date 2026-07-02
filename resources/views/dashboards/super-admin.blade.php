@include('dashboards.role-dashboard', [
    'title' => 'Super Admin Dashboard',
    'subtitle' => 'System-wide controls and oversight.',
    'cards' => [
        ['title' => 'User Management', 'description' => 'Manage accounts, access, and permissions.'],
        ['title' => 'System Health', 'description' => 'Monitor platform status and uptime.'],
        ['title' => 'Audit Logs', 'description' => 'Review recent critical activities.'],
    ],
])
