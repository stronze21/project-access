<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\ComplaintAuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ComplaintAuditController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAudit', Complaint::class);

        $baseQuery = ComplaintAuditLog::query();

        $query = ComplaintAuditLog::query()
            ->with(['actor:id,name', 'complaint:id,reference_code,title'])
            ->latest('id');

        $search = trim($request->string('q')->toString());
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('event_type', 'like', '%'.$search.'%')
                    ->orWhereHas('actor', function ($actorQuery) use ($search) {
                        $actorQuery
                            ->where('name', 'like', '%'.$search.'%')
                            ->orWhereHas('roles', fn ($roleQuery) => $roleQuery->where('name', 'like', '%'.$search.'%'));
                    })
                    ->orWhereHas('complaint', function ($complaintQuery) use ($search) {
                        $complaintQuery
                            ->where('reference_code', 'like', '%'.$search.'%')
                            ->orWhere('title', 'like', '%'.$search.'%');
                    });
            });
        }

        $selectedEventType = trim($request->string('event_type')->toString());
        if ($selectedEventType !== '') {
            $query->where('event_type', $selectedEventType);
        }

        $referenceCode = trim($request->string('reference_code')->toString());
        if ($referenceCode !== '') {
            $query->whereHas('complaint', function ($builder) use ($referenceCode) {
                $builder->where('reference_code', 'like', '%'.$referenceCode.'%');
            });
        }

        $dateFrom = trim($request->string('date_from')->toString());
        if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) === 1) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        $dateTo = trim($request->string('date_to')->toString());
        if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo) === 1) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $activeFilterLabels = [];
        if ($search !== '') {
            $activeFilterLabels[] = 'Search: '.$search;
        }

        if ($selectedEventType !== '') {
            $activeFilterLabels[] = 'Event: '.$selectedEventType;
        }

        if ($referenceCode !== '') {
            $activeFilterLabels[] = 'Reference: '.$referenceCode;
        }

        if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) === 1) {
            $activeFilterLabels[] = 'From: '.$dateFrom;
        }

        if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo) === 1) {
            $activeFilterLabels[] = 'To: '.$dateTo;
        }

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'today' => (clone $baseQuery)->whereDate('created_at', now()->toDateString())->count(),
            'event_types' => (clone $baseQuery)->select('event_type')->distinct()->count('event_type'),
            'actors' => (clone $baseQuery)->whereNotNull('actor_user_id')->select('actor_user_id')->distinct()->count('actor_user_id'),
            'with_complaint' => (clone $baseQuery)->whereNotNull('complaint_id')->count(),
            'filtered' => (clone $query)->count(),
        ];

        return view('complaints.audit.index', [
            'logs' => $query->paginate(30)->withQueryString(),
            'eventTypes' => (clone $baseQuery)->select('event_type')->distinct()->orderBy('event_type')->pluck('event_type'),
            'stats' => $stats,
            'activeFilterLabels' => $activeFilterLabels,
            'hasActiveFilters' => ! empty($activeFilterLabels),
        ]);
    }
}
