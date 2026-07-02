<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        $meta = $this->dashboardMeta($user);
        if ($user->isCitizen()) {
            $tickets = Complaint::query()
                ->where('submitted_by_user_id', $user->id)
                ->with(['category:id,name', 'assignedDepartment:id,name', 'assignedOfficer:id,name'])
                ->latest('id')
                ->limit(20)
                ->get();

            return view('dashboards.citizen-tickets', [
                'title' => $meta['title'],
                'subtitle' => $meta['subtitle'],
                'tickets' => $tickets,
            ]);
        }

        $metrics = $this->dashboardMetrics($user);

        return view('dashboards.role-dashboard', [
            'title' => $meta['title'],
            'subtitle' => $meta['subtitle'],
            'cards' => $meta['cards'],
            'stats' => $metrics['stats'],
            'charts' => $metrics['charts'],
            'graphSuggestions' => $meta['graphSuggestions'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function dashboardMeta(User $user): array
    {
        return match ($user->role) {
            User::ROLE_ADMIN,
            User::ROLE_SUPER_ADMIN => [
                'title' => 'Super Admin Dashboard',
                'subtitle' => 'System-wide controls and oversight.',
                'cards' => [],
                'graphSuggestions' => [
                    'Status distribution to monitor queue pressure',
                    '6-month complaint trend to track seasonality',
                    'Average first-response and resolution hours trend',
                    'Division response time per request to compare departments',
                    'Priority mix to spot urgent-case spikes',
                ],
            ],
            User::ROLE_MAYOR => [
                'title' => 'Mayor Dashboard',
                'subtitle' => 'City-level updates and priority concerns.',
                'cards' => [
                    [
                        'title' => 'City Snapshot',
                        'description' => 'View key reports and complaint trends.',
                        'href' => route('complaints.executive.dashboard'),
                    ],
                    [
                        'title' => 'Priority Issues',
                        'description' => 'Track urgent cases requiring executive action.',
                        'href' => route('complaints.manage.index', ['focus' => 'priority_issues']),
                    ],
                    [
                        'title' => 'Public Updates',
                        'description' => 'Review announcements and community notices.',
                        'href' => route('complaints.public.index'),
                    ],
                ],
                'graphSuggestions' => [
                    'Status distribution to monitor city-wide execution',
                    '6-month trend to detect service stress periods',
                    'First-response and resolution time trend by month',
                    'Division response time per request for accountability',
                    'Priority mix to keep focus on urgent cases',
                ],
            ],
            User::ROLE_DEPARTMENT_HEAD => [
                'title' => 'Department Head Dashboard',
                'subtitle' => 'Team workload, status, and departmental actions.',
                'cards' => [
                    ['title' => 'Department Queue', 'description' => 'Review incoming and active concerns.'],
                    ['title' => 'Team Assignments', 'description' => 'Monitor officer assignments and progress.'],
                    ['title' => 'Performance Summary', 'description' => 'Check completion rates and response times.'],
                ],
                'graphSuggestions' => [
                    'Department status mix for workload balancing',
                    'Monthly trend for staffing and scheduling',
                    'Response-time trend to track team turnaround',
                    'Division response time per request vs request volume',
                    'Priority mix for escalation planning',
                ],
            ],
            User::ROLE_ACTION_OFFICER => [
                'title' => 'Action Officer Dashboard',
                'subtitle' => 'Assigned field work and action tracking.',
                'cards' => [
                    [
                        'title' => 'My Assignments',
                        'description' => 'See all concerns assigned to you today.',
                        'href' => route('complaints.manage.index', ['focus' => 'assigned_today']),
                    ],
                    [
                        'title' => 'Pending Updates',
                        'description' => 'Submit status updates for ongoing actions.',
                        'href' => route('complaints.manage.index', ['focus' => 'pending_updates']),
                    ],
                    [
                        'title' => 'Completed Actions',
                        'description' => 'Review recently closed activities.',
                        'href' => route('complaints.manage.index', ['focus' => 'completed_actions']),
                    ],
                ],
                'graphSuggestions' => [
                    'Your assignment status mix',
                    'Monthly output trend for personal productivity',
                    'Average response and completion hours by month',
                    'Division response time for your assigned cases',
                    'Priority mix for task planning',
                ],
            ],
            default => [
                'title' => 'Citizen Dashboard',
                'subtitle' => 'Your reports, updates, and community services.',
                'cards' => [
                    ['title' => 'My Reports', 'description' => 'Track the status of your submitted concerns.'],
                    ['title' => 'Submit Concern', 'description' => 'Create a new concern for city action.'],
                    ['title' => 'Community Updates', 'description' => 'Read recent announcements and advisories.'],
                ],
                'graphSuggestions' => [
                    'Your complaint status distribution',
                    'Your monthly submission trend',
                    'Average response and completion time for your complaints',
                    'Division response time for departments handling your cases',
                    'Priority profile of your submitted concerns',
                ],
            ],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function dashboardMetrics(User $user): array
    {
        if (!Schema::hasTable('complaints')) {
            return [
                'stats' => $this->emptyStats($user),
                'charts' => $this->emptyCharts(),
            ];
        }

        $query = $this->scopedComplaintQuery($user);
        $now = Carbon::now();

        $total = (clone $query)->count();
        $open = (clone $query)
            ->whereIn('status', [Complaint::STATUS_RECEIVED, Complaint::STATUS_ASSIGNED, Complaint::STATUS_IN_PROGRESS])
            ->count();
        $resolved = (clone $query)->where('status', Complaint::STATUS_RESOLVED)->count();
        $closed = (clone $query)->where('status', Complaint::STATUS_CLOSED)->count();
        $overdue = (clone $query)
            ->whereIn('status', [Complaint::STATUS_RECEIVED, Complaint::STATUS_ASSIGNED, Complaint::STATUS_IN_PROGRESS])
            ->whereNotNull('due_resolution_at')
            ->where('due_resolution_at', '<', $now)
            ->count();
        $urgent = (clone $query)->where('priority', Complaint::PRIORITY_URGENT)->count();
        $supportSum = (clone $query)->sum('support_count');

        $statusKeys = config('complaints.workflow.statuses', [
            Complaint::STATUS_RECEIVED,
            Complaint::STATUS_ASSIGNED,
            Complaint::STATUS_IN_PROGRESS,
            Complaint::STATUS_RESOLVED,
            Complaint::STATUS_CLOSED,
        ]);

        $statusLabels = [];
        $statusData = [];
        foreach ($statusKeys as $status) {
            $statusLabels[] = ucwords(str_replace('_', ' ', $status));
            $statusData[] = (clone $query)->where('status', $status)->count();
        }

        $priorityKeys = config('complaints.workflow.priorities', [
            Complaint::PRIORITY_LOW,
            Complaint::PRIORITY_MEDIUM,
            Complaint::PRIORITY_HIGH,
            Complaint::PRIORITY_URGENT,
        ]);

        $priorityLabels = [];
        $priorityData = [];
        foreach ($priorityKeys as $priority) {
            $priorityLabels[] = ucfirst($priority);
            $priorityData[] = (clone $query)->where('priority', $priority)->count();
        }

        $trendLabels = [];
        $trendData = [];
        $firstResponseTrendData = [];
        $resolutionTrendData = [];
        for ($monthsBack = 5; $monthsBack >= 0; $monthsBack--) {
            $start = $now->copy()->subMonths($monthsBack)->startOfMonth();
            $end = $start->copy()->endOfMonth();
            $trendLabels[] = $start->format('M Y');
            $monthlyQuery = (clone $query)->whereBetween('created_at', [$start, $end]);
            $trendData[] = (clone $monthlyQuery)->count();
            $firstResponseTrendData[] = $this->averageFirstResponseHours($monthlyQuery);
            $resolutionTrendData[] = $this->averageResolutionHours($monthlyQuery);
        }

        $avgFirstResponseHours = $this->averageFirstResponseHours($query);
        $avgResolutionHours = $this->averageResolutionHours($query);

        $divisionRows = (clone $query)
            ->leftJoin('departments', 'departments.id', '=', 'complaints.assigned_department_id')
            ->selectRaw('COALESCE(departments.name, "Unassigned") as division_name')
            ->selectRaw('COUNT(complaints.id) as request_count')
            ->selectRaw('AVG(CASE WHEN complaints.first_action_at IS NULL THEN NULL ELSE TIMESTAMPDIFF(MINUTE, complaints.created_at, complaints.first_action_at) END) as avg_first_response_minutes')
            ->selectRaw('AVG(CASE WHEN complaints.resolved_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, complaints.created_at, complaints.resolved_at) WHEN complaints.closed_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, complaints.created_at, complaints.closed_at) ELSE NULL END) as avg_resolution_minutes')
            ->groupByRaw('COALESCE(departments.name, "Unassigned")')
            ->orderByDesc('request_count')
            ->limit(10)
            ->get();

        $divisionLabels = [];
        $divisionFirstResponseData = [];
        $divisionResolutionData = [];
        $divisionRequestCounts = [];

        foreach ($divisionRows as $row) {
            $divisionLabels[] = $row->division_name;
            $divisionFirstResponseData[] = round(((float) ($row->avg_first_response_minutes ?? 0)) / 60, 2);
            $divisionResolutionData[] = round(((float) ($row->avg_resolution_minutes ?? 0)) / 60, 2);
            $divisionRequestCounts[] = (int) ($row->request_count ?? 0);
        }

        $isPersonalDashboard = !$user->isInternalUser();
        $stats = [
            ['label' => $isPersonalDashboard ? 'My Total Cases' : 'Total Cases', 'value' => $total],
            ['label' => $isPersonalDashboard ? 'My Open Cases' : 'Open Cases', 'value' => $open],
            ['label' => $isPersonalDashboard ? 'My Resolved' : 'Resolved', 'value' => $resolved],
            ['label' => $isPersonalDashboard ? 'My Closed' : 'Closed', 'value' => $closed],
            ['label' => $isPersonalDashboard ? 'My Overdue' : 'Overdue', 'value' => $overdue],
        ];

        $stats[] = $user->isCitizen()
            ? ['label' => 'Support Received', 'value' => $supportSum]
            : ['label' => 'Urgent Priority', 'value' => $urgent];

        if (($user->isAdmin() || $user->isMayor()) && Schema::hasTable('sentiment_posts')) {
            $stats[] = [
                'label' => 'Sentiment Posts (7d)',
                'value' => (int) DB::table('sentiment_posts')
                    ->where('created_at', '>=', $now->copy()->subDays(7))
                    ->count(),
            ];
        }

        if (($user->isAdmin() || $user->isMayor()) && Schema::hasTable('sentiment_reports')) {
            $stats[] = [
                'label' => 'Open Sentiment Reports',
                'value' => (int) DB::table('sentiment_reports')
                    ->where('status', 'open')
                    ->count(),
            ];
        }

        return [
            'stats' => $stats,
            'charts' => [
                'status' => [
                    'labels' => $statusLabels,
                    'data' => $statusData,
                ],
                'trend' => [
                    'labels' => $trendLabels,
                    'data' => $trendData,
                ],
                'priority' => [
                    'labels' => $priorityLabels,
                    'data' => $priorityData,
                ],
                'responseTrend' => [
                    'labels' => $trendLabels,
                    'first_action_hours' => $firstResponseTrendData,
                    'resolution_hours' => $resolutionTrendData,
                ],
                'responseSnapshot' => [
                    'labels' => ['First Response', 'Resolution'],
                    'data' => [$avgFirstResponseHours, $avgResolutionHours],
                ],
                'divisionResponse' => [
                    'labels' => $divisionLabels,
                    'first_action_hours' => $divisionFirstResponseData,
                    'resolution_hours' => $divisionResolutionData,
                    'request_counts' => $divisionRequestCounts,
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, int|string>>
     */
    private function emptyStats(User $user): array
    {
        $isPersonalDashboard = !$user->isInternalUser();
        $stats = [
            ['label' => $isPersonalDashboard ? 'My Total Cases' : 'Total Cases', 'value' => 0],
            ['label' => $isPersonalDashboard ? 'My Open Cases' : 'Open Cases', 'value' => 0],
            ['label' => $isPersonalDashboard ? 'My Resolved' : 'Resolved', 'value' => 0],
            ['label' => $isPersonalDashboard ? 'My Closed' : 'Closed', 'value' => 0],
            ['label' => $isPersonalDashboard ? 'My Overdue' : 'Overdue', 'value' => 0],
        ];

        $stats[] = $user->isCitizen()
            ? ['label' => 'Support Received', 'value' => 0]
            : ['label' => 'Urgent Priority', 'value' => 0];

        return $stats;
    }

    /**
     * @return array<string, array<string, array<int, int|string>>>
     */
    private function emptyCharts(): array
    {
        return [
            'status' => [
                'labels' => ['Received', 'Assigned', 'In Progress', 'Resolved', 'Closed'],
                'data' => [0, 0, 0, 0, 0],
            ],
            'trend' => [
                'labels' => [],
                'data' => [],
            ],
            'priority' => [
                'labels' => ['Low', 'Medium', 'High', 'Urgent'],
                'data' => [0, 0, 0, 0],
            ],
            'responseTrend' => [
                'labels' => [],
                'first_action_hours' => [],
                'resolution_hours' => [],
            ],
            'responseSnapshot' => [
                'labels' => ['First Response', 'Resolution'],
                'data' => [0, 0],
            ],
            'divisionResponse' => [
                'labels' => [],
                'first_action_hours' => [],
                'resolution_hours' => [],
                'request_counts' => [],
            ],
        ];
    }

    private function averageFirstResponseHours(Builder $query): float
    {
        $rows = (clone $query)
            ->whereNotNull('first_action_at')
            ->get(['created_at', 'first_action_at']);

        $totalHours = 0.0;
        $count = 0;

        foreach ($rows as $row) {
            if ($row->created_at === null || $row->first_action_at === null) {
                continue;
            }

            $totalHours += max(0, $row->created_at->diffInMinutes($row->first_action_at, false) / 60);
            $count++;
        }

        return $count > 0 ? round($totalHours / $count, 2) : 0.0;
    }

    private function averageResolutionHours(Builder $query): float
    {
        $rows = (clone $query)
            ->where(function (Builder $builder): void {
                $builder
                    ->whereNotNull('resolved_at')
                    ->orWhereNotNull('closed_at');
            })
            ->get(['created_at', 'resolved_at', 'closed_at']);

        $totalHours = 0.0;
        $count = 0;

        foreach ($rows as $row) {
            if ($row->created_at === null) {
                continue;
            }

            $accomplishedAt = $row->resolved_at ?? $row->closed_at;
            if ($accomplishedAt === null) {
                continue;
            }

            $totalHours += max(0, $row->created_at->diffInMinutes($accomplishedAt, false) / 60);
            $count++;
        }

        return $count > 0 ? round($totalHours / $count, 2) : 0.0;
    }

    private function scopedComplaintQuery(User $user): Builder
    {
        $query = Complaint::query();

        if ($user->isDepartmentHead()) {
            $query->where('assigned_department_id', $user->department_id);
        } elseif ($user->isActionOfficer()) {
            $query->where('assigned_officer_id', $user->id);
        } elseif (!$user->isInternalUser()) {
            // Any non-internal account gets strictly personal dashboard data only.
            $query->where('submitted_by_user_id', $user->id);
        }

        return $query;
    }
}
