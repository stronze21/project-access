<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class AyudaProgram extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ayuda_programs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'type',
        'amount',
        'goods_description',
        'services_description',
        'start_date',
        'end_date',
        'frequency',
        'distribution_count',
        'total_budget',
        'budget_used',
        'max_beneficiaries',
        'current_beneficiaries',
        'requires_verification',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'amount' => 'decimal:2',
        'total_budget' => 'decimal:2',
        'budget_used' => 'decimal:2',
        'requires_verification' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($program) {
            if (!$program->code) {
                $program->code = self::generateProgramCode($program->name);
            }
        });
    }

    /**
     * Generate a unique program code.
     *
     * @param string $name
     * @return string
     */
    public static function generateProgramCode(string $name): string
    {
        // Create initials from the program name
        $words = explode(' ', $name);
        $initials = '';

        foreach ($words as $word) {
            if (strlen($word) > 0) {
                $initials .= strtoupper(substr($word, 0, 1));
            }
        }

        // Limit to 3-5 characters
        $initials = substr($initials, 0, 5);

        // Add year and month
        $yearMonth = date('ym');
        $baseCode = $initials . '-' . $yearMonth;

        // Check for uniqueness
        $count = self::where('code', 'like', $baseCode . '%')->count();

        if ($count > 0) {
            return $baseCode . '-' . ($count + 1);
        }

        return $baseCode;
    }

    /**
     * Get all eligibility criteria for the program.
     */
    public function eligibilityCriteria(): HasMany
    {
        return $this->hasMany(EligibilityCriteria::class, 'ayuda_program_id');
    }

    /**
     * Get all distribution batches for the program.
     */
    public function distributionBatches(): HasMany
    {
        return $this->hasMany(DistributionBatch::class, 'ayuda_program_id');
    }

    /**
     * Get all distributions for the program.
     */
    public function distributions(): HasMany
    {
        return $this->hasMany(Distribution::class, 'ayuda_program_id');
    }

    /**
     * Check if the program is currently active.
     *
     * @return bool
     */
    public function isCurrentlyActive(): bool
    {
        $today = Carbon::today();

        if (!$this->is_active) {
            return false;
        }

        if ($today->lt($this->start_date)) {
            return false;
        }

        if ($this->end_date && $today->gt($this->end_date)) {
            return false;
        }

        if ($this->max_beneficiaries && $this->current_beneficiaries >= $this->max_beneficiaries) {
            return false;
        }

        if ($this->total_budget && $this->budget_used >= $this->total_budget) {
            return false;
        }

        return true;
    }

    /**
     * Get the program's status.
     *
     * @return string
     */
    public function getStatusAttribute(): string
    {
        $today = Carbon::today();

        if (!$this->is_active) {
            return 'Inactive';
        }

        if ($today->lt($this->start_date)) {
            return 'Upcoming';
        }

        if ($this->end_date && $today->gt($this->end_date)) {
            return 'Completed';
        }

        if ($this->max_beneficiaries && $this->current_beneficiaries >= $this->max_beneficiaries) {
            return 'Full';
        }

        if ($this->total_budget && $this->budget_used >= $this->total_budget) {
            return 'Budget Exhausted';
        }

        return 'Active';
    }

    /**
     * Get the program's progress percentage.
     *
     * @return float
     */
    public function getProgressPercentageAttribute(): float
    {
        if ($this->max_beneficiaries && $this->max_beneficiaries > 0) {
            return min(100, round(($this->current_beneficiaries / $this->max_beneficiaries) * 100, 2));
        }

        if ($this->total_budget && $this->total_budget > 0) {
            return min(100, round(($this->budget_used / $this->total_budget) * 100, 2));
        }

        return 0;
    }

    /**
     * Get the program's remaining budget.
     *
     * @return float
     */
    public function getRemainingBudgetAttribute(): float
    {
        if (!$this->total_budget) {
            return 0;
        }

        return max(0, $this->total_budget - $this->budget_used);
    }

    /**
     * Get the program's remaining beneficiary slots.
     *
     * @return int|null
     */
    public function getRemainingBeneficiarySlots(): ?int
    {
        if (!$this->max_beneficiaries) {
            return null;
        }

        return max(0, $this->max_beneficiaries - $this->current_beneficiaries);
    }

    /**
     * Check if a resident is eligible for this program.
     *
     * @param Resident $resident
     * @return bool
     */
    public function isResidentEligible(Resident $resident): bool
    {
        // Check if resident has already received this aid
        $alreadyReceived = Distribution::where('ayuda_program_id', $this->id)
            ->where('resident_id', $resident->id)
            ->where('status', 'distributed')
            ->exists();

        if ($alreadyReceived) {
            return false;
        }

        // Check each criterion
        foreach ($this->eligibilityCriteria as $criterion) {
            if (!$criterion->checkEligibility($resident)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Update budget and beneficiary counts when a distribution is processed.
     *
     * @param float $amount
     * @return void
     */
    public function recordDistribution(float $amount = null): void
    {
        $this->current_beneficiaries += 1;

        if ($amount !== null) {
            $this->budget_used += $amount;
        } else if ($this->amount) {
            $this->budget_used += $this->amount;
        }

        $this->save();
    }

    /**
     * Scope a query to only include active programs.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        $today = Carbon::today()->format('Y-m-d');

        return $query->where('is_active', true)
                     ->where('start_date', '<=', $today)
                     ->where(function ($q) use ($today) {
                         $q->whereNull('end_date')
                           ->orWhere('end_date', '>=', $today);
                     });
    }
}