<div>
    <div class="flex flex-col justify-between gap-4 mb-6 md:flex-row md:items-end">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Role Management</h1>
            <p class="mt-1 text-sm text-gray-600">Manage system roles and their permissions</p>
        </div>
        <div class="flex space-x-2">
            <x-mary-button wire:click="createRole" icon="o-plus" label="New Role" />
        </div>
    </div>

    <!-- Search -->
    <div class="mb-6">
        <div class="flex flex-col gap-4 md:flex-row">
            <div class="flex-1">
                <x-mary-input wire:model.live.debounce.300ms="search" label="Search"
                    placeholder="Search by role name..." icon="o-magnifying-glass" />
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

    <!-- Roles Table -->
    <x-mary-card>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-base-500">
                <thead class="text-xs text-gray-700 uppercase bg-base-50">
                    <tr>
                        <th scope="col" class="px-4 py-3">
                            <div class="flex items-center">
                                Role Name
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
                        <th scope="col" class="px-4 py-3">Permissions</th>
                        <th scope="col" class="px-4 py-3">Users</th>
                        <th scope="col" class="px-4 py-3">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roles as $role)
                        <tr class="border-b bg-base hover:bg-base-50">
                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ ucwords(str_replace('-', ' ', $role->name)) }}
                                @if ($role->name === 'system-administrator')
                                    <span class="ml-2 text-xs text-red-600">(Protected)</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center">
                                    <span
                                        class="mr-2">{{ $role->permissions_count ?? $role->permissions()->count() }}</span>
                                    <x-mary-button wire:click="viewPermissions({{ $role->id }})" size="xs"
                                        class="tagged-color btn-secondary btn-outline btn-secline" label="View" />
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                {{ isset($role->users_count) ? $role->users_count : ($role->users ? $role->users()->count() : 0) }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <x-mary-button wire:click="editRole({{ $role->id }})" icon="o-pencil"
                                        size="xs" class="tagged-color btn-secondary btn-outline btn-secline" />

                                    @if (!in_array($role->name, ['system-administrator']))
                                        <x-mary-button wire:click="confirmDelete({{ $role->id }})" icon="o-trash"
                                            size="xs" class="tagged-color btn-error" />
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach

                    @if ($roles->count() === 0)
                        <tr class="border-b bg-base">
                            <td colspan="4" class="px-4 py-6 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 mb-2" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="mb-1">No roles found</p>
                                    <p class="text-sm">Try adjusting your search</p>
                                </div>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-4 py-3 border-t">
            {{ $roles->links() }}
        </div>
    </x-mary-card>

    <!-- Create Role Modal -->
    <x-mary-modal title="Create New Role" wire:model="showCreateModal">
        <form wire:submit.prevent="saveRole">
            <div class="space-y-4">
                <x-mary-input label="Role Name" wire:model="name" placeholder="Enter role name" required
                    error="{{ $errors->first('name') }}" />

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">Permissions</label>

                    @foreach ($permissionGroups as $group => $groupPermissions)
                        <div class="mb-4">
                            <h4 class="mb-2 text-sm font-semibold text-gray-600 uppercase">{{ $group }}</h4>
                            <div class="p-3 overflow-y-auto border rounded-md bg-base-50 max-h-40">
                                <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                                    @foreach ($groupPermissions as $permission)
                                        <div>
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
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end mt-6 space-x-2">
                <x-mary-button type="button" wire:click="$set('showCreateModal', false)"
                    class="tagged-color btn-secondary btn-outline btn-secline">Cancel</x-mary-button>
                <x-mary-button type="submit" class="btn-primary">Create Role</x-mary-button>
            </div>
        </form>
    </x-mary-modal>

    <!-- Edit Role Modal -->
    <x-mary-modal title="Edit Role" wire:model="showEditModal">
        <form wire:submit.prevent="saveRole">
            <div class="space-y-4">
                <x-mary-input label="Role Name" wire:model="name" placeholder="Enter role name" required
                    error="{{ $errors->first('name') }}" :disabled="in_array($name, ['system-administrator'])" />

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">Permissions</label>

                    @foreach ($permissionGroups as $group => $groupPermissions)
                        <div class="mb-4">
                            <h4 class="mb-2 text-sm font-semibold text-gray-600 uppercase">{{ $group }}</h4>
                            <div class="p-3 overflow-y-auto border rounded-md bg-base-50 max-h-40">
                                <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                                    @foreach ($groupPermissions as $permission)
                                        <div>
                                            <label class="inline-flex items-center">
                                                <input type="checkbox" wire:model="selectedPermissions"
                                                    value="{{ $permission->id }}"
                                                    class="text-blue-600 border-gray-300 rounded shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                                    {{ $name === 'system-administrator' ? 'disabled' : '' }}>
                                                <span
                                                    class="ml-2">{{ ucwords(str_replace('-', ' ', $permission->name)) }}</span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end mt-6 space-x-2">
                <x-mary-button type="button" wire:click="$set('showEditModal', false)"
                    class="tagged-color btn-secondary btn-outline btn-secline">Cancel</x-mary-button>
                <x-mary-button type="submit" class="btn-primary">Update Role</x-mary-button>
            </div>
        </form>
    </x-mary-modal>

    <!-- Delete Confirmation Modal -->
    <x-mary-modal title="Confirm Deletion" wire:model="showDeleteModal">
        <div>
            <p>Are you sure you want to delete this role? This action cannot be undone and will remove the role from all
                assigned users.</p>
            <div class="flex justify-end mt-6 space-x-2">
                <x-mary-button wire:click="$set('showDeleteModal', false)"
                    class="tagged-color btn-secondary btn-outline btn-secline">Cancel</x-mary-button>
                <x-mary-button wire:click="deleteRole" class="tagged-color btn-error">Delete Role</x-mary-button>
            </div>
        </div>
    </x-mary-modal>

    <!-- View Permissions Modal -->
    <x-mary-modal title="Permissions for {{ $currentRole ? ucwords(str_replace('-', ' ', $currentRole->name)) : '' }}"
        wire:model="showPermissionsModal" size="lg">
        <div>
            @if ($currentRole)
                @if ($currentRole->permissions->isEmpty())
                    <p class="text-center text-gray-500">This role has no permissions.</p>
                @else
                    <div class="space-y-4">
                        @php
                            $groupedPermissions = [];
                            foreach ($currentRole->permissions as $permission) {
                                $parts = explode('-', $permission->name);
                                $group = isset($parts[0]) ? $parts[0] : 'other';

                                if (!isset($groupedPermissions[$group])) {
                                    $groupedPermissions[$group] = [];
                                }

                                $groupedPermissions[$group][] = $permission->name;
                            }
                            ksort($groupedPermissions);
                        @endphp

                        @foreach ($groupedPermissions as $group => $permissions)
                            <div class="mb-4">
                                <h4 class="mb-2 text-sm font-semibold text-gray-600 uppercase">{{ $group }}
                                </h4>
                                <div class="p-3 border rounded-md bg-base-50">
                                    <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                                        @foreach ($permissions as $permission)
                                            <div class="text-sm">
                                                {{ ucwords(str_replace('-', ' ', $permission)) }}
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif

            <div class="flex justify-end mt-6">
                <x-mary-button wire:click="$set('showPermissionsModal', false)"
                    class="tagged-color btn-secondary btn-outline btn-secline">Close</x-mary-button>
            </div>
        </div>
    </x-mary-modal>
</div>
