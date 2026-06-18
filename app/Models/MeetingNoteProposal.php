<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingNoteProposal extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_DISMISSED = 'dismissed';

    protected $table = 'taskit_meeting_note_proposals';

    protected $fillable = [
        'user_id',
        'company_id',
        'status',
        'meeting_summary',
        'transcript',
        'action_items',
        'metadata',
        'project_id',
        'reviewed_at',
    ];

    protected $casts = [
        'action_items' => 'array',
        'metadata' => 'array',
        'reviewed_at' => 'datetime',
        'user_id' => 'integer',
        'company_id' => 'integer',
        'project_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
