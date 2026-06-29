<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class OperationalDocument extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_ARCHIVED = 'archived';

    protected $table = 'taskit_operational_documents';

    protected $fillable = [
        'company_id',
        'operational_object_id',
        'compliance_requirement_id',
        'uploaded_by_user_id',
        'title',
        'document_type',
        'filename',
        'original_filename',
        'mime_type',
        'file_path',
        'file_size',
        'expires_at',
        'status',
        'extracted_data',
        'notes',
    ];

    protected $casts = [
        'expires_at' => 'date',
        'extracted_data' => 'array',
        'file_size' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function operationalObject(): BelongsTo
    {
        return $this->belongsTo(OperationalObject::class);
    }

    public function complianceRequirement(): BelongsTo
    {
        return $this->belongsTo(ComplianceRequirement::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function extractionProposals(): HasMany
    {
        return $this->hasMany(DocumentExtractionProposal::class, 'operational_document_id');
    }

    public function scopeForCompany(Builder $query, int $companyId): void
    {
        $query->where('company_id', $companyId);
    }

    public function refreshExpiryStatus(): void
    {
        if (! $this->expires_at) {
            return;
        }

        $status = $this->expires_at->isPast()
            ? self::STATUS_EXPIRED
            : self::STATUS_ACTIVE;

        if ($this->status !== self::STATUS_ARCHIVED && $this->status !== $status) {
            $this->update(['status' => $status]);
        }
    }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function getIsPdfAttribute(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function canAccess(User $user): bool
    {
        return $user->company_id === $this->company_id;
    }
}
