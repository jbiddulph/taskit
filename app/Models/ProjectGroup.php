<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectGroup extends Model
{
    use HasFactory;

    protected $table = 'taskit_project_groups';

    protected $fillable = [
        'project_id',
        'name',
        'description',
        'color',
        'viewing_order',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'viewing_order' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class);
    }

    public function canAccess(User $user): bool
    {
        return $this->project && $this->project->canAccess($user->id);
    }

    public static function createDefaultForProject(Project $project, string $name = 'Main board'): self
    {
        return static::create([
            'project_id' => $project->id,
            'name' => $name,
            'color' => null,
            'viewing_order' => 1,
            'is_default' => true,
        ]);
    }

    public static function getNextViewingOrder(int $projectId): int
    {
        return (static::where('project_id', $projectId)->max('viewing_order') ?? 0) + 1;
    }

    public static function resolveDefaultIdForProject(int $projectId): ?int
    {
        return static::query()
            ->where('project_id', $projectId)
            ->orderByDesc('is_default')
            ->orderBy('viewing_order')
            ->value('id');
    }

    /**
     * Resolve a board group for a project. Ignores group IDs that belong to
     * a different project so ungrouped todos are never stamped onto the wrong board.
     */
    public static function resolveIdForProject(int $projectId, ?int $requestedGroupId): ?int
    {
        if ($requestedGroupId) {
            $matchingId = static::query()
                ->where('id', $requestedGroupId)
                ->where('project_id', $projectId)
                ->value('id');

            if ($matchingId) {
                return (int) $matchingId;
            }
        }

        return static::resolveDefaultIdForProject($projectId);
    }

    public static function resolveOrCreateForProject(Project $project): int
    {
        return static::resolveDefaultIdForProject($project->id)
            ?? static::createDefaultForProject($project)->id;
    }
}
