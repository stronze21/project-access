<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\Department;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ActionOfficerController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('manageReferenceData', Complaint::class);

        $query = User::query()
            ->actionOfficers()
            ->with('department:id,name')
            ->withCount('assignedComplaints');

        $search = trim($request->string('q')->toString());
        if ($search !== '') {
            $query->where(fn ($builder) => $builder
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%'));
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->integer('department_id'));
        }

        return view('complaints.action-officers.index', [
            'officers' => $query->orderBy('name')->paginate(15)->withQueryString(),
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'stats' => [
                'total' => User::query()->actionOfficers()->count(),
                'assigned' => User::query()->actionOfficers()->whereNotNull('department_id')->count(),
                'unassigned' => User::query()->actionOfficers()->whereNull('department_id')->count(),
                'filtered' => (clone $query)->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manageReferenceData', Complaint::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'department_id' => ['required', Rule::exists('departments', 'id')->where('is_active', true)],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $officer = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'department_id' => $validated['department_id'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        Role::findOrCreate('action-officer', 'web');
        $officer->assignRole('action-officer');

        return back()->with('status', 'Action officer created.');
    }

    public function update(Request $request, User $officer): RedirectResponse
    {
        $this->authorize('manageReferenceData', Complaint::class);
        abort_unless($officer->isActionOfficer(), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($officer->id)],
            'department_id' => ['required', Rule::exists('departments', 'id')->where('is_active', true)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        $officerData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'department_id' => $validated['department_id'],
        ];

        if (filled($validated['password'] ?? null)) {
            $officerData['password'] = Hash::make($validated['password']);
        }

        $officer->update($officerData);

        Role::findOrCreate('action-officer', 'web');
        $officer->assignRole('action-officer');

        return back()->with('status', 'Action officer updated.');
    }

    public function destroy(User $officer): RedirectResponse
    {
        $this->authorize('manageReferenceData', Complaint::class);
        abort_unless($officer->isActionOfficer(), 404);

        $actionRoleIds = Role::query()
            ->whereIn('name', [User::ROLE_ACTION_OFFICER, 'action-officer'])
            ->pluck('id');

        $officer->roles()->detach($actionRoleIds);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('status', 'Action officer access removed. The user account and complaint history were retained.');
    }
}
