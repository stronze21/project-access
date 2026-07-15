<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegacyImportRow extends Model
{
    protected $fillable = [
        'legacy_import_batch_id',
        'source_table',
        'source_row_number',
        'natural_key',
        'row_hash',
        'raw_payload',
        'validation_status',
        'validation_errors',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'validation_errors' => 'array',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(LegacyImportBatch::class, 'legacy_import_batch_id');
    }
}
