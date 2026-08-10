<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScholarshipDocumentType extends Model
{
    use HasFactory;

    public const APPLICANT_NEW = 'new';

    public const APPLICANT_ONGOING = 'ongoing';

    public const APPLICANT_BOTH = 'both';

    protected $fillable = [
        'code',
        'label',
        'applicant_type',
        'sort_order',
        'is_required',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function documents(): HasMany
    {
        return $this->hasMany(ScholarshipApplicationDocument::class, 'document_type_id');
    }

    public function scopeForApplicantType($query, string $applicantType)
    {
        return $query->where(function ($q) use ($applicantType) {
            $q->where('applicant_type', $applicantType)
                ->orWhere('applicant_type', self::APPLICANT_BOTH);
        })->orderBy('sort_order')->orderBy('label');
    }
}
