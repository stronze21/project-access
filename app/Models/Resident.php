<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Carbon\Carbon;

class Resident extends Authenticatable
{
    use HasApiTokens, HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'household_id',
        'signature_status',
        'resident_id',
        'qr_code',
        'rfid_number',
        'first_name',
        'last_name',
        'middle_name',
        'suffix',
        'birth_date',
        'birthplace',
        'blood_type',
        'gender',
        'civil_status',
        'contact_number',
        'email',
        'password',
        'photo_path',
        'id_card_path',
        'relationship_to_head',
        'occupation',
        'monthly_income',
        'educational_attainment',
        'is_registered_voter',
        'precinct_no',
        'is_pwd',
        'is_senior_citizen',
        'is_solo_parent',
        'is_pregnant',
        'is_lactating',
        'is_indigenous',
        'is_4ps',
        'special_sector',
        'is_active',
        'notes',
        'signature',
        'date_issue',
        'last_login_at',

        'emergency_contact_name',
        'emergency_contact_relationship',
        'emergency_contact_number',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'birth_date' => 'date',
        'date_issue' => 'date',
        'last_login_at' => 'datetime',
        'monthly_income' => 'decimal:2',
        'password' => 'hashed',
        'is_registered_voter' => 'boolean',
        'is_pwd' => 'boolean',
        'is_senior_citizen' => 'boolean',
        'is_solo_parent' => 'boolean',
        'is_pregnant' => 'boolean',
        'is_lactating' => 'boolean',
        'is_indigenous' => 'boolean',
        'is_active' => 'boolean',
        'is_4ps' => 'boolean',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($resident) {
            if (!$resident->resident_id) {
                $resident->resident_id = self::generateResidentId();
            }

            // Auto-set is_senior_citizen based on birth_date
            if ($resident->birth_date && $resident->getAge() >= 60) {
                $resident->is_senior_citizen = true;
            }

            // Auto-set date_issue to current date if not provided
            if (!$resident->date_issue) {
                $resident->date_issue = now();
            }
        });

        static::saved(function ($resident) {
            // Update household member count if assigned to a household
            if ($resident->household_id) {
                $resident->household->updateMemberCount();
                $resident->household->calculateTotalIncome();
            }
        });
    }

    /**
     * Generate a unique resident ID.
     *
     * @return string
     */
    public static function generateResidentId(): string
    {
        $prefix = 'R-' . date('Ym') . '-';
        $lastResident = self::where('resident_id', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        $sequence = 1;
        if ($lastResident) {
            $parts = explode('-', $lastResident->resident_id);
            $sequence = (int)end($parts) + 1;
        }

        return $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate a unique QR code for the resident.
     *
     * @return string
     */
    public function generateQrCode(): string
    {
        $this->qr_code = 'QR-R-' . strtoupper(substr(md5($this->id . time()), 0, 10));
        $this->save();

        return $this->qr_code;
    }

    /**
     * Get the resident's age.
     *
     * @return int
     */
    public function getAge(): int
    {
        return $this->birth_date ? Carbon::parse($this->birth_date)->age : 0;
    }

    /**
     * Get the resident's full name.
     */
    public function getFullNameAttribute(): string
    {
        $parts = [
            $this->last_name . ',',
            $this->first_name,
            $this->suffix,
            $this->middle_name ? substr($this->middle_name, 0, 1) . '.' : null,
        ];

        return implode(' ', array_filter($parts));
    }

    public function getEmergencyContactAttribute(): string
    {
        return "{$this->emergency_contact_name}/{$this->emergency_contact_number}";
    }

    /**
     * Get the household this resident belongs to.
     */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    /**
     * Get all distributions for this resident.
     */
    public function distributions(): HasMany
    {
        return $this->hasMany(Distribution::class);
    }

    /**
     * Check if this resident is eligible for a given ayuda program.
     *
     * @param AyudaProgram $program
     * @return bool
     */
    public function isEligibleFor(AyudaProgram $program): bool
    {
        foreach ($program->eligibilityCriteria as $criterion) {
            if ($criterion->is_required && !$criterion->checkEligibility($this)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get all notifications for this resident.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(ResidentNotification::class);
    }

    /**
     * Get all device tokens for this resident.
     */
    public function deviceTokens(): HasMany
    {
        return $this->hasMany(ResidentDeviceToken::class);
    }

    /**
     * Get the resident value for a given criterion.
     *
     * @param EligibilityCriteria $criterion
     * @return mixed
     */
    protected function getValueForCriterion(EligibilityCriteria $criterion)
    {
        switch ($criterion->criterion_type) {
            case 'age':
                return $this->getAge();
            case 'income':
                return $this->monthly_income;
            case 'household_income':
                return $this->household ? $this->household->monthly_income : 0;
            case 'location':
                return $this->household ? $this->household->barangay : '';
            case 'senior_citizen':
                return $this->is_senior_citizen;
            case 'pwd':
                return $this->is_pwd;
            case 'solo_parent':
                return $this->is_solo_parent;
            case 'indigenous':
                return $this->is_indigenous;
            case 'special_sector':
                return $this->special_sector;
            case '4Ps':
                return $this->is_4ps;
            default:
                return null;
        }
    }
}
