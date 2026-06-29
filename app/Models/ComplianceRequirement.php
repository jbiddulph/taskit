<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class ComplianceRequirement extends Model
{
    use HasFactory;

    public const STATUS_COMPLIANT = 'compliant';

    public const STATUS_DUE_SOON = 'due_soon';

    public const STATUS_OVERDUE = 'overdue';

    public const STATUS_MISSING = 'missing';

    protected $table = 'taskit_compliance_requirements';

    protected $fillable = [
        'company_id',
        'operational_object_id',
        'project_id',
        'requirement_type',
        'label',
        'frequency',
        'lead_time_days',
        'next_due_date',
        'last_completed_at',
        'assignee',
        'status',
        'notes',
        'auto_create_tasks',
    ];

    protected $casts = [
        'next_due_date' => 'date',
        'last_completed_at' => 'date',
        'lead_time_days' => 'integer',
        'auto_create_tasks' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function operationalObject(): BelongsTo
    {
        return $this->belongsTo(OperationalObject::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class);
    }

    public function scopeForCompany(Builder $query, int $companyId): void
    {
        $query->where('company_id', $companyId);
    }

    public function scopeAutoCreateTasks(Builder $query): void
    {
        $query->where('auto_create_tasks', true);
    }

    public function refreshStatus(): void
    {
        $status = self::STATUS_MISSING;

        if ($this->next_due_date) {
            $today = now()->startOfDay();
            $dueDate = $this->next_due_date->copy()->startOfDay();

            if ($dueDate->lt($today)) {
                $status = self::STATUS_OVERDUE;
            } elseif ($dueDate->lte($today->copy()->addDays($this->lead_time_days))) {
                $status = self::STATUS_DUE_SOON;
            } else {
                $status = self::STATUS_COMPLIANT;
            }
        }

        if ($this->status !== $status) {
            $this->update(['status' => $status]);
        }
    }

    public function hasOpenTask(): bool
    {
        return $this->todos()->where('status', '!=', 'done')->exists();
    }

    public function taskDueDate(): ?Carbon
    {
        if (! $this->next_due_date) {
            return null;
        }

        return $this->next_due_date->copy()->subDays($this->lead_time_days);
    }

    public function shouldCreateTaskToday(): bool
    {
        if (! $this->auto_create_tasks || ! $this->next_due_date || $this->hasOpenTask()) {
            return false;
        }

        $taskDue = $this->taskDueDate();

        return $taskDue && $taskDue->lte(now()->startOfDay());
    }
}
