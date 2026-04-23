<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class EligibilityCriteria extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'eligibility_criteria';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ayuda_program_id',
        'criterion_name',
        'criterion_type',
        'operator',
        'value',
        'is_required',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_required' => 'boolean',
    ];

    /**
     * Get the ayuda program that owns the criterion.
     */
    public function ayudaProgram(): BelongsTo
    {
        return $this->belongsTo(AyudaProgram::class, 'ayuda_program_id');
    }

    /**
     * Check if a resident meets this criterion.
     *
     * @param Resident $resident
     * @return bool
     */
    public function checkEligibility(Resident $resident): bool
    {
        $residentValue = $this->getResidentValue($resident);

        // Skip non-required criteria if value is null/empty
        if (!$this->is_required && ($residentValue === null || $residentValue === '')) {
            return true;
        }

        return $this->compareValues($residentValue);
    }

    /**
     * Get the relevant resident value for this criterion.
     *
     * @param Resident $resident
     * @return mixed
     */
    protected function getResidentValue(Resident $resident)
    {
        switch ($this->criterion_type) {
            case 'age':
                return $resident->getAge();

            case 'gender':
                return $resident->gender;

            case 'civil_status':
                return $resident->civil_status;

            case 'income':
                return $resident->monthly_income;

            case 'household_income':
                return $resident->household ? $resident->household->monthly_income : null;

            case 'household_size':
                return $resident->household ? $resident->household->member_count : null;

            case 'location':
            case 'barangay':
                return $resident->household ? $resident->household->barangay : null;

            case 'city':
                return $resident->household ? $resident->household->city_municipality : null;

            case 'voter':
                return $resident->is_registered_voter;

            case 'pwd':
                return $resident->is_pwd;

            case 'senior':
                return $resident->is_senior_citizen;

            case 'solo_parent':
                return $resident->is_solo_parent;

            case 'pregnant':
                return $resident->is_pregnant;

            case 'lactating':
                return $resident->is_lactating;

            case 'indigenous':
                return $resident->is_indigenous;

            case 'occupation':
                return $resident->occupation;

            case 'education':
                return $resident->educational_attainment;

            default:
                return null;
        }
    }

    /**
     * Compare the resident value with the criterion value based on the operator.
     *
     * @param mixed $residentValue
     * @return bool
     */
    protected function compareValues($residentValue): bool
    {
        $criterionValue = $this->value;

        // Convert boolean strings to actual booleans
        if (in_array($criterionValue, ['true', 'false'])) {
            $criterionValue = $criterionValue === 'true';
        }

        switch ($this->operator) {
            case '=':
            case 'equals':
            case 'equal':
                return $residentValue == $criterionValue;

            case '!=':
            case 'not_equals':
            case 'not_equal':
                return $residentValue != $criterionValue;

            case '>':
            case 'greater_than':
                return $residentValue > $criterionValue;

            case '>=':
            case 'greater_than_or_equal':
                return $residentValue >= $criterionValue;

            case '<':
            case 'less_than':
                return $residentValue < $criterionValue;

            case '<=':
            case 'less_than_or_equal':
                return $residentValue <= $criterionValue;

            case 'in':
                $values = explode(',', $criterionValue);
                return in_array($residentValue, $values);

            case 'not_in':
                $values = explode(',', $criterionValue);
                return !in_array($residentValue, $values);

            case 'contains':
                return is_string($residentValue) &&
                       is_string($criterionValue) &&
                       str_contains($residentValue, $criterionValue);

            case 'starts_with':
                return is_string($residentValue) &&
                       is_string($criterionValue) &&
                       str_starts_with($residentValue, $criterionValue);

            case 'ends_with':
                return is_string($residentValue) &&
                       is_string($criterionValue) &&
                       str_ends_with($residentValue, $criterionValue);

            case 'between':
                list($min, $max) = explode(',', $criterionValue);
                return $residentValue >= $min && $residentValue <= $max;

            default:
                return false;
        }
    }
}