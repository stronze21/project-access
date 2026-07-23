<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ResidentIdentityChangeRequest extends Model
{
    use HasFactory;

    public const TYPE_PHOTO = 'photo';

    public const TYPE_SIGNATURE = 'signature';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_DENIED = 'denied';

    protected $fillable = [
        'reference_number', 'resident_id', 'type', 'requested_file_path',
        'requested_signature', 'request_reason', 'status', 'reviewed_by',
        'review_reason', 'reviewed_at',
    ];

    protected $casts = ['reviewed_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(function (self $request): void {
            $request->reference_number ??= 'IDR-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        });
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
