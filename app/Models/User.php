<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Jetstream\HasProfilePhoto;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    public const ROLE_ADMIN = 'Admin';
    public const ROLE_SUPER_ADMIN = 'Super Admin';
    public const ROLE_MAYOR = 'Mayor';
    public const ROLE_DEPARTMENT_HEAD = 'Department Head';
    public const ROLE_ACTION_OFFICER = 'Action Officer';
    public const ROLE_CITIZEN = 'Citizen';

    public const ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_SUPER_ADMIN,
        self::ROLE_MAYOR,
        self::ROLE_DEPARTMENT_HEAD,
        self::ROLE_ACTION_OFFICER,
        self::ROLE_CITIZEN,
    ];

    use HasApiTokens;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasRoles; // Add this trait for Spatie Permissions

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'department_id',
        'password',
        'profile_photo_path',
        'sentiment_posting_banned_at',
        'sentiment_posting_ban_reason',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'sentiment_posting_banned_at' => 'datetime',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function submittedComplaints(): HasMany
    {
        return $this->hasMany(Complaint::class, 'submitted_by_user_id');
    }

    public function assignedComplaints(): HasMany
    {
        return $this->hasMany(Complaint::class, 'assigned_officer_id');
    }

    public function sentimentPosts(): HasMany
    {
        return $this->hasMany(SentimentPost::class, 'user_id');
    }

    public function sentimentComments(): HasMany
    {
        return $this->hasMany(SentimentComment::class, 'user_id');
    }

    public function sentimentReactions(): HasMany
    {
        return $this->hasMany(SentimentReaction::class, 'user_id');
    }

    public function followingUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'sentiment_follows',
            'follower_user_id',
            'followed_user_id'
        )->withTimestamps();
    }

    public function followerUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'sentiment_follows',
            'followed_user_id',
            'follower_user_id'
        )->withTimestamps();
    }

    public function getRoleAttribute(): string
    {
        $roleName = $this->getRoleNames()->first();

        return $this->bosesmotoRoleFromSpatieRole($roleName);
    }

    public function isAdmin(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_ADMIN,
            self::ROLE_SUPER_ADMIN,
            'admin',
            'system-administrator',
        ]);
    }

    public function isMayor(): bool
    {
        return $this->hasAnyRole([self::ROLE_MAYOR, 'mayor']);
    }

    public function isDepartmentHead(): bool
    {
        return $this->hasAnyRole([self::ROLE_DEPARTMENT_HEAD, 'department-head']);
    }

    public function isActionOfficer(): bool
    {
        return $this->hasAnyRole([self::ROLE_ACTION_OFFICER, 'action-officer']);
    }

    public function isCitizen(): bool
    {
        return $this->hasAnyRole([self::ROLE_CITIZEN, 'citizen']);
    }

    public function isInternalUser(): bool
    {
        return $this->isAdmin() || $this->isMayor() || $this->isDepartmentHead() || $this->isActionOfficer();
    }

    public function isSentimentPostingBanned(): bool
    {
        return $this->sentiment_posting_banned_at !== null;
    }

    public function belongsToDepartment(?int $departmentId): bool
    {
        if ($departmentId === null) {
            return false;
        }

        return (int) $this->department_id === (int) $departmentId;
    }

    public function profilePhotoUrl(): ?string
    {
        return $this->profile_photo_url;
    }

    public function getProfilePhotoUrlAttribute(): string
    {
        return $this->profile_photo_path
            ? Storage::disk($this->profilePhotoDisk())->url($this->profile_photo_path)
            : $this->defaultProfilePhotoUrl();
    }

    public function profileInitials(): string
    {
        $name = trim((string) $this->name);
        if ($name === '') {
            return 'U';
        }

        $parts = preg_split('/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $initials = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= strtoupper(substr($part, 0, 1));
        }

        return $initials !== '' ? $initials : 'U';
    }

    private function bosesmotoRoleFromSpatieRole(?string $roleName): string
    {
        return match ($roleName) {
            'system-administrator' => self::ROLE_SUPER_ADMIN,
            'admin' => self::ROLE_ADMIN,
            'mayor' => self::ROLE_MAYOR,
            'department-head' => self::ROLE_DEPARTMENT_HEAD,
            'action-officer' => self::ROLE_ACTION_OFFICER,
            'citizen' => self::ROLE_CITIZEN,
            default => $roleName ?: self::ROLE_CITIZEN,
        };
    }
}
