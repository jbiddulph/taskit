<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workspace extends Model
{
    protected $table = 'taskit_workspaces';

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'type',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(OperationalObject::class);
    }

    public function scopeForCompany(Builder $query, int $companyId): void
    {
        $query->where('company_id', $companyId);
    }

    /**
     * Ensure a company has at least one default workspace.
     */
    public static function ensureDefaultForCompany(Company $company): self
    {
        $existing = static::query()
            ->where('company_id', $company->id)
            ->where('is_default', true)
            ->first();

        if ($existing) {
            return $existing;
        }

        return static::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'name' => 'General',
            ],
            [
                'description' => 'Default workspace',
                'type' => 'general',
                'is_default' => true,
            ]
        );
    }
}
