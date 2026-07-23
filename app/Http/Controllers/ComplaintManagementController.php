<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\ComplaintCategory;
use App\Models\Department;
use App\Models\PublicOfficial;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ComplaintManagementController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user?->isInternalUser(), 403);

        $baseQuery = Complaint::query()
            ->with([
                'category:id,name', 'assignedDepartment:id,name', 'assignedOfficer:id,name',
                'submitter:id,resident_id,name', 'submitter.resident:id,resident_id,first_name,last_name,middle_name,suffix',
            ])
            ->latest('id');

        $this->applyRoleScope($baseQuery, $user);

        $query = clone $baseQuery;
        $focusLabel = $this->applyFocusFilter($query, $request, $user);
        $this->applyFilters($query, $request);

        $now = Carbon::now();
        $openStatuses = [
            Complaint::STATUS_RECEIVED,
            Complaint::STATUS_ASSIGNED,
            Complaint::STATUS_IN_PROGRESS,
        ];

        $queueStats = [
            'total' => (clone $baseQuery)->count(),
            'open' => (clone $baseQuery)->whereIn('status', $openStatuses)->count(),
            'overdue' => (clone $baseQuery)
                ->whereIn('status', $openStatuses)
                ->whereNotNull('due_resolution_at')
                ->where('due_resolution_at', '<', $now)
                ->count(),
            'urgent' => (clone $baseQuery)->where('priority', Complaint::PRIORITY_URGENT)->count(),
            'resolved_this_month' => (clone $baseQuery)
                ->whereNotNull('resolved_at')
                ->whereBetween('resolved_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
                ->count(),
            'filtered' => (clone $query)->count(),
        ];

        $activeFilterLabels = [];
        if ($focusLabel !== null) {
            $activeFilterLabels[] = $focusLabel;
        }

        if ($request->filled('status')) {
            $activeFilterLabels[] = 'Status: '.str_replace('_', ' ', ucfirst($request->string('status')));
        }

        if ($request->filled('priority')) {
            $activeFilterLabels[] = 'Priority: '.ucfirst($request->string('priority'));
        }

        if ($request->filled('department_id')) {
            $departmentName = Department::query()->whereKey($request->integer('department_id'))->value('name');
            if ($departmentName) {
                $activeFilterLabels[] = 'Department: '.$departmentName;
            }
        }

        if ($request->filled('moderation_status')) {
            $activeFilterLabels[] = 'Moderation: '.ucfirst($request->string('moderation_status'));
        }

        $isRestrictedScope = $user->isDepartmentHead() || $user->isActionOfficer();
        $scopeLabel = $user->isDepartmentHead()
            ? 'Department scope'
            : ($user->isActionOfficer() ? 'Assigned-to-me scope' : 'All internal cases');

        return view('complaints.manage.index', [
            'complaints' => $query->paginate(15)->withQueryString(),
            'queueStats' => $queueStats,
            'activeFilterLabels' => $activeFilterLabels,
            'hasActiveFilters' => ! empty($activeFilterLabels),
            'scopeLabel' => $scopeLabel,
            'isRestrictedScope' => $isRestrictedScope,
            'statuses' => config('complaints.workflow.statuses', []),
            'priorities' => config('complaints.workflow.priorities', []),
            'moderationStatuses' => config('complaints.workflow.moderation_statuses', []),
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Request $request, Complaint $complaint): View
    {
        $this->authorize('viewInternal', $complaint);

        $complaint->load([
            'submitter:id,resident_id,name,email',
            'submitter.resident:id,resident_id,first_name,last_name,middle_name,suffix',
            'category:id,name',
            'barangay:id,name',
            'assignedDepartment:id,name',
            'assignedOfficer:id,name,department_id',
            'officials:id,name,position',
            'attachments.uploadedBy:id,name',
            'statusHistories.changedBy:id,name',
            'assignments.assignedBy:id,name',
            'assignments.department:id,name',
            'assignments.officer:id,name',
            'internalNotes.user:id,name',
            'comments.user:id,name',
        ]);

        $officers = User::query()
            ->actionOfficers()
            ->when($complaint->assigned_department_id, fn ($q) => $q->where('department_id', $complaint->assigned_department_id))
            ->orderBy('name')
            ->get(['id', 'name', 'department_id']);

        return view('complaints.manage.show', [
            'complaint' => $complaint,
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'categories' => ComplaintCategory::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'officers' => $officers,
            'officials' => PublicOfficial::query()->where('is_active', true)->orderBy('position')->orderBy('name')->get(['id', 'name', 'position']),
            'statuses' => config('complaints.workflow.statuses', []),
            'priorities' => config('complaints.workflow.priorities', []),
            'moderationStatuses' => config('complaints.workflow.moderation_statuses', []),
        ]);
    }

    private function applyRoleScope(Builder $query, User $user): void
    {
        if ($user->isDepartmentHead()) {
            $query->where('assigned_department_id', $user->department_id);
        }

        if ($user->isActionOfficer()) {
            $query->where('assigned_officer_id', $user->id);
        }
    }

    private function applyFocusFilter(Builder $query, Request $request, User $user): ?string
    {
        $focus = (string) $request->string('focus');

        return match ($focus) {
            'assigned_today' => $this->applyAssignedTodayFocus($query, $user),
            'pending_updates' => $this->applyPendingUpdatesFocus($query),
            'completed_actions' => $this->applyCompletedActionsFocus($query),
            'priority_issues' => $this->applyPriorityIssuesFocus($query),
            default => null,
        };
    }

    private function applyAssignedTodayFocus(Builder $query, User $user): ?string
    {
        if (! $user->isActionOfficer()) {
            return null;
        }

        $start = Carbon::now(Complaint::DISPLAY_TIMEZONE)->startOfDay()->utc();
        $end = Carbon::now(Complaint::DISPLAY_TIMEZONE)->endOfDay()->utc();

        $query->whereHas('assignments', function (Builder $builder) use ($user, $start, $end): void {
            $builder
                ->where('officer_id', $user->id)
                ->whereBetween('created_at', [$start, $end]);
        });

        return 'Focus: My Assignments Today';
    }

    private function applyPendingUpdatesFocus(Builder $query): string
    {
        $query->whereIn('status', [
            Complaint::STATUS_ASSIGNED,
            Complaint::STATUS_IN_PROGRESS,
        ]);

        return 'Focus: Pending Updates';
    }

    private function applyCompletedActionsFocus(Builder $query): string
    {
        $query->whereIn('status', [
            Complaint::STATUS_RESOLVED,
            Complaint::STATUS_CLOSED,
        ]);

        return 'Focus: Completed Actions';
    }

    private function applyPriorityIssuesFocus(Builder $query): string
    {
        $now = Carbon::now();
        $openStatuses = [
            Complaint::STATUS_RECEIVED,
            Complaint::STATUS_ASSIGNED,
            Complaint::STATUS_IN_PROGRESS,
        ];

        $query
            ->whereIn('status', $openStatuses)
            ->where(function (Builder $builder) use ($now): void {
                $builder
                    ->where('priority', Complaint::PRIORITY_URGENT)
                    ->orWhere('is_escalated', true)
                    ->orWhere(function (Builder $overdueBuilder) use ($now): void {
                        $overdueBuilder
                            ->whereNotNull('due_resolution_at')
                            ->where('due_resolution_at', '<', $now);
                    });
            });

        return 'Focus: Priority Issues';
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->string('priority'));
        }

        if ($request->filled('department_id')) {
            $query->where('assigned_department_id', $request->integer('department_id'));
        }

        if ($request->filled('moderation_status')) {
            $query->where('moderation_status', $request->string('moderation_status'));
        }
    }
}
