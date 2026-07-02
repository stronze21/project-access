<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintAttachment extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    public const TYPE_EVIDENCE = 'evidence';
    public const TYPE_RESOLUTION = 'resolution';

    public const SCAN_PENDING = 'pending';
    public const SCAN_CLEAN = 'clean';
    public const SCAN_INFECTED = 'infected';
    public const SCAN_FAILED = 'failed';

    protected $fillable = [
        'complaint_id',
        'uploaded_by_user_id',
        'type',
        'storage_disk',
        'storage_path',
        'original_name',
        'mime_type',
        'size_bytes',
        'virus_scan_status',
        'virus_scan_message',
        'scanned_at',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
