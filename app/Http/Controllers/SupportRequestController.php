<?php

namespace App\Http\Controllers;

use App\Models\SupportRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportRequestController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'resident_identifier' => 'nullable|string|max:100',
            'resident_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'contact_number' => 'nullable|string|max:30',
            'category' => 'required|in:account,privacy,technical,service-request,emergency,other',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:4000',
        ]);

        $supportRequest = SupportRequest::create([
            ...$validated,
            'source' => 'web-form',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()
            ->route('legal.support')
            ->with('support_status', 'Support request received. Reference number: ' . $supportRequest->reference_number);
    }

    public function index(): View
    {
        $requests = SupportRequest::with('resident')
            ->latest('submitted_at')
            ->paginate(20);

        return view('support-requests.index', [
            'requests' => $requests,
        ]);
    }

    public function update(Request $request, SupportRequest $supportRequest): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:received,reviewing,resolved,closed',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $supportRequest->update([
            ...$validated,
            'resolved_at' => in_array($validated['status'], ['resolved', 'closed'], true) ? now() : null,
        ]);

        return redirect()
            ->route('support-requests.index')
            ->with('status', 'Support request updated.');
    }
}
