<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountDeletionRequest extends Model
{
    use HasFactory;

    public const STATUS_RECEIVED = 'received';
    public const STATUS_REVIEWING = 'reviewing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'resident_id',
        'reference_number',
        'resident_identifier',
        'resident_name',
        'email',
        'contact_number',
        'reason',
        'requested_action',
        'retention_acknowledged',
        'status',
        'source',
        'platform',
        'device_name',
        'ip_address',
        'user_agent',
        'submitted_at',
        'processed_at',
        'admin_notes',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'processed_at' => 'datetime',
        'retention_acknowledged' => 'boolean',
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
            $reference = 'ADR-' . now()->format('Ymd') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 6));
        } while (self::where('reference_number', $reference)->exists());

        return $reference;
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }
}
