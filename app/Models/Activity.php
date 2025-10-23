<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    use HasFactory;

    protected $table = 'taskit_activities';

    protected $fillable = [
        'type',
        'actor_id',
        'actor_name',
        'actor_email',
        'target_id',
        'target_name',
        'description',
        'metadata',
        'project_id',
        'company_id',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // Scopes
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('actor_id', $userId);
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Static methods for creating activities
    public static function createTodoActivity($todo, $actor, $type, $changes = null)
    {
        return self::create([
            'type' => $type,
            'actor_id' => $actor->id,
            'actor_name' => $actor->name,
            'actor_email' => $actor->email,
            'target_id' => $todo->id,
            'target_name' => $todo->title,
            'description' => self::getTodoDescription($type, $actor->name, $todo->title, $changes),
            'metadata' => $changes,
            'project_id' => $todo->project_id,
            'company_id' => $actor->company_id,
        ]);
    }

    public static function createProjectActivity($project, $actor, $type, $changes = null)
    {
        return self::create([
            'type' => $type,
            'actor_id' => $actor->id,
            'actor_name' => $actor->name,
            'actor_email' => $actor->email,
            'target_id' => $project->id,
            'target_name' => $project->name,
            'description' => self::getProjectDescription($type, $actor->name, $project->name, $changes),
            'metadata' => $changes,
            'project_id' => $project->id,
            'company_id' => $actor->company_id,
        ]);
    }

    public static function createCommentActivity($todo, $actor, $comment)
    {
        return self::create([
            'type' => 'todo_commented',
            'actor_id' => $actor->id,
            'actor_name' => $actor->name,
            'actor_email' => $actor->email,
            'target_id' => $todo->id,
            'target_name' => $todo->title,
            'description' => "{$actor->name} commented on \"{$todo->title}\"",
            'metadata' => ['comment' => $comment],
            'project_id' => $todo->project_id,
            'company_id' => $actor->company_id,
        ]);
    }

    public static function createMentionActivity($todo, $actor, $mentionedUser)
    {
        return self::create([
            'type' => 'mention',
            'actor_id' => $actor->id,
            'actor_name' => $actor->name,
            'actor_email' => $actor->email,
            'target_id' => $todo->id,
            'target_name' => $todo->title,
            'description' => "{$actor->name} mentioned {$mentionedUser->name} in \"{$todo->title}\"",
            'metadata' => ['mentioned_user' => $mentionedUser],
            'project_id' => $todo->project_id,
            'company_id' => $actor->company_id,
        ]);
    }

    private static function getTodoDescription($type, $actorName, $todoTitle, $changes = null)
    {
        switch ($type) {
            case 'todo_created':
                return "{$actorName} created todo \"{$todoTitle}\"";
            case 'todo_updated':
                return "{$actorName} updated todo \"{$todoTitle}\"";
            case 'todo_deleted':
                return "{$actorName} deleted todo \"{$todoTitle}\"";
            case 'todo_status_changed':
                $oldStatus = $changes['old_status'] ?? 'unknown';
                $newStatus = $changes['new_status'] ?? 'unknown';
                return "{$actorName} moved \"{$todoTitle}\" from {$oldStatus} to {$newStatus}";
            case 'todo_assigned':
                $assignee = $changes['assignee'] ?? 'unknown';
                return "{$actorName} assigned \"{$todoTitle}\" to {$assignee}";
            default:
                return "{$actorName} performed action on \"{$todoTitle}\"";
        }
    }

    private static function getProjectDescription($type, $actorName, $projectName, $changes = null)
    {
        switch ($type) {
            case 'project_created':
                return "{$actorName} created project \"{$projectName}\"";
            case 'project_updated':
                return "{$actorName} updated project \"{$projectName}\"";
            case 'project_deleted':
                return "{$actorName} deleted project \"{$projectName}\"";
            default:
                return "{$actorName} performed action on project \"{$projectName}\"";
        }
    }
}