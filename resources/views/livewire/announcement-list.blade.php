<div>
    <!-- Page Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Announcements Management</h1>
                <p class="mt-1 text-sm text-gray-600">Create and manage announcements for the resident portal</p>
            </div>
            <div class="flex space-x-2">
                <x-mary-button icon="o-funnel" @click="$wire.toggleFilters()"
                    class="{{ $showFilters ? 'btn-primary' : 'btn-outline' }}">
                    Filters
                </x-mary-button>
                <x-mary-button icon="o-plus" wire:click="create" class="btn-primary">
                    New Announcement
                </x-mary-button>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    @if ($showFilters)
        <x-mary-card class="mb-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div>
                    <x-mary-select label="Type" wire:model.live="type" :options="collect($typesList)
                        ->map(fn($name, $id) => ['id' => $id, 'name' => $name])
                        ->values()
                        ->toArray()" placeholder="Select type" />
                </div>
                <div>
                    <x-mary-select label="Priority" wire:model.live="priority" :options="collect($prioritiesList)
                        ->map(fn($name, $id) => ['id' => $id, 'name' => $name])
                        ->values()
                        ->toArray()"
                        placeholder="Select priority" />
                </div>
                <div>
                    <x-mary-select label="Status" wire:model.live="status" :options="collect([
                        'all' => 'All Status',
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'published' => 'Published',
                        'pinned' => 'Pinned',
                    ])
                        ->map(fn($name, $id) => ['id' => $id, 'name' => $name])
                        ->values()
                        ->toArray()"
                        placeholder="Select status" />
                </div>
                <div class="flex items-end">
                    <x-mary-button wire:click="clearFilters" class="w-full btn-secondary">
                        Clear Filters
                    </x-mary-button>
                </div>
            </div>
        </x-mary-card>
    @endif

    <!-- Announcements Table -->
    <x-mary-card>
        <!-- Search Bar -->
        <div class="mb-4">
            <x-mary-input icon="o-magnifying-glass" wire:model.live.debounce.300ms="search"
                placeholder="Search announcements by title or content..." />
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full table-auto">
                <thead class="bg-base-200">
                    <tr>
                        <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-700 uppercase cursor-pointer"
                            wire:click="sortBy('title')">
                            <div class="flex items-center space-x-1">
                                <span>Title</span>
                                @if ($sortField === 'title')
                                    <x-mary-icon
                                        name="{{ $sortDirection === 'asc' ? 'o-chevron-up' : 'o-chevron-down' }}"
                                        class="w-4 h-4" />
                                @endif
                            </div>
                        </th>
                        <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                            Type
                        </th>
                        <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                            Priority
                        </th>
                        <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                            Recipients
                        </th>
                        <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-700 uppercase cursor-pointer"
                            wire:click="sortBy('published_at')">
                            <div class="flex items-center space-x-1">
                                <span>Published</span>
                                @if ($sortField === 'published_at')
                                    <x-mary-icon
                                        name="{{ $sortDirection === 'asc' ? 'o-chevron-up' : 'o-chevron-down' }}"
                                        class="w-4 h-4" />
                                @endif
                            </div>
                        </th>
                        <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                            Status
                        </th>
                        <th class="px-4 py-3 text-xs font-medium tracking-wider text-right text-gray-700 uppercase">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($announcements as $announcement)
                        <tr class="border-b bg-base hover:bg-base-50">
                            <td class="px-4 py-3">
                                <div class="flex items-start space-x-3">
                                    @if ($announcement->image_path)
                                        <img src="{{ Storage::url($announcement->image_path) }}"
                                            alt="{{ $announcement->title }}" class="object-cover w-12 h-12 rounded">
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <div class="font-medium text-gray-900">
                                            {{ $announcement->title }}
                                            @if ($announcement->is_pinned)
                                                <x-mary-badge value="Pinned" class="badge-warning badge-sm" />
                                            @endif
                                        </div>
                                        <div class="text-sm text-gray-500 truncate">
                                            {{ Str::limit($announcement->content, 80) }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <x-mary-badge value="{{ ucfirst($announcement->type) }}"
                                    class="badge-{{ $announcement->type === 'emergency' ? 'error' : ($announcement->type === 'program' ? 'info' : 'ghost') }} badge-sm" />
                            </td>
                            <td class="px-4 py-3">
                                <x-mary-badge value="{{ ucfirst($announcement->priority) }}"
                                    class="badge-{{ $announcement->priority === 'urgent' ? 'error' : ($announcement->priority === 'high' ? 'warning' : 'ghost') }} badge-sm" />
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900">
                                @if ($announcement->recipient_type === 'program_beneficiaries')
                                    <x-mary-badge value="Program" class="badge-info badge-sm" />
                                    <span class="text-xs text-gray-500">{{ $announcement->program ? $announcement->program->name : '' }}</span>
                                @else
                                    <x-mary-badge value="All Residents" class="badge-success badge-sm" />
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900">
                                {{ $announcement->published_at ? $announcement->published_at->setTimezone('Asia/Manila')->format('M d, Y h:i A') : 'Draft' }}
                            </td>
                            <td class="px-4 py-3">
                                @if ($announcement->is_active)
                                    <x-mary-badge value="Active" class="badge-success badge-sm" />
                                @else
                                    <x-mary-badge value="Inactive" class="badge-ghost badge-sm" />
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <x-mary-button icon="o-pencil" wire:click="edit({{ $announcement->id }})"
                                        class="btn-ghost btn-sm" tooltip="Edit" />
                                    <x-mary-button
                                        icon="{{ $announcement->is_active ? 'o-x-circle' : 'o-check-circle' }}"
                                        wire:click="toggleStatus({{ $announcement->id }})" class="btn-ghost btn-sm"
                                        tooltip="{{ $announcement->is_active ? 'Deactivate' : 'Activate' }}" />
                                    <x-mary-button
                                        icon="{{ $announcement->is_pinned ? 'o-bookmark-slash' : 'o-bookmark' }}"
                                        wire:click="togglePinned({{ $announcement->id }})" class="btn-ghost btn-sm"
                                        tooltip="{{ $announcement->is_pinned ? 'Unpin' : 'Pin' }}" />
                                    <x-mary-button icon="o-bell-alert"
                                        wire:click="confirmNotify({{ $announcement->id }})"
                                        class="btn-ghost btn-sm text-info" tooltip="Send Push Notification" />
                                    <x-mary-button icon="o-trash" wire:click="confirmDelete({{ $announcement->id }})"
                                        class="btn-ghost btn-sm text-error" tooltip="Delete" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                <div
                                    class="inline-flex items-center justify-center w-16 h-16 mb-4 rounded-full bg-base-200">
                                    <x-mary-icon name="o-megaphone" class="w-8 h-8 text-gray-400" />
                                </div>
                                <p class="text-lg font-medium">No announcements found</p>
                                <p class="text-sm">
                                    {{ $search ? 'Try adjusting your search or filters' : 'Create your first announcement to get started' }}
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-4 py-3 border-t">
            {{ $announcements->links() }}
        </div>
    </x-mary-card>

    <!-- Create Modal -->
    <x-mary-modal wire:model="showCreateModal" title="Create New Announcement" box-class="max-w-3xl">
        <form wire:submit.prevent="save">
            <div class="space-y-4">
                <x-mary-input label="Title" wire:model="title" placeholder="Enter announcement title" required
                    error="{{ $errors->first('title') }}" />

                <x-mary-textarea label="Content" wire:model="content" placeholder="Enter announcement content"
                    rows="5" required error="{{ $errors->first('content') }}" />

                <div class="grid grid-cols-2 gap-4">
                    <x-mary-select label="Type" wire:model="announcementType" placeholder="Select type"
                        placeholder-value="" required :options="collect($typesList)
                            ->map(fn($name, $id) => ['id' => $id, 'name' => $name])
                            ->values()
                            ->toArray()" />
                    <x-mary-select label="Priority" wire:model="announcementPriority" placeholder="Select priority"
                        placeholder-value="" required :options="collect($prioritiesList)
                            ->map(fn($name, $id) => ['id' => $id, 'name' => $name])
                            ->values()
                            ->toArray()" />
                </div>

                <x-mary-select label="Recipients" wire:model.live="recipientType" placeholder="Select recipients"
                    placeholder-value="" required :options="collect($recipientTypesList)
                        ->map(fn($name, $id) => ['id' => $id, 'name' => $name])
                        ->values()
                        ->toArray()"
                    hint="Choose who should see this announcement" />

                <x-mary-select label="{{ $recipientType === 'program_beneficiaries' ? 'Target Program (Required)' : 'Related Program (Optional)' }}" wire:model="ayudaProgramId" :options="collect($ayudaPrograms)
                    ->map(fn($p) => ['id' => $p['id'], 'name' => $p['name'] . ' (' . $p['code'] . ')'])
                    ->values()
                    ->toArray()"
                    placeholder="Select a program"
                    :required="$recipientType === 'program_beneficiaries'"
                    error="{{ $errors->first('ayudaProgramId') }}" />

                <x-mary-file label="Announcement Image (Optional)" wire:model="image" accept="image/*"
                    hint="Max 2MB" />
                @if ($image)
                    <div class="mt-2">
                        <img src="{{ $image->temporaryUrl() }}" alt="Preview"
                            class="object-cover w-32 h-32 rounded">
                    </div>
                @endif

                <div class="grid grid-cols-2 gap-4">
                    <x-mary-input label="Publish Date & Time (Optional)" wire:model="publishedAt"
                        type="datetime-local" hint="Leave empty to publish immediately" />
                    <x-mary-input label="Expiry Date & Time (Optional)" wire:model="expiresAt" type="datetime-local"
                        hint="Leave empty for no expiration" />
                </div>

                <div class="flex space-x-4">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" wire:model="isActive" class="checkbox checkbox-primary">
                        <span class="label-text">Active</span>
                    </label>
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" wire:model="isPinned" class="checkbox checkbox-primary">
                        <span class="label-text">Pin to Top</span>
                    </label>
                </div>
            </div>

            <div class="flex justify-end mt-6 space-x-2">
                <x-mary-button type="button" wire:click="$set('showCreateModal', false)"
                    class="btn-secondary btn-outline">
                    Cancel
                </x-mary-button>
                <x-mary-button type="submit" class="btn-primary">
                    Create Announcement
                </x-mary-button>
            </div>
        </form>
    </x-mary-modal>

    <!-- Edit Modal -->
    <x-mary-modal wire:model="showEditModal" title="Edit Announcement" box-class="max-w-3xl">
        <form wire:submit.prevent="save">
            <div class="space-y-4">
                <x-mary-input label="Title" wire:model="title" placeholder="Enter announcement title" required
                    error="{{ $errors->first('title') }}" />

                <x-mary-textarea label="Content" wire:model="content" placeholder="Enter announcement content"
                    rows="5" required error="{{ $errors->first('content') }}" />

                <div class="grid grid-cols-2 gap-4">
                    <x-mary-select label="Type" wire:model="announcementType" placeholder="Select type"
                        placeholder-value="" required :options="collect($typesList)
                            ->map(fn($name, $id) => ['id' => $id, 'name' => $name])
                            ->values()
                            ->toArray()" />
                    <x-mary-select label="Priority" wire:model="announcementPriority" placeholder="Select priority"
                        placeholder-value="" required :options="collect($prioritiesList)
                            ->map(fn($name, $id) => ['id' => $id, 'name' => $name])
                            ->values()
                            ->toArray()" />
                </div>

                <x-mary-select label="Recipients" wire:model.live="recipientType" placeholder="Select recipients"
                    placeholder-value="" required :options="collect($recipientTypesList)
                        ->map(fn($name, $id) => ['id' => $id, 'name' => $name])
                        ->values()
                        ->toArray()"
                    hint="Choose who should see this announcement" />

                <x-mary-select label="{{ $recipientType === 'program_beneficiaries' ? 'Target Program (Required)' : 'Related Program (Optional)' }}" wire:model="ayudaProgramId" :options="collect($ayudaPrograms)
                    ->map(fn($p) => ['id' => $p['id'], 'name' => $p['name'] . ' (' . $p['code'] . ')'])
                    ->values()
                    ->toArray()"
                    placeholder="Select a program"
                    :required="$recipientType === 'program_beneficiaries'"
                    error="{{ $errors->first('ayudaProgramId') }}" />

                <div>
                    @if ($existingImagePath)
                        <div class="mb-2">
                            <p class="text-sm font-medium text-gray-700">Current Image:</p>
                            <img src="{{ Storage::url($existingImagePath) }}" alt="Current"
                                class="object-cover w-32 h-32 rounded">
                        </div>
                    @endif
                    <x-mary-file label="Update Image (Optional)" wire:model="image" accept="image/*"
                        hint="Max 2MB. Leave empty to keep current image." />
                    @if ($image)
                        <div class="mt-2">
                            <p class="text-sm font-medium text-gray-700">New Image Preview:</p>
                            <img src="{{ $image->temporaryUrl() }}" alt="Preview"
                                class="object-cover w-32 h-32 rounded">
                        </div>
                    @endif
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <x-mary-input label="Publish Date & Time (Optional)" wire:model="publishedAt"
                        type="datetime-local" hint="Leave empty to publish immediately" />
                    <x-mary-input label="Expiry Date & Time (Optional)" wire:model="expiresAt" type="datetime-local"
                        hint="Leave empty for no expiration" />
                </div>

                <div class="flex space-x-4">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" wire:model="isActive" class="checkbox checkbox-primary">
                        <span class="label-text">Active</span>
                    </label>
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" wire:model="isPinned" class="checkbox checkbox-primary">
                        <span class="label-text">Pin to Top</span>
                    </label>
                </div>
            </div>

            <div class="flex justify-end mt-6 space-x-2">
                <x-mary-button type="button" wire:click="$set('showEditModal', false)"
                    class="btn-secondary btn-outline">
                    Cancel
                </x-mary-button>
                <x-mary-button type="submit" class="btn-primary">
                    Update Announcement
                </x-mary-button>
            </div>
        </form>
    </x-mary-modal>

    <!-- Delete Confirmation Modal -->
    <x-mary-modal wire:model="showDeleteModal" title="Delete Announcement">
        <div class="py-4">
            <p class="text-gray-600">Are you sure you want to delete this announcement? This action cannot be undone.
            </p>
        </div>

        <div class="flex justify-end mt-6 space-x-2">
            <x-mary-button type="button" wire:click="$set('showDeleteModal', false)"
                class="btn-secondary btn-outline">
                Cancel
            </x-mary-button>
            <x-mary-button wire:click="delete" class="btn-error">
                Delete Announcement
            </x-mary-button>
        </div>
    </x-mary-modal>

    <!-- Send Notification Confirmation Modal -->
    <x-mary-modal wire:model="showNotifyModal" title="Send Push Notification">
        <div class="py-4">
            <div class="flex items-start space-x-3">
                <div class="flex items-center justify-center flex-shrink-0 w-10 h-10 rounded-full bg-blue-100">
                    <x-mary-icon name="o-bell-alert" class="w-5 h-5 text-blue-600" />
                </div>
                <div>
                    <p class="text-gray-600">
                        This will send a push notification about this announcement to <strong>all residents</strong>
                        with registered devices.
                    </p>
                    <p class="mt-2 text-sm text-gray-500">
                        Notifications will be delivered even if residents have the app closed (requires FCM to be
                        configured).
                    </p>
                </div>
            </div>
        </div>

        <div class="flex justify-end mt-6 space-x-2">
            <x-mary-button type="button" wire:click="$set('showNotifyModal', false)"
                class="btn-secondary btn-outline">
                Cancel
            </x-mary-button>
            <x-mary-button wire:click="sendNotification" class="btn-primary">
                <x-mary-icon name="o-paper-airplane" class="w-4 h-4 mr-1" />
                Send Notification
            </x-mary-button>
        </div>
    </x-mary-modal>
</div>
