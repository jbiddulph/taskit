<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Automation;
use App\Models\ComplianceRequirement;
use App\Models\Notification;
use App\Models\Project;
use App\Models\ProjectGroup;
use App\Models\Todo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AutomationRunnerService
{
    /** Prevent nested dispatch while an action is creating/updating todos. */
    private static bool $dispatching = false;

    /**
     * Run scheduled triggers: date_reached, task_overdue, compliance_expiring.
     */
    public function runScheduledAutomations(?Carbon $date = null): int
    {
        $date ??= Carbon::today();

        return $this->runDateReachedAutomations($date)
            + $this->runTaskOverdueAutomations($date)
            + $this->runComplianceExpiringAutomations($date);
    }

    /**
     * Run date_reached automations that create reminder/tasks for a given date.
     */
    public function runDateReachedAutomations(?Carbon $date = null): int
    {
        $date ??= Carbon::today();
        $ran = 0;

        $automations = Automation::query()
            ->enabled()
            ->ofTrigger(Automation::TRIGGER_DATE_REACHED)
            ->get();

        foreach ($automations as $automation) {
            try {
                if ($this->shouldRunDateReached($automation, $date)) {
                    $this->executeAction($automation, [
                        'date' => $date->toDateString(),
                    ]);
                    $automation->forceFill(['last_run_at' => now()])->save();
                    $ran++;
                }
            } catch (\Throwable $e) {
                Log::error('Automation failed', [
                    'automation_id' => $automation->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $ran;
    }

    /**
     * Fire task_overdue for incomplete todos past due (once per automation per day).
     */
    public function runTaskOverdueAutomations(?Carbon $date = null): int
    {
        $date ??= Carbon::today();
        $ran = 0;

        $automations = Automation::query()
            ->enabled()
            ->ofTrigger(Automation::TRIGGER_TASK_OVERDUE)
            ->get();

        foreach ($automations as $automation) {
            if ($automation->last_run_at && $automation->last_run_at->isSameDay($date)) {
                continue;
            }

            $overdue = Todo::query()
                ->forCompany($automation->company_id)
                ->whereDate('due_date', '<', $date)
                ->whereNotIn('status', ['done', 'completed', 'cancelled'])
                ->whereNull('parent_task_id')
                ->limit(50)
                ->get();

            foreach ($overdue as $todo) {
                $context = [
                    'todo_id' => $todo->id,
                    'task_title' => $todo->title,
                    'asset_id' => $todo->operational_object_id,
                    'project_id' => $todo->project_id,
                    'category' => $todo->category,
                    'due_date' => optional($todo->due_date)?->toDateString(),
                    'date' => $date->toDateString(),
                ];

                if (! $this->matchesTriggerFilters($automation, $context)) {
                    continue;
                }

                try {
                    $this->executeAction($automation, $context);
                    $ran++;
                } catch (\Throwable $e) {
                    Log::error('Overdue automation failed', [
                        'automation_id' => $automation->id,
                        'todo_id' => $todo->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if ($overdue->isNotEmpty()) {
                $automation->forceFill(['last_run_at' => now()])->save();
            }
        }

        return $ran;
    }

    /**
     * Fire compliance_expiring for requirements due within configured days.
     */
    public function runComplianceExpiringAutomations(?Carbon $date = null): int
    {
        $date ??= Carbon::today();
        $ran = 0;

        $automations = Automation::query()
            ->enabled()
            ->ofTrigger(Automation::TRIGGER_COMPLIANCE_EXPIRING)
            ->get();

        foreach ($automations as $automation) {
            if ($automation->last_run_at && $automation->last_run_at->isSameDay($date)) {
                continue;
            }

            $daysBefore = (int) ($automation->trigger_config['days_before'] ?? 30);
            $windowEnd = $date->copy()->addDays($daysBefore);

            $requirements = ComplianceRequirement::query()
                ->forCompany($automation->company_id)
                ->whereNotNull('next_due_date')
                ->whereDate('next_due_date', '>=', $date)
                ->whereDate('next_due_date', '<=', $windowEnd)
                ->limit(50)
                ->get();

            foreach ($requirements as $requirement) {
                $context = [
                    'compliance_requirement_id' => $requirement->id,
                    'task_title' => $requirement->label ?? $requirement->requirement_type,
                    'asset_id' => $requirement->operational_object_id,
                    'project_id' => $requirement->project_id,
                    'category' => $requirement->requirement_type,
                    'due_date' => optional($requirement->next_due_date)?->toDateString(),
                    'date' => $date->toDateString(),
                ];

                try {
                    $this->executeAction($automation, $context);
                    $ran++;
                } catch (\Throwable $e) {
                    Log::error('Compliance expiring automation failed', [
                        'automation_id' => $automation->id,
                        'requirement_id' => $requirement->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if ($requirements->isNotEmpty()) {
                $automation->forceFill(['last_run_at' => now()])->save();
            }
        }

        return $ran;
    }

    /**
     * Dispatch event-based automations (task_completed, task_created, etc.).
     */
    public function dispatch(string $triggerType, int $companyId, array $context = []): int
    {
        if (self::$dispatching) {
            return 0;
        }

        $ran = 0;

        $automations = Automation::query()
            ->forCompany($companyId)
            ->enabled()
            ->ofTrigger($triggerType)
            ->get();

        foreach ($automations as $automation) {
            if (! $this->matchesTriggerFilters($automation, $context)) {
                continue;
            }

            try {
                self::$dispatching = true;
                $this->executeAction($automation, $context);
                $automation->forceFill(['last_run_at' => now()])->save();
                $ran++;
            } catch (\Throwable $e) {
                Log::error('Automation failed', [
                    'automation_id' => $automation->id,
                    'error' => $e->getMessage(),
                ]);
            } finally {
                self::$dispatching = false;
            }
        }

        return $ran;
    }

    /**
     * Optional trigger_config filters: category, project_id, asset_id.
     */
    private function matchesTriggerFilters(Automation $automation, array $context): bool
    {
        $config = $automation->trigger_config ?? [];

        if (! empty($config['category']) && ($context['category'] ?? null) !== $config['category']) {
            return false;
        }

        if (! empty($config['project_id'])
            && (int) ($context['project_id'] ?? 0) !== (int) $config['project_id']) {
            return false;
        }

        if (! empty($config['asset_id'])
            && (int) ($context['asset_id'] ?? 0) !== (int) $config['asset_id']) {
            return false;
        }

        return true;
    }

    private function shouldRunDateReached(Automation $automation, Carbon $date): bool
    {
        $config = $automation->trigger_config ?? [];
        $targetDate = $config['date'] ?? null;

        if (! $targetDate) {
            // days_before relative to a stored future_date in config
            $futureDate = $config['future_date'] ?? null;
            $daysBefore = (int) ($config['days_before'] ?? 0);

            if (! $futureDate) {
                return false;
            }

            $target = Carbon::parse($futureDate)->subDays($daysBefore);

            return $target->isSameDay($date);
        }

        return Carbon::parse($targetDate)->isSameDay($date);
    }

    private function executeAction(Automation $automation, array $context): void
    {
        match ($automation->action_type) {
            Automation::ACTION_CREATE_TASK,
            Automation::ACTION_CREATE_FUTURE_TASK => $this->createTask($automation, $context),
            Automation::ACTION_SEND_NOTIFICATION => $this->sendNotification($automation, $context),
            Automation::ACTION_CHANGE_STATUS => $this->changeStatus($automation, $context),
            Automation::ACTION_ASSIGN_USER => $this->assignUser($automation, $context),
            default => Log::info('Automation action skipped (not implemented)', [
                'action' => $automation->action_type,
                'automation_id' => $automation->id,
            ]),
        };
    }

    private function createTask(Automation $automation, array $context): void
    {
        $config = $automation->action_config ?? [];
        $company = $automation->company;

        $actor = $automation->creator
            ?? $company->users()->orderBy('created_at')->first();

        if (! $actor) {
            return;
        }

        $project = null;
        if (! empty($config['project_id'])) {
            $project = Project::query()
                ->forCompany($company->id)
                ->where('id', (int) $config['project_id'])
                ->first();
        }

        $project ??= Project::query()
            ->forCompany($company->id)
            ->where('is_active', true)
            ->orderBy('viewing_order')
            ->first();

        if (! $project) {
            return;
        }

        $dueDate = null;
        if (! empty($config['due_in_days'])) {
            $dueDate = Carbon::today()->addDays((int) $config['due_in_days'])->toDateString();
        } elseif (! empty($config['due_date'])) {
            $dueDate = $config['due_date'];
        } elseif ($automation->action_type === Automation::ACTION_CREATE_FUTURE_TASK
            && ! empty($config['months_ahead'])) {
            $dueDate = Carbon::today()->addMonths((int) $config['months_ahead'])->toDateString();
        }

        $title = $config['title'] ?? $automation->name;
        if (! empty($context['task_title']) && str_contains($title, '{task_title}')) {
            $title = str_replace('{task_title}', $context['task_title'], $title);
        }

        $groupId = ProjectGroup::resolveIdForProject($project->id, null);

        $todo = Todo::create([
            'user_id' => $actor->id,
            'company_id' => $company->id,
            'project_id' => $project->id,
            'project_group_id' => $groupId,
            'workspace_id' => $config['workspace_id'] ?? $project->workspace_id,
            'operational_object_id' => $config['asset_id'] ?? ($context['asset_id'] ?? null),
            'title' => $title,
            'description' => $config['description'] ?? null,
            'assignee' => $config['assigned_to'] ?? null,
            'due_date' => $dueDate,
            'priority' => $config['priority'] ?? 'Medium',
            'type' => 'Task',
            'status' => 'todo',
            'category' => $config['category'] ?? null,
            'source' => 'automation',
            'metadata' => [
                'automation_id' => $automation->id,
                'trigger_context' => $context,
            ],
        ]);

        Activity::createTodoActivity($todo, $actor, 'todo_created');
    }

    private function sendNotification(Automation $automation, array $context): void
    {
        $config = $automation->action_config ?? [];
        $company = $automation->company;

        $users = $company->users();
        if (! empty($config['user_id'])) {
            $users->where('id', (int) $config['user_id']);
        }

        foreach ($users->get() as $user) {
            Notification::create([
                'user_id' => $user->id,
                'type' => 'automation_triggered',
                'title' => $config['title'] ?? $automation->name,
                'message' => $config['message'] ?? 'An automation was triggered.',
                'data' => [
                    'automation_id' => $automation->id,
                    'context' => $context,
                ],
                'is_read' => false,
            ]);
        }
    }

    private function changeStatus(Automation $automation, array $context): void
    {
        $todoId = $context['todo_id'] ?? ($automation->action_config['todo_id'] ?? null);
        if (! $todoId) {
            return;
        }

        $todo = Todo::query()
            ->forCompany($automation->company_id)
            ->where('id', (int) $todoId)
            ->first();

        if (! $todo) {
            return;
        }

        $status = $automation->action_config['status'] ?? 'todo';
        $todo->update([
            'status' => $status,
            'completed_at' => $status === 'done' ? now() : null,
        ]);
    }

    private function assignUser(Automation $automation, array $context): void
    {
        $todoId = $context['todo_id'] ?? null;
        $assignee = $automation->action_config['assigned_to'] ?? null;

        if (! $todoId || ! $assignee) {
            return;
        }

        $todo = Todo::query()
            ->forCompany($automation->company_id)
            ->where('id', (int) $todoId)
            ->first();

        if ($todo) {
            $todo->update(['assignee' => $assignee]);
        }
    }
}
