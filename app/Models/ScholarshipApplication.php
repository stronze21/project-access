<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScholarshipApplication extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_NEEDS_RESUBMISSION = 'needs_resubmission';

    public const STATUS_CONDITIONALLY_APPROVED = 'conditionally_approved';

    public const STATUS_AWARDED = 'awarded';

    public const STATUS_REJECTED = 'rejected';

    public const APPLICANT_NEW = 'new';

    public const APPLICANT_ONGOING = 'ongoing';

    public const TIER_EXCELLENCE = 'academic_excellence';

    public const TIER_ACHIEVEMENT = 'academic_achievement';

    public const ACTIVE_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SUBMITTED,
        self::STATUS_UNDER_REVIEW,
        self::STATUS_NEEDS_RESUBMISSION,
        self::STATUS_CONDITIONALLY_APPROVED,
    ];

    public const TIER_LABELS = [
        self::TIER_EXCELLENCE => 'Academic Excellence',
        self::TIER_ACHIEVEMENT => 'Academic Achievement',
    ];

    public const TIER_BENEFITS = [
        self::TIER_EXCELLENCE => 'Full tuition, monthly stipend, and book allowance',
        self::TIER_ACHIEVEMENT => '75% tuition and monthly stipend',
    ];

    protected $fillable = [
        'resident_id',
        'scholarship_program_id',
        'reference_number',
        'applicant_type',
        'status',
        'gwa',
        'award_tier',
        'rejection_reason',
        'staff_notes',
        'reviewed_by',
        'reviewed_at',
        'conditionally_approved_at',
        'physically_verified_at',
        'awarded_at',
        'submitted_at',
    ];

    protected $casts = [
        'gwa' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'conditionally_approved_at' => 'datetime',
        'physically_verified_at' => 'datetime',
        'awarded_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $application): void {
            if (! $application->reference_number) {
                $application->reference_number = 'ACSP-'.now()->format('Ymd').'-'.strtoupper(substr(md5(uniqid('', true)), 0, 6));
            }
        });
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(ScholarshipProgram::class, 'scholarship_program_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ScholarshipApplicationDocument::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ScholarshipApplicationEvent::class)->orderByDesc('created_at');
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }

    public function canResidentEditDocuments(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_NEEDS_RESUBMISSION,
        ], true);
    }

    public function canResidentSubmit(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_NEEDS_RESUBMISSION,
        ], true);
    }

    public function awardTierSuggestedFromGwa(): ?string
    {
        if ($this->gwa === null) {
            return null;
        }

        $gwa = (float) $this->gwa;
        if ($gwa >= 95) {
            return self::TIER_EXCELLENCE;
        }
        if ($gwa >= 90) {
            return self::TIER_ACHIEVEMENT;
        }

        return null;
    }

    public function awardTierLabel(): ?string
    {
        return self::TIER_LABELS[$this->award_tier] ?? null;
    }

    public function awardTierBenefits(): ?string
    {
        return self::TIER_BENEFITS[$this->award_tier] ?? null;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_SUBMITTED => 'Submitted',
            self::STATUS_UNDER_REVIEW => 'Under Review',
            self::STATUS_NEEDS_RESUBMISSION => 'Needs Resubmission',
            self::STATUS_CONDITIONALLY_APPROVED => 'Conditionally Approved',
            self::STATUS_AWARDED => 'Awarded',
            self::STATUS_REJECTED => 'Rejected',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }
}
