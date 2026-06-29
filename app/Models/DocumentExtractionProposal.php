<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentExtractionProposal extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_DISMISSED = 'dismissed';

    protected $table = 'taskit_document_extraction_proposals';

    protected $fillable = [
        'user_id',
        'company_id',
        'operational_document_id',
        'operational_object_id',
        'status',
        'extracted_data',
        'summary',
        'metadata',
        'reviewed_at',
    ];

    protected $casts = [
        'extracted_data' => 'array',
        'metadata' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function operationalDocument(): BelongsTo
    {
        return $this->belongsTo(OperationalDocument::class, 'operational_document_id');
    }

    public function operationalObject(): BelongsTo
    {
        return $this->belongsTo(OperationalObject::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
