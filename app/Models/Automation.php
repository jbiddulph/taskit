<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Automation extends Model
{
    protected $table = 'taskit_automations';

    public const TRIGGER_DATE_REACHED = 'date_reached';
    public const TRIGGER_TASK_CREATED = 'task_created';
    public const TRIGGER_TASK_COMPLETED = 'task_completed';
    public const TRIGGER_TASK_OVERDUE = 'task_overdue';
    public const TRIGGER_ASSET_CREATED = 'asset_created';
    public const TRIGGER_DOCUMENT_UPLOADED = 'document_uploaded';
    public const TRIGGER_COMPLIANCE_EXPIRING = 'compliance_expiring';

    public const ACTION_CREATE_TASK = 'create_task';
    public const ACTION_SEND_NOTIFICATION = 'send_notification';
    public const ACTION_SEND_EMAIL = 'send_email';
    public const ACTION_ASSIGN_USER = 'assign_user';
    public const ACTION_CHANGE_STATUS = 'change_status';
    public const ACTION_CREATE_FUTURE_TASK = 'create_future_task';

    protected $fillable = [
        'company_id',
        'created_by',
        'name',
        'trigger_type',
        'trigger_config',
        'action_type',
        'action_config',
        'enabled',
        'last_run_at',
    ];

    protected $casts = [
        'trigger_config' => 'array',
        'action_config' => 'array',
        'enabled' => 'boolean',
        'last_run_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForCompany(Builder $query, int $companyId): void
    {
        $query->where('company_id', $companyId);
    }

    public function scopeEnabled(Builder $query): void
    {
        $query->where('enabled', true);
    }

    public function scopeOfTrigger(Builder $query, string $triggerType): void
    {
        $query->where('trigger_type', $triggerType);
    }
}
