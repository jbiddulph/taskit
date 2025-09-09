<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Todo extends Model
{
    use HasFactory;

    protected $table = 'taskit_todos';

    protected $fillable = [
        'user_id',
        'project_id',
        'parent_task_id',
        'title',
        'description',
        'priority',
        'type',
        'tags',
        'assignee',
        'due_date',
        'story_points',
        'status',
        'order',
    ];

    protected $casts = [
        'tags' => 'array',
        'due_date' => 'date',
        'story_points' => 'integer',
    ];

    protected $dates = [
        'due_date',
        'created_at',
        'updated_at',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TodoComment::class)->orderBy('created_at', 'desc');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TodoAttachment::class)->orderBy('created_at', 'desc');
    }

    // Parent-child relationships for subtasks
    public function parentTask(): BelongsTo
    {
        return $this->belongsTo(Todo::class, 'parent_task_id');
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(Todo::class, 'parent_task_id')->orderBy('created_at', 'asc');
    }

    // Check if this todo is a subtask
    public function isSubtask(): bool
    {
        return !is_null($this->parent_task_id);
    }

    // Check if this todo has subtasks
    public function hasSubtasks(): bool
    {
        return $this->subtasks()->count() > 0;
    }

    // Scopes
    public function scopeForUser(Builder $query, User $user): void
    {
        $query->where('user_id', $user->id);
    }

    public function scopeForCompany(Builder $query, int $companyId): void
    {
        $query->whereHas('user', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        });
    }

    public function scopeForProject(Builder $query, int $projectId): void
    {
        $query->where('project_id', $projectId);
    }

    public function scopeByStatus(Builder $query, string $status): void
    {
        $query->where('status', $status);
    }

    public function scopeByPriority(Builder $query, string $priority): void
    {
        $query->where('priority', $priority);
    }

    public function scopeByType(Builder $query, string $type): void
    {
        $query->where('type', $type);
    }

    public function scopeByAssignee(Builder $query, string $assignee): void
    {
        $query->where('assignee', $assignee);
    }

    public function scopeOverdue(Builder $query): void
    {
        $query->where('due_date', '<', now()->startOfDay())
              ->where('status', '!=', 'done');
    }

    public function scopeDueToday(Builder $query): void
    {
        $query->whereDate('due_date', now()->toDateString());
    }

    public function scopeSearch(Builder $query, string $search): void
    {
        $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhereJsonContains('tags', $search);
        });
    }

    // Accessors
    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date && $this->due_date->isPast() && $this->status !== 'done';
    }

    public function getDaysUntilDueAttribute(): ?int
    {
        if (!$this->due_date) {
            return null;
        }

        $days = now()->diffInDays($this->due_date, false);
        return $days;
    }

    // Methods
    public function addComment(string $content, User $user): TodoComment
    {
        return $this->comments()->create([
            'user_id' => $user->id,
            'content' => $content,
        ]);
    }

    public function addAttachment(string $filePath, string $originalFilename, string $mimeType, int $fileSize, User $user): TodoAttachment
    {
        return $this->attachments()->create([
            'user_id' => $user->id,
            'filename' => basename($filePath),
            'original_filename' => $originalFilename,
            'mime_type' => $mimeType,
            'file_path' => $filePath,
            'file_size' => $fileSize,
        ]);
    }

    /**
     * Check if user can access this todo
     */
    public function canAccess(User $user): bool
    {
        if (!$user->company_id) {
            return $this->user_id === $user->id;
        }
        
        // User can access if they created it OR if they're in the same company as the creator
        return $this->user_id === $user->id || $this->user->company_id === $user->company_id;
    }
}
