<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Mary\Traits\Toast;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Hash;

class UserManagement extends Component
{
    use WithPagination, Toast, AuthorizesRequests;

    // List properties
    public $search = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $perPage = 10;
    public $roleFilter = '';

    // User form properties
    public $userId;
    public $name;
    public $email;
    public $password;
    public $password_confirmation;
    public $selectedRoles = [];
    public $selectedPermissions = [];

    // Modal visibility
    public $showCreateModal = false;
    public $showEditModal = false;
    public $showDeleteModal = false;

    protected $listeners = ['refreshUsers' => '$refresh'];

    protected function rules()
    {
        $passwordRules = $this->userId
            ? ['nullable', 'min:8', 'confirmed'] // Optional for updates
            : ['required', 'min:8', 'confirmed']; // Required for new users

        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $this->userId,
            'password' => $passwordRules,
            'selectedRoles' => 'nullable|array',
            'selectedPermissions' => 'nullable|array',
        ];
    }

    public function mount()
    {
        $this->authorize('view-users');
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

    public function createUser()
    {
        $this->authorize('create-users');

        $this->resetUserForm();
        $this->showCreateModal = true;
    }

    public function editUser($userId)
    {
        $this->authorize('edit-users');

        $this->resetUserForm();
        $this->userId = $userId;
        $user = User::findOrFail($userId);

        $this->name = $user->name;
        $this->email = $user->email;
        $this->selectedRoles = $user->roles->pluck('id')->toArray();
        $this->selectedPermissions = $user->permissions->pluck('id')->toArray();

        $this->showEditModal = true;
    }

    public function confirmDelete($userId)
    {
        $this->authorize('delete-users');

        $this->userId = $userId;
        $this->showDeleteModal = true;
    }

    public function deleteUser()
    {
        $this->authorize('delete-users');

        $user = User::findOrFail($this->userId);

        // Prevent deleting yourself
        if (auth()->id() === $user->id) {
            $this->error('You cannot delete your own account.');
            $this->showDeleteModal = false;
            return;
        }

        $user->delete();
        $this->success('User deleted successfully.');
        $this->showDeleteModal = false;
        $this->resetPage();
    }

    public function saveUser()
    {
        if ($this->userId) {
            $this->authorize('edit-users');
            $this->updateUser();
        } else {
            $this->authorize('create-users');
            $this->storeUser();
        }
    }

    private function storeUser()
    {
        $this->validate();

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
        ]);

        // Assign roles
        if (!empty($this->selectedRoles)) {
            $roles = Role::whereIn('id', $this->selectedRoles)->get();
            $user->syncRoles($roles);
        }

        // Assign direct permissions
        if (!empty($this->selectedPermissions)) {
            $permissions = Permission::whereIn('id', $this->selectedPermissions)->get();
            $user->syncPermissions($permissions);
        }

        $this->success('User created successfully.');
        $this->showCreateModal = false;
        $this->resetUserForm();
    }

    private function updateUser()
    {
        $this->validate();

        $user = User::findOrFail($this->userId);

        $userData = [
            'name' => $this->name,
            'email' => $this->email,
        ];

        if (!empty($this->password)) {
            $userData['password'] = Hash::make($this->password);
        }

        $user->update($userData);

        // Sync roles
        $roles = !empty($this->selectedRoles) ? Role::whereIn('id', $this->selectedRoles)->get() : [];
        $user->syncRoles($roles);

        // Sync direct permissions
        $permissions = !empty($this->selectedPermissions) ? Permission::whereIn('id', $this->selectedPermissions)->get() : [];
        $user->syncPermissions($permissions);

        $this->success('User updated successfully.');
        $this->showEditModal = false;
    }

    private function resetUserForm()
    {
        $this->userId = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->selectedRoles = [];
        $this->selectedPermissions = [];
    }

    public function render()
    {
        $this->authorize('view-users');

        $query = User::query();

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        if (!empty($this->roleFilter)) {
            $query->whereHas('roles', function($q) {
                $q->where('id', $this->roleFilter);
            });
        }

        // Apply sorting
        $query->orderBy($this->sortField, $this->sortDirection);

        $users = $query->with('roles', 'permissions')->paginate($this->perPage);
        $roles = Role::all();
        $permissions = Permission::all();

        return view('livewire.admin.user-management', [
            'users' => $users,
            'roles' => $roles,
            'permissions' => $permissions
        ]);
    }
}
