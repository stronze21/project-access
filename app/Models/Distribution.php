<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\Resident;
use App\Models\Household;
use App\Models\AyudaProgram;
use App\Models\DistributionBatch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Distribution extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'reference_number',
        'ayuda_program_id',
        'resident_id',
        'household_id',
        'batch_id',
        'distributed_by',
        'verified_by',
        'distribution_date',
        'amount',
        'goods_details',
        'services_details',
        'status',
        'receipt_path',
        'notes',
        'verification_data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'distribution_date' => 'date',
        'amount' => 'decimal:2',
        'verification_data' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected function createdAt(): Attribute
    {
        return Attribute::make(
            get: fn($value) => \Carbon\Carbon::parse($value)
                ->timezone('Asia/Manila'),
        );
    }

    protected function updatedAt(): Attribute
    {
        return Attribute::make(
            get: fn($value) => \Carbon\Carbon::parse($value)
                ->timezone('Asia/Manila'),
        );
    }

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($distribution) {
            if (!$distribution->reference_number) {
                $distribution->reference_number = self::generateReferenceNumber();
            }

            // Set household_id based on resident if not provided
            if (!$distribution->household_id && $distribution->resident_id) {
                $resident = Resident::find($distribution->resident_id);
                if ($resident && $resident->household_id) {
                    $distribution->household_id = $resident->household_id;
                }
            }
        });

        static::created(function ($distribution) {
            // Update the program's statistics
            if ($distribution->status === 'distributed' && $distribution->ayuda_program_id) {
                $distribution->ayudaProgram->recordDistribution($distribution->amount);
            }

            // Update batch statistics
            if ($distribution->batch_id) {
                $distribution->batch->updateStats();
            }
        });

        static::updated(function ($distribution) {
            // If status changed to 'distributed', update program and batch statistics
            if ($distribution->isDirty('status') && $distribution->status === 'distributed') {
                $distribution->ayudaProgram->recordDistribution($distribution->amount);

                if ($distribution->batch_id) {
                    $distribution->batch->updateStats();
                }
            }
        });
    }

    /**
     * Generate a unique reference number.
     *
     * @return string
     */
    public static function generateReferenceNumber($date = null): string
    {
        $referenceDate = $date ? Carbon::parse($date) : now();
        $prefix = 'D-' . $referenceDate->format('Ymd') . '-';

        $lastDistribution = self::withTrashed()
            ->where('reference_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        $sequence = 1;
        if ($lastDistribution) {
            $parts = explode('-', $lastDistribution->reference_number);
            $sequence = (int)end($parts) + 1;
        }

        return $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get the ayuda program for this distribution.
     */
    public function ayudaProgram(): BelongsTo
    {
        return $this->belongsTo(AyudaProgram::class, 'ayuda_program_id');
    }

    /**
     * Get the resident who received this distribution.
     */
    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class, 'resident_id');
    }

    /**
     * Get the household for this distribution.
     */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class, 'household_id');
    }

    /**
     * Get the batch for this distribution.
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(DistributionBatch::class, 'batch_id');
    }

    /**
     * Get the user who distributed this aid.
     */
    public function distributor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'distributed_by');
    }

    /**
     * Get the user who verified this distribution.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Scope a query to only include distributions with specific status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to include distributions by date range.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $startDate
     * @param string|null $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDateRange($query, string $startDate, string $endDate = null)
    {
        $query->whereDate('created_at', '>=', $startDate);

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return $query;
    }
}
