<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScholarshipApplicationEvent extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    public const ACTOR_RESIDENT = 'resident';

    public const ACTOR_STAFF = 'staff';

    public const ACTOR_SYSTEM = 'system';

    protected $fillable = [
        'scholarship_application_id',
        'from_status',
        'to_status',
        'actor_type',
        'actor_id',
        'note',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(ScholarshipApplication::class, 'scholarship_application_id');
    }
}
