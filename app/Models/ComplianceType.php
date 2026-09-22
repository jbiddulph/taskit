<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ComplianceType extends Model
{
    protected $table = 'taskit_compliance_types';

    protected $fillable = [
        'slug',
        'name',
        'default_validity_months',
        'category',
        'description',
        'lead_time_days',
        'frequency',
        'is_active',
    ];

    protected $casts = [
        'default_validity_months' => 'integer',
        'lead_time_days' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeOfCategory(Builder $query, string $category): void
    {
        $query->where('category', $category);
    }
}
