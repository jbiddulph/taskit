<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;

    protected $table = 'taskit_clients';

    protected $fillable = [
        'name',
        'key_contact_name',
        'key_contact_email',
        'key_contact_phone',
        'address_line_1',
        'address_line_2',
        'city',
        'state_province',
        'postal_code',
        'country',
        'website',
        'notes',
        'is_active',
        'company_id',
        'created_by_user_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the company that owns this client
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who created this client
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get all projects for this client
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Get active projects for this client
     */
    public function activeProjects(): HasMany
    {
        return $this->projects()->where('is_active', true);
    }

    /**
     * Get clients for a specific company
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId)->where('is_active', true);
    }

    /**
     * Get client's full address as a string
     */
    public function getFullAddressAttribute(): string
    {
        $addressParts = array_filter([
            $this->address_line_1,
            $this->address_line_2,
            $this->city,
            $this->state_province,
            $this->postal_code,
            $this->country,
        ]);

        return implode(', ', $addressParts);
    }

    /**
     * Get client statistics
     */
    public function getStats(): array
    {
        $projects = $this->activeProjects;
        $totalTodos = 0;
        $completedTodos = 0;

        foreach ($projects as $project) {
            $projectTodos = $project->todos;
            $totalTodos += $projectTodos->count();
            $completedTodos += $projectTodos->where('status', 'done')->count();
        }

        return [
            'total_projects' => $projects->count(),
            'total_todos' => $totalTodos,
            'completed_todos' => $completedTodos,
            'completion_rate' => $totalTodos > 0 ? round(($completedTodos / $totalTodos) * 100, 1) : 0,
        ];
    }

    /**
     * Check if user can access this client
     */
    public function canAccess(int $userId): bool
    {
        $user = User::find($userId);
        if (!$user || !$user->company_id) {
            return false;
        }

        return $this->company_id === $user->company_id;
    }
}
