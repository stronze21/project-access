<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Mary\Traits\Toast;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class RoleManagement extends Component
{
    use WithPagination, Toast, AuthorizesRequests;

    // List properties
    public $search = '';
    public $sortField = 'name';
    public $sortDirection = 'asc';
    public $perPage = 10;

    // Role form properties
    public $roleId;
    public $name;
    public $selectedPermissions = [];

    // Modal visibility
    public $showCreateModal = false;
    public $showEditModal = false;
    public $showDeleteModal = false;
    public $showPermissionsModal = false;
    public $currentRole;

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255|unique:roles,name,' . $this->roleId,
            'selectedPermissions' => 'nullable|array',
        ];
    }

    public function mount()
    {
        $this->authorize('manage-users');
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function createRole()
    {
        $this->authorize('manage-users');

        $this->resetRoleForm();
        $this->showCreateModal = true;
    }

    public function editRole($roleId)
    {
        $this->authorize('manage-users');

        $this->resetRoleForm();
        $this->roleId = $roleId;
        $role = Role::findOrFail($roleId);

        $this->name = $role->name;
        $this->selectedPermissions = $role->permissions->pluck('id')->toArray();

        $this->showEditModal = true;
    }

    public function confirmDelete($roleId)
    {
        $this->authorize('manage-users');

        $this->roleId = $roleId;
        $this->showDeleteModal = true;
    }

    public function deleteRole()
    {
        $this->authorize('manage-users');

        $role = Role::findOrFail($this->roleId);

        // Prevent deleting protected roles
        if (in_array($role->name, ['system-administrator'])) {
            $this->error('This role is protected and cannot be deleted.');
            $this->showDeleteModal = false;
            return;
        }

        $role->delete();
        $this->success('Role deleted successfully.');
        $this->showDeleteModal = false;
    }

    public function saveRole()
    {
        $this->authorize('manage-users');

        $this->validate();

        if ($this->roleId) {
            $this->updateRole();
        } else {
            $this->storeRole();
        }
    }

    private function storeRole()
    {
        $role = Role::create(['name' => $this->name, 'guard_name' => 'web']);

        // Assign permissions
        if (!empty($this->selectedPermissions)) {
            $permissions = Permission::whereIn('id', $this->selectedPermissions)->get();
            $role->syncPermissions($permissions);
        }

        $this->success('Role created successfully.');
        $this->showCreateModal = false;
        $this->resetRoleForm();
    }

    private function updateRole()
    {
        $role = Role::findOrFail($this->roleId);

        // Prevent editing protected roles
        if (in_array($role->name, ['system-administrator']) && $this->name !== $role->name) {
            $this->error('This role name is protected and cannot be changed.');
            return;
        }

        $role->update(['name' => $this->name]);

        // Sync permissions
        if (!empty($this->selectedPermissions)) {
            $permissions = Permission::whereIn('id', $this->selectedPermissions)->get();
            $role->syncPermissions($permissions);
        } else {
            $role->syncPermissions([]);
        }

        $this->success('Role updated successfully.');
        $this->showEditModal = false;
    }

    public function viewPermissions($roleId)
    {
        $this->currentRole = Role::with('permissions')->findOrFail($roleId);
        $this->showPermissionsModal = true;
    }

    private function resetRoleForm()
    {
        $this->roleId = null;
        $this->name = '';
        $this->selectedPermissions = [];
    }

    public function render()
    {
        $this->authorize('manage-users');

        $query = Role::query();

        if (!empty($this->search)) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        // Apply sorting
        $query->orderBy($this->sortField, $this->sortDirection);

        $roles = $query->withCount('permissions')->paginate($this->perPage);
        $permissions = Permission::orderBy('name')->get();

        // Group permissions for display
        $permissionGroups = [];
        foreach ($permissions as $permission) {
            $parts = explode('-', $permission->name);
            $group = isset($parts[0]) ? $parts[0] : 'other';

            if (!isset($permissionGroups[$group])) {
                $permissionGroups[$group] = [];
            }

            $permissionGroups[$group][] = $permission;
        }

        ksort($permissionGroups);

        return view('livewire.admin.role-management', [
            'roles' => $roles,
            'permissions' => $permissions,
            'permissionGroups' => $permissionGroups
        ]);
    }
}
