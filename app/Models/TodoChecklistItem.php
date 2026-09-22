<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TodoChecklistItem extends Model
{
    protected $table = 'taskit_todo_checklist_items';

    protected $fillable = [
        'todo_id',
        'title',
        'completed',
        'position',
        'completed_at',
        'completed_by',
    ];

    protected $casts = [
        'completed' => 'boolean',
        'completed_at' => 'datetime',
        'position' => 'integer',
    ];

    public function todo(): BelongsTo
    {
        return $this->belongsTo(Todo::class);
    }

    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function markCompleted(?User $user = null): void
    {
        $this->forceFill([
            'completed' => true,
            'completed_at' => now(),
            'completed_by' => $user?->id,
        ])->save();
    }

    public function markIncomplete(): void
    {
        $this->forceFill([
            'completed' => false,
            'completed_at' => null,
            'completed_by' => null,
        ])->save();
    }
}
