<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DocumentAiUsage extends Model
{
    public const KIND_EXTRACTION = 'extraction';

    public const KIND_PORTFOLIO_ASK = 'portfolio_ask';

    protected $table = 'taskit_document_ai_usages';

    protected $fillable = [
        'company_id',
        'user_id',
        'kind',
        'reference_type',
        'reference_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
