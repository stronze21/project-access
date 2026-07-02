<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\ComplaintCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ExecutiveDashboardController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->isMayor(), 403);

        $now = Carbon::now();
        $windowStart = $now->copy()->subDays(30);

        $overdueAcknowledgement = Complaint::query()
            ->notClosed()
            ->where('moderation_status', Complaint::MODERATION_NORMAL)
            ->whereNull('acknowledged_at')
            ->whereNotNull('due_ack_at')
            ->where('due_ack_at', '<', $now)
            ->count();

        $overdueFirstAction = Complaint::query()
            ->notClosed()
            ->where('moderation_status', Complaint::MODERATION_NORMAL)
            ->whereIn('status', [Complaint::STATUS_ASSIGNED, Complaint::STATUS_IN_PROGRESS])
            ->whereNull('first_action_at')
            ->whereNotNull('due_first_action_at')
            ->where('due_first_action_at', '<', $now)
            ->count();

        $overdueResolution = Complaint::query()
            ->where('moderation_status', Complaint::MODERATION_NORMAL)
            ->whereIn('status', [Complaint::STATUS_RECEIVED, Complaint::STATUS_ASSIGNED, Complaint::STATUS_IN_PROGRESS])
            ->whereNotNull('due_resolution_at')
            ->where('due_resolution_at', '<', $now)
            ->count();

        $departmentPerformance = Complaint::query()
            ->selectRaw('departments.name as department_name, COUNT(complaints.id) as total_cases, AVG(TIMESTAMPDIFF(HOUR, complaints.created_at, complaints.closed_at)) as avg_resolution_hours')
            ->join('departments', 'departments.id', '=', 'complaints.assigned_department_id')
            ->whereNotNull('complaints.closed_at')
            ->where('complaints.created_at', '>=', $windowStart)
            ->groupBy('departments.name')
            ->orderByDesc('total_cases')
            ->limit(10)
            ->get();

        $mostSupportedIssues = Complaint::query()
            ->publicListing()
            ->orderByDesc('support_count')
            ->limit(10)
            ->get(['id', 'reference_code', 'title', 'status', 'support_count']);

        $trendingCategories = ComplaintCategory::query()
            ->select('complaint_categories.name')
            ->selectRaw('COUNT(complaints.id) as total')
            ->join('complaints', 'complaints.category_id', '=', 'complaint_categories.id')
            ->where('complaints.created_at', '>=', $windowStart)
            ->groupBy('complaint_categories.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $resolvedWithCitizenFeedback = Complaint::query()
            ->where('is_anonymous_submission', false)
            ->whereNotNull('citizen_confirmed_at')
            ->where('citizen_confirmed_at', '>=', $windowStart)
            ->count();

        $resolvedEligible = Complaint::query()
            ->where('is_anonymous_submission', false)
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '>=', $windowStart)
            ->count();

        $citizenSatisfactionIndex = $resolvedEligible > 0
            ? round(($resolvedWithCitizenFeedback / $resolvedEligible) * 100, 2)
            : null;

        return view('complaints.executive-dashboard', [
            'overdueAcknowledgement' => $overdueAcknowledgement,
            'overdueFirstAction' => $overdueFirstAction,
            'overdueResolution' => $overdueResolution,
            'departmentPerformance' => $departmentPerformance,
            'mostSupportedIssues' => $mostSupportedIssues,
            'trendingCategories' => $trendingCategories,
            'citizenSatisfactionIndex' => $citizenSatisfactionIndex,
            'generatedAt' => $now,
        ]);
    }
}
