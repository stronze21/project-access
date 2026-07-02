@include('dashboards.role-dashboard', [
    'title' => 'Department Head Dashboard',
    'subtitle' => 'Team workload, status, and departmental actions.',
    'cards' => [
        ['title' => 'Department Queue', 'description' => 'Review incoming and active concerns.'],
        ['title' => 'Team Assignments', 'description' => 'Monitor officer assignments and progress.'],
        ['title' => 'Performance Summary', 'description' => 'Check completion rates and response times.'],
    ],
])
