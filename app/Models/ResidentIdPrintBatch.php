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
        'exclude_printed',
        'batch_number',
        'total_matching',
        'resident_count',
        'status',
        'printed_at',
    ];

    protected $casts = [
        'exclude_printed' => 'boolean',
        'printed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ResidentIdPrintBatchItem::class, 'print_batch_id');
    }
}
