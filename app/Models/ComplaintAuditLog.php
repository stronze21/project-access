<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintAuditLog extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'complaint_id',
        'actor_user_id',
        'event_type',
        'auditable_type',
        'auditable_id',
        'ip_address',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function eventTypeLabel(): string
    {
        return ucwords(str_replace('_', ' ', $this->event_type));
    }

    /**
     * @return array<int, string>
     */
    public function userActivityLines(): array
    {
        $metadata = is_array($this->metadata) ? $this->metadata : [];
        if (empty($metadata)) {
            return [];
        }

        return match ($this->event_type) {
            'comment_hidden' => $this->commentHiddenLines($metadata),
            'comment_reaction_added' => [
                'Reaction added: '.$this->titleCaseValue($metadata['reaction'] ?? null),
            ],
            'comment_reaction_removed' => [
                'Reaction removed: '.$this->titleCaseValue($metadata['reaction'] ?? null),
            ],
            'comment_reaction_updated' => [
                'Reaction changed from '.$this->titleCaseValue($metadata['from'] ?? null).' to '.$this->titleCaseValue($metadata['to'] ?? null).'.',
            ],
            'complaint_priority_set' => [
                'Priority set to '.$this->titleCaseValue($metadata['priority'] ?? null).'.',
            ],
            'complaint_status_updated' => $this->statusUpdatedLines($metadata),
            'complaint_moderated' => $this->moderatedLines($metadata),
            'complaint_mayor_override' => $this->mayorOverrideLines($metadata),
            'complaint_official_tags_updated' => $this->officialTagsLines($metadata),
            'complaint_submitted' => $this->submissionLines($metadata),
            'attachment_uploaded' => $this->attachmentLines($metadata),
            default => $this->genericMetadataLines($metadata),
        };
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<int, string>
     */
    private function commentHiddenLines(array $metadata): array
    {
        $reason = trim((string) ($metadata['reason'] ?? ''));

        return $reason !== '' ? ['Hidden reason: '.$reason] : ['Comment was hidden by moderator.'];
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<int, string>
     */
    private function statusUpdatedLines(array $metadata): array
    {
        $from = $this->titleCaseValue($metadata['from'] ?? null);
        $to = $this->titleCaseValue($metadata['to'] ?? null);
        $override = (bool) ($metadata['override'] ?? false);

        $lines = ['Status changed from '.$from.' to '.$to.'.'];
        if ($override) {
            $lines[] = 'Update was made through an override action.';
        }

        return $lines;
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<int, string>
     */
    private function moderatedLines(array $metadata): array
    {
        $status = $this->titleCaseValue($metadata['moderation_status'] ?? null);
        $reason = trim((string) ($metadata['moderation_reason'] ?? ''));

        $lines = ['Moderation set to '.$status.'.'];
        if ($reason !== '') {
            $lines[] = 'Reason: '.$reason;
        }

        return $lines;
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<int, string>
     */
    private function mayorOverrideLines(array $metadata): array
    {
        $action = $this->titleCaseValue($metadata['action'] ?? null);
        $note = trim((string) ($metadata['note'] ?? ''));

        $lines = ['Mayor override action: '.$action.'.'];
        if ($note !== '') {
            $lines[] = 'Note: '.$note;
        }

        return $lines;
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<int, string>
     */
    private function officialTagsLines(array $metadata): array
    {
        $ids = $metadata['official_ids'] ?? [];
        if (!is_array($ids) || empty($ids)) {
            return ['Official tags were cleared.'];
        }

        $list = implode(', ', array_map(fn ($id) => (string) $id, $ids));

        return [
            'Tagged official IDs: '.$list,
            'Total tagged officials: '.count($ids),
        ];
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<int, string>
     */
    private function submissionLines(array $metadata): array
    {
        $type = (string) ($metadata['submission_type'] ?? '');
        $label = match ($type) {
            'citizen' => 'Verified citizen submission',
            'citizen_quick' => 'Quick ticket submission',
            'anonymous' => 'Anonymous submission',
            default => $this->titleCaseValue($type),
        };

        return ['Submission type: '.$label.'.'];
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<int, string>
     */
    private function attachmentLines(array $metadata): array
    {
        $lines = [];

        if (array_key_exists('type', $metadata)) {
            $lines[] = 'Attachment type: '.$this->titleCaseValue($metadata['type']).'.';
        }

        if (array_key_exists('scan_status', $metadata)) {
            $lines[] = 'Virus scan status: '.$this->titleCaseValue($metadata['scan_status']).'.';
        }

        return !empty($lines) ? $lines : ['Attachment activity logged.'];
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<int, string>
     */
    private function genericMetadataLines(array $metadata): array
    {
        $lines = [];

        foreach ($metadata as $key => $value) {
            $label = ucwords(str_replace('_', ' ', (string) $key));
            $lines[] = $label.': '.$this->stringifyValue($value);
        }

        return $lines;
    }

    private function titleCaseValue(mixed $value): string
    {
        $text = trim((string) $value);
        if ($text === '') {
            return 'N/A';
        }

        return ucwords(str_replace('_', ' ', $text));
    }

    private function stringifyValue(mixed $value): string
    {
        if (is_null($value)) {
            return 'N/A';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            $isFlat = array_filter($value, fn ($item) => is_array($item) || is_object($item)) === [];
            if ($isFlat) {
                return implode(', ', array_map(fn ($item) => (string) $item, $value));
            }

            return (string) json_encode($value, JSON_UNESCAPED_SLASHES);
        }

        return (string) $value;
    }
}
