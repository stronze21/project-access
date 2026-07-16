<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\ComplaintBarangay;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ComplaintBarangayController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('manageReferenceData', Complaint::class);

        $baseQuery = ComplaintBarangay::query()->withCount('complaints');
        $query = clone $baseQuery;
        $search = trim($request->string('q')->toString());

        if ($search !== '') {
            $query->where(fn ($builder) => $builder
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('code', 'like', '%'.$search.'%'));
        }

        if (in_array($request->string('state')->toString(), ['active', 'inactive'], true)) {
            $query->where('is_active', $request->string('state')->toString() === 'active');
        }

        return view('complaints.references.manage', [
            'heading' => 'BosesMoto Barangays',
            'description' => 'Manage the barangay choices used for complaint locations and public reporting.',
            'resourceLabel' => 'Barangay',
            'records' => $query->orderBy('name')->paginate(15)->withQueryString(),
            'fields' => [
                'name' => ['label' => 'Name', 'required' => true, 'placeholder' => 'e.g. Poblacion'],
                'code' => ['label' => 'Code', 'required' => false, 'placeholder' => 'Optional code'],
            ],
            'routePrefix' => 'complaints.barangays',
            'routeParameter' => 'barangay',
            'usageLabel' => 'Complaints',
            'stats' => $this->stats($baseQuery, $query),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manageReferenceData', Complaint::class);
        $validated = $this->validateBarangay($request);
        ComplaintBarangay::query()->create($validated + ['is_active' => $request->boolean('is_active', true)]);

        return back()->with('status', 'Barangay created.');
    }

    public function update(Request $request, ComplaintBarangay $barangay): RedirectResponse
    {
        $this->authorize('manageReferenceData', Complaint::class);
        $barangay->update($this->validateBarangay($request, $barangay) + ['is_active' => $request->boolean('is_active')]);

        return back()->with('status', 'Barangay updated.');
    }

    public function destroy(ComplaintBarangay $barangay): RedirectResponse
    {
        $this->authorize('manageReferenceData', Complaint::class);

        if ($barangay->complaints()->exists()) {
            return back()->withErrors(['barangay' => 'This barangay is linked to complaints. Deactivate it instead.']);
        }

        $barangay->delete();

        return back()->with('status', 'Barangay removed.');
    }

    private function validateBarangay(Request $request, ?ComplaintBarangay $barangay = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('bosesmoto_barangays', 'name')->ignore($barangay?->id)],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('bosesmoto_barangays', 'code')->ignore($barangay?->id)],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function stats($baseQuery, $filteredQuery): array
    {
        return [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('is_active', true)->count(),
            'inactive' => (clone $baseQuery)->where('is_active', false)->count(),
            'filtered' => (clone $filteredQuery)->count(),
        ];
    }
}
