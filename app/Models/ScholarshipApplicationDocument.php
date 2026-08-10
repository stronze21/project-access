<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScholarshipApplicationDocument extends Model
{
    use HasFactory;

    public const STATUS_UPLOADED = 'uploaded';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_REJECTED = 'rejected';

    public const SCAN_PENDING = 'pending';

    public const SCAN_CLEAN = 'clean';

    public const SCAN_INFECTED = 'infected';

    public const SCAN_FAILED = 'failed';

    protected $fillable = [
        'scholarship_application_id',
        'document_type_id',
        'storage_disk',
        'storage_path',
        'original_name',
        'mime_type',
        'size_bytes',
        'virus_scan_status',
        'virus_scan_message',
        'scanned_at',
        'status',
        'rejection_reason',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
        'size_bytes' => 'integer',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(ScholarshipApplication::class, 'scholarship_application_id');
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(ScholarshipDocumentType::class, 'document_type_id');
    }

    public function isScanClean(): bool
    {
        return $this->virus_scan_status === self::SCAN_CLEAN;
    }
}
