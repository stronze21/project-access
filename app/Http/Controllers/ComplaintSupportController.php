<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\ComplaintSupport;
use App\Services\ComplaintAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ComplaintSupportController extends Controller
{
    public function __construct(private ComplaintAuditLogger $auditLogger)
    {
    }

    public function store(Request $request, Complaint $complaint): RedirectResponse
    {
        $this->authorize('support', $complaint);

        $support = ComplaintSupport::query()->firstOrCreate([
            'complaint_id' => $complaint->id,
            'user_id' => $request->user()->id,
        ]);

        if ($support->wasRecentlyCreated) {
            $complaint->increment('support_count');
            $this->auditLogger->log('complaint_supported', $complaint, $support, $request->user(), $request);
        }

        return back()->with('status', $support->wasRecentlyCreated ? 'Support added.' : 'Already supported.');
    }
}
