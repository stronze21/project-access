<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResidentIdPrintBatchItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'print_batch_id',
        'resident_id',
        'resident_pin',
        'resident_name',
        'printed_at',
    ];

    protected $casts = ['printed_at' => 'datetime'];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ResidentIdPrintBatch::class, 'print_batch_id');
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }
}
