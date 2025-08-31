<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $table = 'taskit_projects';

    protected $fillable = [
        'name',
        'description',
        'key',
        'color',
        'is_active',
        'owner_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the owner of the project
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get all todos for this project
     */
    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class);
    }

    /**
     * Get todos by status for this project
     */
    public function todosByStatus(string $status): HasMany
    {
        return $this->todos()->where('status', $status);
    }

    /**
     * Get active projects for a user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('owner_id', $userId)->where('is_active', true);
    }

    /**
     * Get projects by key
     */
    public function scopeByKey($query, string $key)
    {
        return $query->where('key', $key);
    }

    /**
     * Get project statistics
     */
    public function getStats(): array
    {
        $todos = $this->todos;
        
        return [
            'total' => $todos->count(),
            'todo' => $todos->where('status', 'todo')->count(),
            'in-progress' => $todos->where('status', 'in-progress')->count(),
            'done' => $todos->where('status', 'done')->count(),
            'overdue' => $todos->where('due_date', '<', now())->where('status', '!=', 'done')->count(),
            'due_today' => $todos->where('due_date', now()->toDateString())->count(),
        ];
    }

    /**
     * Generate a unique project key
     */
    public static function generateUniqueKey(string $name): string
    {
        $baseKey = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $name), 0, 4));
        $key = $baseKey;
        $counter = 1;

        while (static::where('key', $key)->exists()) {
            $key = $baseKey . $counter;
            $counter++;
        }

        return $key;
    }

    /**
     * Get the project's display name with key
     */
    public function getDisplayName(): string
    {
        return "{$this->key} - {$this->name}";
    }

    /**
     * Check if user can access this project
     */
    public function canAccess(int $userId): bool
    {
        return $this->owner_id === $userId;
    }
}
