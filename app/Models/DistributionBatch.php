<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DistributionBatch extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'batch_number',
        'ayuda_program_id',
        'location',
        'batch_date',
        'start_time',
        'end_time',
        'target_beneficiaries',
        'actual_beneficiaries',
        'total_amount',
        'status',
        'created_by',
        'updated_by',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'batch_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'target_beneficiaries' => 'integer',
        'actual_beneficiaries' => 'integer',
        'total_amount' => 'decimal:2',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($batch) {
            if (!$batch->batch_number) {
                $batch->batch_number = self::generateBatchNumber($batch->ayuda_program_id);
            }
        });
    }

    /**
     * Generate a unique batch number.
     *
     * @param int $programId
     * @return string
     */
    public static function generateBatchNumber(int $programId): string
    {
        $program = AyudaProgram::find($programId);
        $programCode = $program ? $program->code : 'BATCH';

        $today = now()->format('Ymd');
        $baseBatchNumber = "{$programCode}-{$today}";

        $count = self::where('batch_number', 'like', $baseBatchNumber . '%')->count();

        if ($count > 0) {
            return $baseBatchNumber . '-' . ($count + 1);
        }

        return $baseBatchNumber . '-1';
    }

    /**
     * Get the ayuda program for this batch.
     */
    public function ayudaProgram(): BelongsTo
    {
        return $this->belongsTo(AyudaProgram::class, 'ayuda_program_id');
    }

    /**
     * Get the user who created this batch.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this batch.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get all distributions in this batch.
     */
    public function distributions(): HasMany
    {
        return $this->hasMany(Distribution::class, 'batch_id');
    }

    /**
     * Update the actual beneficiaries count and total amount.
     */
    public function updateStats(): void
    {
        $this->actual_beneficiaries = $this->distributions()
            ->where('status', 'distributed')
            ->count();

        $this->total_amount = $this->distributions()
            ->where('status', 'distributed')
            ->sum('amount');

        $this->save();
    }

    /**
     * Get the completion percentage.
     */
    public function getCompletionPercentageAttribute(): float
    {
        if (!$this->target_beneficiaries || $this->target_beneficiaries == 0) {
            return 0;
        }

        return min(100, round(($this->actual_beneficiaries / $this->target_beneficiaries) * 100, 2));
    }

    /**
     * Scope a query to only include active or upcoming batches.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['scheduled', 'ongoing']);
    }

    /**
     * Scope a query to only include today's batches.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeToday($query)
    {
        return $query->whereDate('batch_date', now()->toDateString());
    }
}