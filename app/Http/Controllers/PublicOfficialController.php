<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\PublicOfficial;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PublicOfficialController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('manageReferenceData', Complaint::class);

        $baseQuery = PublicOfficial::query();
        $query = PublicOfficial::query()->withCount('complaints');

        $search = trim($request->string('q')->toString());
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('position', 'like', '%'.$search.'%');
            });
        }

        $selectedState = $request->string('state')->toString();
        if ($selectedState === 'active') {
            $query->where('is_active', true);
        }

        if ($selectedState === 'inactive') {
            $query->where('is_active', false);
        }

        $activeFilterLabels = [];
        if ($search !== '') {
            $activeFilterLabels[] = 'Search: '.$search;
        }

        if ($selectedState === 'active') {
            $activeFilterLabels[] = 'Status: Active';
        }

        if ($selectedState === 'inactive') {
            $activeFilterLabels[] = 'Status: Inactive';
        }

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('is_active', true)->count(),
            'inactive' => (clone $baseQuery)->where('is_active', false)->count(),
            'positions' => (clone $baseQuery)->select('position')->distinct()->count('position'),
            'filtered' => (clone $query)->count(),
        ];

        return view('complaints.officials.index', [
            'officials' => $query->orderBy('position')->orderBy('name')->paginate(12)->withQueryString(),
            'stats' => $stats,
            'activeFilterLabels' => $activeFilterLabels,
            'hasActiveFilters' => !empty($activeFilterLabels),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manageReferenceData', Complaint::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        PublicOfficial::create([
            'name' => $validated['name'],
            'position' => $validated['position'],
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return back()->with('status', 'Public official created.');
    }

    public function update(Request $request, PublicOfficial $official): RedirectResponse
    {
        $this->authorize('manageReferenceData', Complaint::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $official->update([
            'name' => $validated['name'],
            'position' => $validated['position'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return back()->with('status', 'Public official updated.');
    }

    public function destroy(Request $request, PublicOfficial $official): RedirectResponse
    {
        $this->authorize('manageReferenceData', Complaint::class);
        $official->delete();

        return back()->with('status', 'Public official removed.');
    }
}
