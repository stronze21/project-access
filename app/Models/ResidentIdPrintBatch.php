<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResidentIdPrintBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_number',
        'user_id',
        'barangay',
        'status_filter',
        'sector_filter',
        'alphabetical',
        'exclude_printed',
        'batch_number',
        'total_matching',
        'resident_count',
        'status',
        'printed_at',
        'reprint_start_batch_id',
    ];

    protected $casts = [
        'sector_filter' => 'array',
        'alphabetical' => 'boolean',
        'exclude_printed' => 'boolean',
        'printed_at' => 'datetime',
        'reprint_start_batch_id' => 'integer',
    ];

    public function scopeForSectors(\Illuminate\Database\Eloquent\Builder $query, array $sectors): void
    {
        $query->where('sector_filter', $sectors ? json_encode($sectors) : null);
    }

    public function getSectorLabelAttribute(): string
    {
        return $this->sector_filter ? implode(', ', $this->sector_filter) : 'All sectors';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ResidentIdPrintBatchItem::class, 'print_batch_id');
    }
}
