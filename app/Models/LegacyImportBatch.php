<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegacyImportBatch extends Model
{
    protected $fillable = [
        'source_system',
        'manifest_checksum',
        'status',
        'file_manifest',
        'stats',
        'error_summary',
        'imported_at',
        'promoted_at',
    ];

    protected $casts = [
        'file_manifest' => 'array',
        'stats' => 'array',
        'error_summary' => 'array',
        'imported_at' => 'datetime',
        'promoted_at' => 'datetime',
    ];

    public function rows(): HasMany
    {
        return $this->hasMany(LegacyImportRow::class);
    }
}
