<?php

namespace App\Livewire;

use App\Models\ResidentIdentityChangeRequest;
use App\Models\ResidentNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class ResidentIdentityChangeRequests extends Component
{
    use Toast, WithPagination;

    #[Url]
    public string $status = 'pending';

    #[Url]
    public string $type = 'all';

    #[Url]
    public string $search = '';

    public bool $showDenyModal = false;

    public ?int $denyingRequestId = null;

    public string $denialReason = '';

    public function mount(): void
    {
        $this->authorizeReview();
    }

    public function updated($property): void
    {
        if (in_array($property, ['status', 'type', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function approve(int $id): void
    {
        $this->authorizeReview();

        DB::transaction(function () use ($id): void {
            $change = ResidentIdentityChangeRequest::with('resident')->lockForUpdate()->findOrFail($id);
            abort_unless($change->status === ResidentIdentityChangeRequest::STATUS_PENDING, 409);

            if ($change->type === ResidentIdentityChangeRequest::TYPE_PHOTO) {
                abort_unless($change->requested_file_path && Storage::disk('local')->exists($change->requested_file_path), 422, 'Requested photo is unavailable.');
                $extension = pathinfo($change->requested_file_path, PATHINFO_EXTENSION) ?: 'jpg';
                $newPath = 'resident-photos/'.$change->resident->resident_id.'-'.now()->format('YmdHis').'.'.$extension;
                Storage::disk('public')->put($newPath, Storage::disk('local')->get($change->requested_file_path));
                $oldPath = $change->resident->photo_path;
                $change->resident->update(['photo_path' => $newPath]);
                if ($oldPath && $oldPath !== $newPath) {
                    Storage::disk('public')->delete($oldPath);
                }
                Storage::disk('local')->delete($change->requested_file_path);
                $change->requested_file_path = null;
            } else {
                abort_unless($change->requested_signature, 422, 'Requested signature is unavailable.');
                $change->resident->update([
                    'signature' => $change->requested_signature,
                    'signature_status' => 'completed',
                ]);
                $change->requested_signature = null;
            }

            $change->status = ResidentIdentityChangeRequest::STATUS_APPROVED;
            $change->reviewed_by = auth()->id();
            $change->reviewed_at = now();
            $change->save();

            $this->notifyResident($change, true);
        });

        $this->success('Replacement request approved and resident identity record updated.');
    }

    public function openDenyModal(int $id): void
    {
        $this->authorizeReview();
        $change = ResidentIdentityChangeRequest::findOrFail($id);
        abort_unless($change->status === ResidentIdentityChangeRequest::STATUS_PENDING, 409);
        $this->denyingRequestId = $id;
        $this->denialReason = '';
        $this->showDenyModal = true;
        $this->resetValidation();
    }

    public function deny(): void
    {
        $this->authorizeReview();
        $this->validate(['denialReason' => ['required', 'string', 'min:5', 'max:2000']]);

        DB::transaction(function (): void {
            $change = ResidentIdentityChangeRequest::with('resident')->lockForUpdate()->findOrFail($this->denyingRequestId);
            abort_unless($change->status === ResidentIdentityChangeRequest::STATUS_PENDING, 409);

            if ($change->requested_file_path) {
                Storage::disk('local')->delete($change->requested_file_path);
            }

            $change->update([
                'status' => ResidentIdentityChangeRequest::STATUS_DENIED,
                'requested_file_path' => null,
                'requested_signature' => null,
                'reviewed_by' => auth()->id(),
                'review_reason' => $this->denialReason,
                'reviewed_at' => now(),
            ]);

            $this->notifyResident($change, false);
        });

        $this->showDenyModal = false;
        $this->denyingRequestId = null;
        $this->denialReason = '';
        $this->success('Replacement request denied. The resident can see the reason.');
    }

    public function render()
    {
        $requests = ResidentIdentityChangeRequest::query()
            ->with(['resident.household', 'reviewer'])
            ->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))
            ->when($this->type !== 'all', fn ($query) => $query->where('type', $this->type))
            ->when(trim($this->search) !== '', function ($query): void {
                $term = '%'.trim($this->search).'%';
                $query->where(function ($query) use ($term): void {
                    $query->where('reference_number', 'like', $term)
                        ->orWhereHas('resident', fn ($resident) => $resident->where('resident_id', 'like', $term)
                            ->orWhere('first_name', 'like', $term)->orWhere('last_name', 'like', $term));
                });
            })
            ->latest()
            ->paginate(15);

        return view('livewire.resident-identity-change-requests', [
            'requests' => $requests,
            'counts' => ResidentIdentityChangeRequest::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ])->layout('layouts.app');
    }

    private function notifyResident(ResidentIdentityChangeRequest $change, bool $approved): void
    {
        ResidentNotification::create([
            'resident_id' => $change->resident_id,
            'title' => ucfirst($change->type).' replacement request '.($approved ? 'approved' : 'denied'),
            'body' => $approved
                ? "Your {$change->type} replacement request {$change->reference_number} was approved."
                : "Your {$change->type} replacement request {$change->reference_number} was denied: {$change->review_reason}",
            'type' => 'identity-change-request',
            'data' => ['request_id' => $change->id, 'status' => $change->status],
        ]);
    }

    private function authorizeReview(): void
    {
        abort_unless(auth()->user()?->can('edit-residents'), 403);
    }
}
