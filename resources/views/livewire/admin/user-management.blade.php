<div>
    <div class="flex flex-col justify-between gap-4 mb-6 md:flex-row md:items-end">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">User Management</h1>
            <p class="mt-1 text-sm text-gray-600">Manage system users, roles, and permissions</p>
        </div>
        <div class="flex space-x-2">
            @can('create-users')
                <x-mary-button wire:click="createUser" icon="o-plus" label="New User" class="btn-primary" />
            @endcan
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="mb-6">
        <div class="flex flex-col gap-4 md:flex-row">
            <div class="flex-1">
                <x-mary-input wire:model.live.debounce.300ms="search" label="Search"
                    placeholder="Search by name or email..." icon="o-magnifying-glass" />
            </div>
            <div>
                <x-mary-select label="Role Filter" wire:model.live="roleFilter" :options="$roles"
                    placeholder="All Roles" placeholder-value="" />
            </div>
            <div>
                <x-mary-select label="Items per page" :options="[
                    ['key' => '10', 'id' => '10 per page'],
                    ['key' => '25', 'id' => '25 per page'],
                    ['key' => '50', 'id' => '50 per page'],
                    ['key' => '100', 'id' => '100 per page'],
                ]" option-value="key" option-label="id"
                    placeholder="Select items per page" placeholder-value="" wire:model="perPage" />

            </div>
        </div>
    </div>

    <!-- Users Table -->
    <x-mary-card>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-base-500">
                <thead class="text-xs text-gray-700 uppercase bg-base-50">
                    <tr>
                        <th scope="col" class="px-4 py-3">
                            <div class="flex items-center">
                                Name
                                <button wire:click="sortBy('name')" class="ml-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        @if ($sortField === 'name')
                                            @if ($sortDirection === 'asc')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 15l7-7 7 7" />
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            @endif
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 16V4m0 0L3 8m4-4l4 4" />
                                        @endif
                                    </svg>
                                </button>
                            </div>
                        </th>
                        <th scope="col" class="px-4 py-3">
                            <div class="flex items-center">
                                Email
                                <button wire:click="sortBy('email')" class="ml-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        @if ($sortField === 'email')
                                            @if ($sortDirection === 'asc')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 15l7-7 7 7" />
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            @endif
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 16V4m0 0L3 8m4-4l4 4" />
                                        @endif
                                    </svg>
                                </button>
                            </div>
                        </th>
                        <th scope="col" class="px-4 py-3">Roles</th>
                        <th scope="col" class="px-4 py-3">Direct Permissions</th>
                        <th scope="col" class="px-4 py-3">
                            <div class="flex items-center">
                                Created
                                <button wire:click="sortBy('created_at')" class="ml-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        @if ($sortField === 'created_at')
                                            @if ($sortDirection === 'asc')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 15l7-7 7 7" />
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            @endif
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 16V4m0 0L3 8m4-4l4 4" />
                                        @endif
                                    </svg>
                                </button>
                            </div>
                        </th>
                        <th scope="col" class="px-4 py-3">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr class="border-b bg-base hover:bg-base-50">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $user->name }}</td>
                            <td class="px-4 py-3">{{ $user->email }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($user->roles as $role)
                                        <span
                                            class="px-2 py-1 text-xs font-medium rounded-full
                                            @if ($role->name === 'system-administrator') bg-red-100 text-red-800
                                            @elseif($role->name === 'program-manager')
                                                bg-blue-100 text-blue-800
                                            @elseif($role->name === 'registration-officer')
                                                bg-green-100 text-green-800
                                            @elseif($role->name === 'distribution-officer')
                                                bg-purple-100 text-purple-800
                                            @elseif($role->name === 'reporting-user')
                                                bg-amber-100 text-amber-800
                                            @else
                                                bg-gray-100 text-gray-800 @endif
                                        ">
                                            {{ ucwords(str_replace('-', ' ', $role->name)) }}
                                        </span>
                                    @endforeach
                                    @if ($user->roles->isEmpty())
                                        <span class="text-gray-500">None</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @if ($user->permissions->count() > 0)
                                        <span
                                            class="inline-flex items-center px-2 py-1 text-xs font-medium text-gray-800 bg-gray-100 rounded-full">
                                            {{ $user->permissions->count() }} direct
                                            {{ Str::plural('permission', $user->permissions->count()) }}
                                        </span>
                                        <button type="button"
                                            class="text-xs text-blue-600 hover:underline focus:outline-none"
                                            onclick="alert('Direct permissions: {{ $user->permissions->pluck('name')->implode(', ') }}')">
                                            View details
                                        </button>
                                    @else
                                        <span class="text-gray-500">None</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3">{{ $user->created_at->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    @can('edit-users')
                                        <x-mary-button wire:click="editUser({{ $user->id }})" icon="o-pencil"
                                            size="xs" class="tagged-color btn-secondary btn-outline btn-secline" />
                                    @endcan

                                    @can('delete-users')
                                        @if (auth()->id() !== $user->id)
                                            <x-mary-button wire:click="confirmDelete({{ $user->id }})" icon="o-trash"
                                                size="xs" class="tagged-color btn-error" />
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach

                    @if ($users->count() === 0)
                        <tr class="border-b bg-base">
                            <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 mb-2" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="mb-1">No users found</p>
                                    <p class="text-sm">Try adjusting your search or filters</p>
                                </div>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-4 py-3 border-t">
            {{ $users->links() }}
        </div>
    </x-mary-card>

    <!-- Create User Modal -->
    <x-mary-modal title="Create New User" wire:model="showCreateModal">
        <form wire:submit.prevent="saveUser">
            <div class="space-y-4">
                <x-mary-input label="Name" wire:model="name" placeholder="Enter full name" required
                    error="{{ $errors->first('name') }}" />

                <x-mary-input label="Email" wire:model="email" type="email" placeholder="Enter email address"
                    required error="{{ $errors->first('email') }}" />

                <x-mary-input label="Password" wire:model="password" type="password" placeholder="Enter password"
                    required error="{{ $errors->first('password') }}" />

                <x-mary-input label="Confirm Password" wire:model="password_confirmation" type="password"
                    placeholder="Confirm password" required />

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">Roles</label>
                    <div class="p-3 overflow-y-auto border rounded-md bg-base-50 max-h-40">
                        @foreach ($roles as $role)
                            <div class="mb-2">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" wire:model="selectedRoles" value="{{ $role->id }}"
                                        class="text-blue-600 border-gray-300 rounded shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    <span class="ml-2">{{ ucwords(str_replace('-', ' ', $role->name)) }}</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">Direct Permissions</label>
                    <div class="p-3 overflow-y-auto border rounded-md bg-base-50 max-h-40">
                        @foreach ($permissions as $permission)
                            <div class="mb-2">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" wire:model="selectedPermissions"
                                        value="{{ $permission->id }}"
                                        class="text-blue-600 border-gray-300 rounded shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    <span
                                        class="ml-2">{{ ucwords(str_replace('-', ' ', $permission->name)) }}</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="flex justify-end mt-6 space-x-2">
                <x-mary-button type="button" wire:click="$set('showCreateModal', false)"
                    class="tagged-color btn-secondary btn-outline btn-secline">Cancel</x-mary-button>
                <x-mary-button type="submit">Create User</x-mary-button>
            </div>
        </form>
    </x-mary-modal>

    <!-- Edit User Modal -->
    <x-mary-modal title="Edit User" wire:model="showEditModal">
        <form wire:submit.prevent="saveUser">
            <div class="space-y-4">
                <x-mary-input label="Name" wire:model="name" placeholder="Enter full name" required
                    error="{{ $errors->first('name') }}" />

                <x-mary-input label="Email" wire:model="email" type="email" placeholder="Enter email address"
                    required error="{{ $errors->first('email') }}" />

                <x-mary-input label="Password" wire:model="password" type="password"
                    placeholder="Enter new password to change" error="{{ $errors->first('password') }}" />

                <x-mary-input label="Confirm Password" wire:model="password_confirmation" type="password"
                    placeholder="Confirm new password" />

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">Roles</label>
                    <div class="p-3 overflow-y-auto border rounded-md bg-base-50 max-h-40">
                        @foreach ($roles as $role)
                            <div class="mb-2">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" wire:model="selectedRoles" value="{{ $role->id }}"
                                        class="text-blue-600 border-gray-300 rounded shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    <span class="ml-2">{{ ucwords(str_replace('-', ' ', $role->name)) }}</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">Direct Permissions</label>
                    <div class="p-3 overflow-y-auto border rounded-md bg-base-50 max-h-40">
                        @foreach ($permissions as $permission)
                            <div class="mb-2">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" wire:model="selectedPermissions"
                                        value="{{ $permission->id }}"
                                        class="text-blue-600 border-gray-300 rounded shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    <span
                                        class="ml-2">{{ ucwords(str_replace('-', ' ', $permission->name)) }}</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="flex justify-end mt-6 space-x-2">
                <x-mary-button type="button" wire:click="$set('showEditModal', false)"
                    class="tagged-color btn-secondary btn-outline btn-secline">Cancel</x-mary-button>
                <x-mary-button type="submit">Update User</x-mary-button>
            </div>
        </form>
    </x-mary-modal>

    <!-- Delete Confirmation Modal -->
    <x-mary-modal title="Confirm Deletion" wire:model="showDeleteModal">
        <div>
            <p>Are you sure you want to delete this user? This action cannot be undone.</p>
            <div class="flex justify-end mt-6 space-x-2">
                <x-mary-button wire:click="$set('showDeleteModal', false)"
                    class="tagged-color btn-secondary btn-outline btn-secline">Cancel</x-mary-button>
                <x-mary-button wire:click="deleteUser" class="tagged-color btn-error">Delete User</x-mary-button>
            </div>
        </div>
    </x-mary-modal>
</div>
