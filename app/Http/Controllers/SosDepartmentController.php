<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\SosDepartment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SosDepartmentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('manageReferenceData', Complaint::class);

        $baseQuery = SosDepartment::query()->withCount('alerts');
        $query = clone $baseQuery;
        $search = trim($request->string('q')->toString());

        if ($search !== '') {
            $query->where(fn ($builder) => $builder
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('code', 'like', '%'.$search.'%')
                ->orWhere('hotline', 'like', '%'.$search.'%'));
        }

        if (in_array($request->string('state')->toString(), ['active', 'inactive'], true)) {
            $query->where('is_active', $request->string('state')->toString() === 'active');
        }

        return view('complaints.references.manage', [
            'heading' => 'SOS Response Departments',
            'description' => 'Manage the response units residents can notify when sending an SOS alert.',
            'resourceLabel' => 'SOS Department',
            'records' => $query->orderBy('sort_order')->orderBy('name')->paginate(15)->withQueryString(),
            'fields' => [
                'name' => ['label' => 'Name', 'required' => true, 'placeholder' => 'e.g. Fire Department'],
                'code' => ['label' => 'Code', 'required' => true, 'placeholder' => 'e.g. FIRE'],
                'hotline' => ['label' => 'Hotline', 'required' => false, 'placeholder' => 'Contact number'],
                'description' => ['label' => 'Description', 'required' => false, 'placeholder' => 'Response coverage'],
                'sort_order' => ['label' => 'Sort Order', 'required' => true, 'placeholder' => '0', 'type' => 'number'],
            ],
            'routePrefix' => 'complaints.sos-departments',
            'routeParameter' => 'sosDepartment',
            'usageLabel' => 'SOS Alerts',
            'stats' => $this->stats($baseQuery, $query),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manageReferenceData', Complaint::class);
        SosDepartment::query()->create($this->validatedData($request) + ['is_active' => $request->boolean('is_active', true)]);

        return back()->with('status', 'SOS department created.');
    }

    public function update(Request $request, SosDepartment $sosDepartment): RedirectResponse
    {
        $this->authorize('manageReferenceData', Complaint::class);
        $sosDepartment->update($this->validatedData($request, $sosDepartment) + ['is_active' => $request->boolean('is_active')]);

        return back()->with('status', 'SOS department updated.');
    }

    public function destroy(SosDepartment $sosDepartment): RedirectResponse
    {
        $this->authorize('manageReferenceData', Complaint::class);

        if ($sosDepartment->alerts()->exists()) {
            return back()->withErrors(['sos_department' => 'This response department is linked to SOS alerts. Deactivate it instead.']);
        }

        $sosDepartment->delete();

        return back()->with('status', 'SOS department removed.');
    }

    private function validatedData(Request $request, ?SosDepartment $department = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('sos_departments', 'code')->ignore($department?->id)],
            'hotline' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
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
