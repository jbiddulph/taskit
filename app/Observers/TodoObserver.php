<?php

namespace App\Observers;

use App\Models\Automation;
use App\Models\Todo;
use App\Services\AutomationRunnerService;

class TodoObserver
{
    public function __construct(
        protected AutomationRunnerService $runner,
    ) {}

    public function created(Todo $todo): void
    {
        if (! $todo->company_id) {
            return;
        }

        // Avoid automation → create_task → task_created loops.
        if ($todo->source === 'automation') {
            return;
        }

        $this->runner->dispatch(Automation::TRIGGER_TASK_CREATED, (int) $todo->company_id, [
            'todo_id' => $todo->id,
            'task_title' => $todo->title,
            'asset_id' => $todo->operational_object_id,
            'project_id' => $todo->project_id,
            'category' => $todo->category,
            'status' => $todo->status,
            'source' => $todo->source,
        ]);
    }

    public function updated(Todo $todo): void
    {
        if (! $todo->company_id) {
            return;
        }

        if (! $todo->wasChanged('status')) {
            return;
        }

        $status = (string) $todo->status;
        $completed = in_array($status, ['done', 'completed'], true);

        if (! $completed) {
            return;
        }

        $this->runner->dispatch(Automation::TRIGGER_TASK_COMPLETED, (int) $todo->company_id, [
            'todo_id' => $todo->id,
            'task_title' => $todo->title,
            'asset_id' => $todo->operational_object_id,
            'project_id' => $todo->project_id,
            'category' => $todo->category,
            'status' => $todo->status,
            'source' => $todo->source,
        ]);
    }
}
