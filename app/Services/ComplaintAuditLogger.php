<?php

namespace App\Services;

use App\Models\Complaint;
use App\Models\ComplaintAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ComplaintAuditLogger
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function log(
        string $eventType,
        ?Complaint $complaint = null,
        ?Model $auditable = null,
        ?User $actor = null,
        ?Request $request = null,
        array $metadata = []
    ): ComplaintAuditLog {
        $actor ??= Auth::user();

        return ComplaintAuditLog::create([
            'complaint_id' => $complaint?->id,
            'actor_user_id' => $actor?->id,
            'event_type' => $eventType,
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id' => $auditable?->getKey(),
            'ip_address' => null,
            'metadata' => $metadata,
        ]);
    }
}
