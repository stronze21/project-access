<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\ComplaintCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ComplaintCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('manageReferenceData', Complaint::class);

        $baseQuery = ComplaintCategory::query()->withCount('complaints');
        $query = clone $baseQuery;

        $search = trim($request->string('q')->toString());
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
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
            'with_description' => (clone $baseQuery)->whereNotNull('description')->where('description', '!=', '')->count(),
            'filtered' => (clone $query)->count(),
        ];

        return view('complaints.categories.index', [
            'categories' => $query->orderBy('name')->paginate(12)->withQueryString(),
            'stats' => $stats,
            'activeFilterLabels' => $activeFilterLabels,
            'hasActiveFilters' => !empty($activeFilterLabels),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manageReferenceData', Complaint::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('complaint_categories', 'name')],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        ComplaintCategory::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return back()->with('status', 'Category created.');
    }

    public function update(Request $request, ComplaintCategory $category): RedirectResponse
    {
        $this->authorize('manageReferenceData', Complaint::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('complaint_categories', 'name')->ignore($category->id)],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $category->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return back()->with('status', 'Category updated.');
    }

    public function destroy(Request $request, ComplaintCategory $category): RedirectResponse
    {
        $this->authorize('manageReferenceData', Complaint::class);
        $category->delete();

        return back()->with('status', 'Category removed.');
    }
}
