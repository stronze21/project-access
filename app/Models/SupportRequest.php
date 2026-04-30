<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportRequest extends Model
{
    use HasFactory;

    public const STATUS_RECEIVED = 'received';
    public const STATUS_REVIEWING = 'reviewing';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'resident_id',
        'reference_number',
        'resident_identifier',
        'resident_name',
        'email',
        'contact_number',
        'category',
        'subject',
        'message',
        'status',
        'source',
        'platform',
        'device_name',
        'ip_address',
        'user_agent',
        'submitted_at',
        'resolved_at',
        'admin_notes',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $request): void {
            if (!$request->reference_number) {
                $request->reference_number = self::generateReferenceNumber();
            }

            $request->submitted_at ??= now();
            $request->status ??= self::STATUS_RECEIVED;
        });
    }

    public static function generateReferenceNumber(): string
    {
        do {
            $reference = 'SUP-' . now()->format('Ymd') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 6));
        } while (self::where('reference_number', $reference)->exists());

        return $reference;
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }
}
