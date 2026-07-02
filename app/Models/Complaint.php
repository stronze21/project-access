<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Complaint extends Model
{
    use HasFactory;

    public const DISPLAY_TIMEZONE = 'Asia/Manila';

    public const STATUS_RECEIVED = 'received';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    public const VISIBILITY_PUBLIC_NAMED = 'public_named';
    public const VISIBILITY_PUBLIC_ANONYMOUS = 'public_anonymous';
    public const VISIBILITY_PRIVATE = 'private';

    public const MODERATION_NORMAL = 'normal';
    public const MODERATION_SPAM = 'spam';
    public const MODERATION_ABUSIVE = 'abusive';
    public const MODERATION_INVALID = 'invalid';

    protected $fillable = [
        'reference_code',
        'submitted_by_user_id',
        'is_anonymous_submission',
        'reporter_name',
        'reporter_email',
        'title',
        'short_summary',
        'description',
        'category_id',
        'visibility',
        'barangay_id',
        'latitude',
        'longitude',
        'status',
        'priority',
        'assigned_department_id',
        'assigned_officer_id',
        'assigned_by_user_id',
        'moderation_status',
        'moderation_reason',
        'is_escalated',
        'acknowledged_at',
        'first_action_at',
        'resolved_at',
        'closed_at',
        'resolution_summary',
        'citizen_confirmed_at',
        'auto_closed_at',
        'due_ack_at',
        'due_first_action_at',
        'due_resolution_at',
        'ack_overdue_notified_at',
        'first_action_overdue_notified_at',
        'resolution_overdue_notified_at',
        'mayor_notified_at',
        'submitted_ip',
        'submitted_device_hash',
        'support_count',
    ];

    protected $casts = [
        'is_anonymous_submission' => 'boolean',
        'is_escalated' => 'boolean',
        'acknowledged_at' => 'datetime',
        'first_action_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'citizen_confirmed_at' => 'datetime',
        'auto_closed_at' => 'datetime',
        'due_ack_at' => 'datetime',
        'due_first_action_at' => 'datetime',
        'due_resolution_at' => 'datetime',
        'ack_overdue_notified_at' => 'datetime',
        'first_action_overdue_notified_at' => 'datetime',
        'resolution_overdue_notified_at' => 'datetime',
        'mayor_notified_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ComplaintCategory::class, 'category_id');
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(ComplaintBarangay::class, 'barangay_id');
    }

    public function assignedDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'assigned_department_id');
    }

    public function assignedOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_officer_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ComplaintAssignment::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(ComplaintStatusHistory::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ComplaintComment::class);
    }

    public function visibleComments(): HasMany
    {
        return $this->comments()->where('is_hidden', false);
    }

    public function supports(): HasMany
    {
        return $this->hasMany(ComplaintSupport::class);
    }

    public function internalNotes(): HasMany
    {
        return $this->hasMany(ComplaintInternalNote::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ComplaintAttachment::class);
    }

    public function previewImageAttachment(): HasOne
    {
        return $this->hasOne(ComplaintAttachment::class)
            ->where('type', ComplaintAttachment::TYPE_EVIDENCE)
            ->whereIn('mime_type', ['image/jpeg', 'image/png', 'image/webp'])
            ->whereNotIn('virus_scan_status', [ComplaintAttachment::SCAN_INFECTED, ComplaintAttachment::SCAN_FAILED]);
    }

    public function officials(): BelongsToMany
    {
        return $this->belongsToMany(PublicOfficial::class, 'complaint_public_official');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(ComplaintAuditLog::class);
    }

    public function scopePublicListing(Builder $query): Builder
    {
        return $query
            ->where('moderation_status', self::MODERATION_NORMAL)
            ->whereIn('visibility', [self::VISIBILITY_PUBLIC_NAMED, self::VISIBILITY_PUBLIC_ANONYMOUS]);
    }

    public function scopeNotClosed(Builder $query): Builder
    {
        return $query->where('status', '!=', self::STATUS_CLOSED);
    }

    public function isPubliclyVisible(): bool
    {
        return $this->moderation_status === self::MODERATION_NORMAL
            && in_array($this->visibility, [self::VISIBILITY_PUBLIC_NAMED, self::VISIBILITY_PUBLIC_ANONYMOUS], true);
    }

    public function canBeEditedByCitizen(User $user): bool
    {
        return (int) $this->submitted_by_user_id === (int) $user->id
            && $this->status === self::STATUS_RECEIVED
            && $this->assigned_department_id === null;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function submittedAtManila(): ?Carbon
    {
        return $this->created_at?->copy()->timezone(self::DISPLAY_TIMEZONE);
    }

    public function accomplishedAt(): ?Carbon
    {
        return $this->resolved_at?->copy() ?? $this->closed_at?->copy();
    }

    public function accomplishedAtManila(): ?Carbon
    {
        return $this->accomplishedAt()?->timezone(self::DISPLAY_TIMEZONE);
    }

    public function runningTimeLabel(): string
    {
        if ($this->created_at === null) {
            return 'N/A';
        }

        $accomplishedAt = $this->accomplishedAt();
        $end = $accomplishedAt ?? Carbon::now();
        $duration = $this->formatDuration($this->created_at, $end);

        return $accomplishedAt ? $duration : $duration.' (ongoing)';
    }

    public function timeMetricTitle(): string
    {
        return $this->accomplishedAt() ? 'Time Resolved' : 'Running Time';
    }

    private function formatDuration(Carbon $start, Carbon $end): string
    {
        $seconds = max(0, $start->diffInSeconds($end, false));
        $days = intdiv($seconds, 86400);
        $seconds %= 86400;
        $hours = intdiv($seconds, 3600);
        $seconds %= 3600;
        $minutes = intdiv($seconds, 60);

        $parts = [];

        if ($days > 0) {
            $parts[] = $days.' day'.($days === 1 ? '' : 's');
        }

        if ($hours > 0) {
            $parts[] = $hours.' hr'.($hours === 1 ? '' : 's');
        }

        if ($minutes > 0) {
            $parts[] = $minutes.' min'.($minutes === 1 ? '' : 's');
        }

        return empty($parts) ? 'Under 1 min' : implode(' ', $parts);
    }
}
