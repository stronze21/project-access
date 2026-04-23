<?php

namespace App\Livewire;

use App\Models\Announcement;
use App\Models\AyudaProgram;
use App\Models\User;
use App\Jobs\SendAnnouncementNotifications;
use App\Traits\ComponentAuthorization;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AnnouncementList extends Component
{
    use WithPagination;
    use WithFileUploads;
    use Toast;
    use ComponentAuthorization;

    // Search and filters
    #[Url]
    public $search = '';

    #[Url]
    public $type = '';

    #[Url]
    public $priority = '';

    #[Url]
    public $status = 'all';

    #[Url]
    public $sortField = 'created_at';

    #[Url]
    public $sortDirection = 'desc';

    #[Url]
    public $perPage = 10;

    // Flags
    public $showFilters = false;
    public $showCreateModal = false;
    public $showEditModal = false;
    public $showDeleteModal = false;
    public $showNotifyModal = false;

    // Form fields
    public $announcementId;
    public $title;
    public $content;
    public $announcementType = 'general';
    public $announcementPriority = 'normal';
    public $recipientType = 'all';
    public $image;
    public $existingImagePath;
    public $ayudaProgramId;
    public $publishedAt;
    public $expiresAt;
    public $isActive = true;
    public $isPinned = false;

    // Lists for dropdowns
    public $typesList = [
        'general' => 'General',
        'program' => 'Program',
        'distribution' => 'Distribution',
        'emergency' => 'Emergency',
        'maintenance' => 'Maintenance'
    ];

    public $prioritiesList = [
        'low' => 'Low',
        'normal' => 'Normal',
        'high' => 'High',
        'urgent' => 'Urgent'
    ];

    public $recipientTypesList = [
        'all' => 'All Residents',
        'program_beneficiaries' => 'Program Beneficiaries Only',
    ];

    public $ayudaPrograms = [];


    /**
     * Mount the component
     */
    public function mount()
    {
        $this->loadAyudaPrograms();
    }

    /**
     * Load ayuda programs for dropdown
     */
    public function loadAyudaPrograms()
    {
        $this->ayudaPrograms = AyudaProgram::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->toArray();
    }

    /**
     * Validation rules
     */
    protected function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'announcementType' => 'required|in:general,program,distribution,emergency,maintenance',
            'announcementPriority' => 'required|in:low,normal,high,urgent',
            'recipientType' => 'required|in:all,program_beneficiaries',
            'image' => 'nullable|image|max:2048',
            'ayudaProgramId' => $this->recipientType === 'program_beneficiaries' ? 'required|exists:ayuda_programs,id' : 'nullable|exists:ayuda_programs,id',
            'publishedAt' => 'nullable|date',
            'expiresAt' => 'nullable|date|after:publishedAt',
            'isActive' => 'boolean',
            'isPinned' => 'boolean',
        ];
    }

    /**
     * Updated search - reset pagination
     */
    public function updatedSearch()
    {
        $this->resetPage();
    }

    /**
     * Toggle filters visibility
     */
    public function toggleFilters()
    {
        $this->showFilters = !$this->showFilters;
    }

    /**
     * Clear all filters
     */
    public function clearFilters()
    {
        $this->search = '';
        $this->type = '';
        $this->priority = '';
        $this->status = 'all';
        $this->resetPage();
    }

    /**
     * Sort by field
     */
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    /**
     * Open create modal
     */
    public function create()
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    /**
     * Open edit modal
     */
    public function edit($id)
    {

        $announcement = Announcement::findOrFail($id);

        $this->announcementId = $announcement->id;
        $this->title = $announcement->title;
        $this->content = $announcement->content;
        $this->announcementType = $announcement->type;
        $this->announcementPriority = $announcement->priority;
        $this->recipientType = $announcement->recipient_type ?? 'all';
        $this->existingImagePath = $announcement->image_path;
        $this->ayudaProgramId = $announcement->ayuda_program_id;
        $this->publishedAt = $announcement->published_at ? $announcement->published_at->setTimezone('Asia/Manila')->format('Y-m-d\TH:i') : null;
        $this->expiresAt = $announcement->expires_at ? $announcement->expires_at->setTimezone('Asia/Manila')->format('Y-m-d\TH:i') : null;
        $this->isActive = $announcement->is_active;
        $this->isPinned = $announcement->is_pinned;

        $this->showEditModal = true;
    }

    /**
     * Save announcement (create or update)
     */
    public function save()
    {
        $this->validate();

        try {
            $data = [
                'title' => $this->title,
                'content' => $this->content,
                'type' => $this->announcementType,
                'priority' => $this->announcementPriority,
                'recipient_type' => $this->recipientType,
                'ayuda_program_id' => $this->ayudaProgramId,
                'published_at' => $this->publishedAt
                    ? Carbon::parse($this->publishedAt, 'Asia/Manila')->utc()
                    : now(),
                'expires_at' => $this->expiresAt
                    ? Carbon::parse($this->expiresAt, 'Asia/Manila')->utc()
                    : null,
                'is_active' => $this->isActive,
                'is_pinned' => $this->isPinned,
                'created_by' => auth()->id(),
            ];

            // Handle image upload
            if ($this->image) {
                $imagePath = $this->image->store('announcements', 'public');
                $data['image_path'] = $imagePath;

                // Delete old image if updating
                if ($this->announcementId && $this->existingImagePath) {
                    Storage::disk('public')->delete($this->existingImagePath);
                }
            }

            if ($this->announcementId) {
                // Update existing announcement
                $announcement = Announcement::findOrFail($this->announcementId);
                $announcement->update($data);
                $this->success('Announcement updated successfully');
            } else {
                // Create new announcement
                $announcement = Announcement::create($data);

                // Auto-send push notification if the announcement is published now
                $isPublishedNow = $this->isActive
                    && now()->gte($data['published_at']);

                if ($isPublishedNow) {
                    SendAnnouncementNotifications::dispatch($announcement->id);
                    $this->success('Announcement created and push notifications sent');
                } else {
                    $this->success('Announcement created successfully');
                }
            }

            $this->resetForm();
            $this->showCreateModal = false;
            $this->showEditModal = false;
        } catch (\Exception $e) {
            $this->error('Error saving announcement: ' . $e->getMessage());
        }
    }

    /**
     * Confirm delete
     */
    public function confirmDelete($id)
    {
        $this->announcementId = $id;
        $this->showDeleteModal = true;
    }

    /**
     * Delete announcement
     */
    public function delete()
    {
        try {
            $announcement = Announcement::findOrFail($this->announcementId);

            // Delete image if exists
            if ($announcement->image_path) {
                Storage::disk('public')->delete($announcement->image_path);
            }

            $announcement->delete();

            $this->success('Announcement deleted successfully');
            $this->showDeleteModal = false;
            $this->resetForm();
        } catch (\Exception $e) {
            $this->error('Error deleting announcement: ' . $e->getMessage());
        }
    }

    /**
     * Toggle announcement status
     */
    public function toggleStatus($id)
    {

        try {
            $announcement = Announcement::findOrFail($id);
            $announcement->is_active = !$announcement->is_active;
            $announcement->save();

            $status = $announcement->is_active ? 'activated' : 'deactivated';
            $this->success("Announcement {$status} successfully");
        } catch (\Exception $e) {
            $this->error('Error updating status: ' . $e->getMessage());
        }
    }

    /**
     * Open the notification confirmation modal for an announcement.
     */
    public function confirmNotify($id)
    {
        $this->announcementId = $id;
        $this->showNotifyModal = true;
    }

    /**
     * Send push notification for an announcement.
     */
    public function sendNotification()
    {
        try {
            $announcement = Announcement::findOrFail($this->announcementId);
            SendAnnouncementNotifications::dispatch($announcement->id);

            $recipientLabel = $announcement->recipient_type === 'program_beneficiaries'
                ? 'eligible program beneficiaries'
                : 'all residents';
            $this->success("Push notifications are being sent to {$recipientLabel}");
            $this->showNotifyModal = false;
            $this->announcementId = null;
        } catch (\Exception $e) {
            $this->error('Error sending notifications: ' . $e->getMessage());
        }
    }

    /**
     * Toggle pinned status
     */
    public function togglePinned($id)
    {

        try {
            $announcement = Announcement::findOrFail($id);
            $announcement->is_pinned = !$announcement->is_pinned;
            $announcement->save();

            $status = $announcement->is_pinned ? 'pinned' : 'unpinned';
            $this->success("Announcement {$status} successfully");
        } catch (\Exception $e) {
            $this->error('Error updating pinned status: ' . $e->getMessage());
        }
    }

    /**
     * Reset form fields
     */
    public function resetForm()
    {
        $this->announcementId = null;
        $this->title = '';
        $this->content = '';
        $this->announcementType = 'general';
        $this->announcementPriority = 'normal';
        $this->recipientType = 'all';
        $this->image = null;
        $this->existingImagePath = null;
        $this->ayudaProgramId = null;
        $this->publishedAt = null;
        $this->expiresAt = null;
        $this->isActive = true;
        $this->isPinned = false;
        $this->resetValidation();
    }

    /**
     * Render the component
     */
    public function render()
    {

        $query = Announcement::query()
            ->with(['program:id,name,code', 'creator:id,name']);

        // Apply search
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                    ->orWhere('content', 'like', '%' . $this->search . '%');
            });
        }

        // Apply type filter
        if (!empty($this->type)) {
            $query->where('type', $this->type);
        }

        // Apply priority filter
        if (!empty($this->priority)) {
            $query->where('priority', $this->priority);
        }

        // Apply status filter
        if ($this->status === 'active') {
            $query->where('is_active', true);
        } elseif ($this->status === 'inactive') {
            $query->where('is_active', false);
        } elseif ($this->status === 'published') {
            $query->published();
        } elseif ($this->status === 'pinned') {
            $query->pinned();
        }

        // Apply sorting
        $query->orderBy($this->sortField, $this->sortDirection);

        $announcements = $query->paginate($this->perPage);

        return view('livewire.announcement-list', [
            'announcements' => $announcements,
        ]);
    }
}
