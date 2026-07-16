<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivationConsentAudit extends Model
{
    protected $fillable = [
        'attempt_id', 'resident_identifier', 'resident_id', 'channel',
        'terms_version', 'privacy_version', 'bhwis_consent_version',
        'terms_accepted_at', 'privacy_acknowledged_at', 'bhwis_consented_at',
        'ip_address', 'user_agent', 'device_name', 'outcome',
    ];

    protected $casts = [
        'terms_accepted_at' => 'datetime',
        'privacy_acknowledged_at' => 'datetime',
        'bhwis_consented_at' => 'datetime',
    ];

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }
}
