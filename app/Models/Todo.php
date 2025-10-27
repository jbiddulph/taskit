<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

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
        'company_id',
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
    public function addComment(string $content, User $user, $webSocketService = null): TodoComment
    {
        $comment = $this->comments()->create([
            'user_id' => $user->id,
            'content' => $content,
        ]);

        // Parse mentions and create notifications
        $this->createMentionNotifications($content, $user, $comment, $webSocketService);

        return $comment;
    }

    /**
     * Parse mentions from comment content and create notifications
     */
    protected function createMentionNotifications(string $content, User $commenter, TodoComment $comment, $webSocketService = null): void
    {
        // Try to parse structured mentions first: @[Name](ID)
        preg_match_all('/@\[([^\]]+)\]\((\d+)\)/', $content, $structuredMatches);
        
        // Also try to parse plain mentions: @Name
        preg_match_all('/@(\w+(?:\s+\w+)*)/', $content, $plainMatches);
        
        \Log::info('Mention parsing', [
            'content' => $content,
            'structured_matches' => $structuredMatches,
            'plain_matches' => $plainMatches,
            'company_id' => $this->user->company_id
        ]);
        
        // Process structured mentions first
        if (!empty($structuredMatches[2])) {
            foreach ($structuredMatches[2] as $index => $mentionedUserId) {
                $mentionedName = $structuredMatches[1][$index] ?? '';
                $this->createNotificationForUser($mentionedUserId, $mentionedName, $commenter, $comment, $webSocketService);
            }
        }
        
        // Process plain mentions
        if (!empty($plainMatches[1])) {
            // Get all company users for matching
            $query = User::query();
            if ($this->user->company_id) {
                $query->where('company_id', $this->user->company_id);
            }
            $companyUsers = $query->where('id', '!=', $commenter->id)->get();
            
            foreach ($plainMatches[1] as $mentionedName) {
                // Find the mentioned user by name (case insensitive, partial match)
                $mentionedUser = $companyUsers->first(function ($u) use ($mentionedName) {
                    $cleanName = str_replace(' ', '', strtolower($u->name));
                    $cleanMention = str_replace(' ', '', strtolower($mentionedName));
                    return $cleanName === $cleanMention || strpos($cleanName, $cleanMention) !== false;
                });
                
                if ($mentionedUser) {
                    $this->createNotificationForUser($mentionedUser->id, $mentionedUser->name, $commenter, $comment, $webSocketService);
                } else {
                    \Log::info('User not found for mention', [
                        'mentioned_name' => $mentionedName,
                        'available_users' => $companyUsers->pluck('name')->toArray()
                    ]);
                }
            }
        }
    }
    
    /**
     * Create a notification for a mentioned user
     */
    protected function createNotificationForUser($userId, $userName, User $commenter, TodoComment $comment, $webSocketService = null): void
    {
        $mentionedUser = User::find($userId);
        
        if ($mentionedUser) {
            \Log::info('Creating mention notification', [
                'mentioned_user_id' => $mentionedUser->id,
                'mentioned_user_name' => $mentionedUser->name,
                'comment_id' => $comment->id,
                'todo_id' => $this->id
            ]);
            
            $notification = Notification::create([
                'user_id' => $mentionedUser->id, // The mentioned user receives the notification
                'mentioned_user_id' => $mentionedUser->id, // Store who was mentioned
                'type' => 'mention',
                'title' => 'You were mentioned',
                'message' => "{$commenter->name} mentioned you in a comment",
                'data' => [
                    'comment_id' => $comment->id,
                    'todo_id' => $this->id,
                    'todo_title' => $this->title,
                    'commenter_id' => $commenter->id,
                    'commenter_name' => $commenter->name,
                    'mentioned_user_id' => $mentionedUser->id,
                ],
            ]);
            
            // Send Pusher notification if webSocketService is available
            if ($webSocketService && method_exists($webSocketService, 'mentionAdded')) {
                $webSocketService->mentionAdded($notification, $mentionedUser->id); // Send to mentioned user
            }
        }
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
