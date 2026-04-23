<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'module',
        'action',
        'description',
        'loggable_id',
        'loggable_type',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'old_values' => 'json',
        'new_values' => 'json',
    ];

    /**
     * Get the user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the related model.
     */
    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Create a new activity log entry.
     *
     * @param array $data
     * @return self
     */
    public static function log(array $data): self
    {
        // Get IP address and user agent if not provided
        if (!isset($data['ip_address'])) {
            $data['ip_address'] = request()->ip();
        }

        if (!isset($data['user_agent'])) {
            $data['user_agent'] = request()->userAgent();
        }

        // Get authenticated user if not provided
        if (!isset($data['user_id']) && auth()->check()) {
            $data['user_id'] = auth()->id();
        }

        return self::create($data);
    }
}