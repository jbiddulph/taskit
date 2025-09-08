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
        'viewing_order',
        'client_id',
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
     * Get the client for this project
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
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
     * Get active projects for a company
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->whereHas('owner', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })->where('is_active', true);
    }

    /**
     * Get visible projects for a company (respecting subscription limits)
     */
    public function scopeVisibleForCompany($query, int $companyId)
    {
        $company = Company::find($companyId);
        if (!$company) {
            return $query->whereRaw('1 = 0'); // Return empty result
        }

        $baseQuery = $query->whereHas('owner', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })->where('is_active', true)->orderBy('viewing_order');

        // Apply subscription-based limits
        $limit = $company->getProjectLimit();
        if ($limit !== PHP_INT_MAX) {
            $baseQuery->limit($limit);
        }

        return $baseQuery;
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
            'qa-testing' => $todos->where('status', 'qa-testing')->count(),
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
        $user = User::find($userId);
        if (!$user || !$user->company_id) {
            return $this->owner_id === $userId;
        }
        
        // Basic access check: User can access if they own it OR if they're in the same company as the owner
        $hasBasicAccess = $this->owner_id === $userId || $this->owner->company_id === $user->company_id;
        
        if (!$hasBasicAccess) {
            return false;
        }
        
        // Additional check: Project must be within subscription limits (visible)
        $visibleProjectIds = static::visibleForCompany($user->company_id)->pluck('id')->toArray();
        return in_array($this->id, $visibleProjectIds);
    }

    /**
     * Initialize viewing order for projects
     */
    public static function initializeViewingOrder(): void
    {
        // Get all companies and individual users
        $companies = User::whereNotNull('company_id')->distinct()->pluck('company_id');
        $individualUsers = User::whereNull('company_id')->pluck('id');

        // Initialize viewing order for company projects
        foreach ($companies as $companyId) {
            $projects = static::forCompany($companyId)->orderBy('created_at')->get();
            foreach ($projects as $index => $project) {
                $project->update(['viewing_order' => $index + 1]);
            }
        }

        // Initialize viewing order for individual user projects
        foreach ($individualUsers as $userId) {
            $projects = static::forUser($userId)->orderBy('created_at')->get();
            foreach ($projects as $index => $project) {
                $project->update(['viewing_order' => $index + 1]);
            }
        }
    }

    /**
     * Get the next viewing order for a user/company
     */
    public static function getNextViewingOrder(int $userId): int
    {
        $user = User::find($userId);
        
        if ($user->company_id) {
            $maxOrder = static::forCompany($user->company_id)->max('viewing_order') ?? 0;
        } else {
            $maxOrder = static::forUser($userId)->max('viewing_order') ?? 0;
        }
        
        return $maxOrder + 1;
    }
}
