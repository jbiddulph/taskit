<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class OperationalObject extends Model
{
    use HasFactory;

    protected $table = 'taskit_operational_objects';

    protected $fillable = [
        'company_id',
        'parent_id',
        'type',
        'name',
        'reference',
        'address_line_1',
        'address_line_2',
        'city',
        'postal_code',
        'country',
        'latitude',
        'longitude',
        'notes',
        'metadata',
        'is_active',
        'created_by_user_id',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('name');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function complianceRequirements(): HasMany
    {
        return $this->hasMany(ComplianceRequirement::class);
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class)->orderByDesc('created_at');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(OperationalDocument::class)->orderByDesc('created_at');
    }

    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class);
    }

    public function scopeForCompany(Builder $query, int $companyId): void
    {
        $query->where('company_id', $companyId)->where('is_active', true);
    }

    public function scopeOfType(Builder $query, string $type): void
    {
        $query->where('type', $type);
    }

    public function getFullAddressAttribute(): string
    {
        return collect([
            $this->address_line_1,
            $this->address_line_2,
            $this->city,
            $this->postal_code,
            $this->country,
        ])->filter()->implode(', ');
    }

    public function getDisplayLocationNameAttribute(): string
    {
        return $this->name;
    }

    public function canAccess(User $user): bool
    {
        return $user->company_id === $this->company_id;
    }

    public function syncLocationToTodos(): void
    {
        if (! $this->latitude && ! $this->longitude && ! $this->full_address) {
            return;
        }

        $this->todos()
            ->whereNull('latitude')
            ->whereNull('longitude')
            ->update([
                'location_name' => $this->name,
                'location_address' => $this->full_address ?: null,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ]);
    }
}
