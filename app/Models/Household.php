<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Household extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'household_id',
        'address',
        'barangay',
        'barangay_code',
        'city_municipality',
        'city_municipality_code',
        'province',
        'province_code',
        'postal_code',
        'region',
        'region_code',
        'monthly_income',
        'member_count',
        'dwelling_type',
        'has_electricity',
        'has_water_supply',
        'is_active',
        'notes',
        'qr_code',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'monthly_income' => 'decimal:2',
        'has_electricity' => 'boolean',
        'has_water_supply' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Generate a unique household ID.
     *
     * @return string
     */
    public static function generateHouseholdId(): string
    {
        $prefix = 'HH-' . date('Ym') . '-';
        $lastHousehold = self::where('household_id', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        $sequence = 1;
        if ($lastHousehold) {
            $parts = explode('-', $lastHousehold->household_id);
            $sequence = (int)end($parts) + 1;
        }

        return $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get all residents belonging to this household.
     */
    public function residents(): HasMany
    {
        return $this->hasMany(Resident::class);
    }

    /**
     * Get the household head.
     */
    public function householdHead()
    {
        return $this->residents()->where('relationship_to_head', 'head')->first();
    }

    /**
     * Get all distributions for this household.
     */
    public function distributions(): HasMany
    {
        return $this->hasMany(Distribution::class);
    }

    /**
     * Update the member count based on active residents.
     */
    public function updateMemberCount(): void
    {
        $this->member_count = $this->residents()->where('is_active', true)->count();
        $this->save();
    }

    /**
     * Calculate total household income from all resident incomes.
     */
    public function calculateTotalIncome(): float
    {
        $total = $this->residents()
            ->where('is_active', true)
            ->sum('monthly_income');

        $this->monthly_income = $total;
        $this->save();

        return $total;
    }

    /**
     * Get the full address as a string.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = [
            $this->address,
            $this->barangay,
            $this->city_municipality,
            $this->province
        ];

        if ($this->postal_code) {
            $parts[] = $this->postal_code;
        }

        return strtoupper(implode(', ', array_filter($parts)));
    }

    /**
     * Get the region information.
     */
    public function region()
    {
        return $this->region_code ? Region::where('regCode', $this->region_code)->first() : null;
    }

    /**
     * Get the province information.
     */
    public function province()
    {
        return $this->province_code ? Province::where('provCode', $this->province_code)->first() : null;
    }

    /**
     * Get the city/municipality information.
     */
    public function cityMunicipality()
    {
        return $this->city_municipality_code ? CityMunicipality::where('citymunCode', $this->city_municipality_code)->first() : null;
    }

    /**
     * Get the barangay information.
     */
    public function barangay()
    {
        return $this->barangay_code ? Barangay::where('brgyCode', $this->barangay_code)->first() : null;
    }
}