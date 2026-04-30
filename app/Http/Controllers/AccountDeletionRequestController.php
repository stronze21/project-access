<?php

namespace App\Http\Controllers;

use App\Models\AccountDeletionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountDeletionRequestController extends Controller
{
    public function privacyPolicy(): View
    {
        return view('legal.privacy-policy');
    }

    public function terms(): View
    {
        return view('legal.terms');
    }

    public function support(): View
    {
        return view('legal.support');
    }

    public function create(): View
    {
        return view('legal.account-deletion');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'resident_identifier' => 'nullable|string|max:100',
            'resident_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'contact_number' => 'nullable|string|max:30',
            'reason' => 'nullable|string|max:2000',
            'requested_action' => 'required|in:delete-account-and-data,delete-app-data-only',
            'retention_acknowledged' => 'accepted',
        ]);

        $deletionRequest = AccountDeletionRequest::create([
            ...$validated,
            'retention_acknowledged' => true,
            'source' => 'web-form',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()
            ->route('account-deletion.create')
            ->with('status', 'Request received. Reference number: ' . $deletionRequest->reference_number);
    }

    public function index(): View
    {
        $requests = AccountDeletionRequest::with('resident')
            ->latest('submitted_at')
            ->paginate(20);

        return view('account-deletion-requests.index', [
            'requests' => $requests,
        ]);
    }

    public function update(Request $request, AccountDeletionRequest $accountDeletionRequest): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:received,reviewing,completed,rejected',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $accountDeletionRequest->update([
            ...$validated,
            'processed_at' => in_array($validated['status'], ['completed', 'rejected'], true)
                ? now()
                : null,
        ]);

        return redirect()
            ->route('account-deletion-requests.index')
            ->with('status', 'Account deletion request updated.');
    }
}
