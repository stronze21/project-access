<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ComplaintReportController extends Controller
{
    public function monthly(Request $request): View
    {
        abort_unless($request->user()?->isAdmin() || $request->user()?->isMayor(), 403);

        $monthInput = $request->string('month')->toString();
        $start = $monthInput !== ''
            ? Carbon::createFromFormat('Y-m', $monthInput)->startOfMonth()
            : Carbon::now()->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $baseQuery = Complaint::query()->whereBetween('complaints.created_at', [$start, $end]);

        $byStatus = (clone $baseQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->orderBy('status')
            ->get();

        $byDepartment = (clone $baseQuery)
            ->selectRaw('departments.name as department_name, COUNT(complaints.id) as total')
            ->leftJoin('departments', 'departments.id', '=', 'complaints.assigned_department_id')
            ->groupBy('departments.name')
            ->orderByDesc('total')
            ->get();

        $byCategory = (clone $baseQuery)
            ->selectRaw('complaint_categories.name as category_name, COUNT(complaints.id) as total')
            ->join('complaint_categories', 'complaint_categories.id', '=', 'complaints.category_id')
            ->groupBy('complaint_categories.name')
            ->orderByDesc('total')
            ->get();

        $avgResolutionHours = Complaint::query()
            ->whereBetween('complaints.closed_at', [$start, $end])
            ->whereNotNull('complaints.closed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, complaints.created_at, complaints.closed_at)) as avg_hours')
            ->value('avg_hours');

        return view('complaints.reports.monthly', [
            'start' => $start,
            'end' => $end,
            'byStatus' => $byStatus,
            'byDepartment' => $byDepartment,
            'byCategory' => $byCategory,
            'avgResolutionHours' => $avgResolutionHours !== null ? round((float) $avgResolutionHours, 2) : null,
        ]);
    }
}
