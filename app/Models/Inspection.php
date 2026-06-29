<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Inspection extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_COMPLETED = 'completed';

    protected $table = 'taskit_inspections';

    protected $fillable = [
        'company_id',
        'operational_object_id',
        'compliance_requirement_id',
        'inspector_user_id',
        'template_key',
        'label',
        'status',
        'responses',
        'summary',
        'pdf_path',
        'completed_at',
    ];

    protected $casts = [
        'responses' => 'array',
        'completed_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function operationalObject(): BelongsTo
    {
        return $this->belongsTo(OperationalObject::class);
    }

    public function complianceRequirement(): BelongsTo
    {
        return $this->belongsTo(ComplianceRequirement::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_user_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(InspectionPhoto::class)->orderBy('sort_order');
    }

    public function followUpTasks(): HasMany
    {
        return $this->hasMany(Todo::class)->where('source', 'inspection_follow_up');
    }

    public function scopeForCompany(Builder $query, int $companyId): void
    {
        $query->where('company_id', $companyId);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function canAccess(User $user): bool
    {
        return $user->company_id === $this->company_id;
    }
}
