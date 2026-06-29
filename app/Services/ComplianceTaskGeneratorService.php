<?php

namespace App\Services;

use App\Models\ComplianceRequirement;
use App\Models\Company;
use App\Models\Project;
use App\Models\Todo;
use App\Models\User;
use App\Support\ComplianceTemplates;
use App\Support\Industries;

class ComplianceTaskGeneratorService
{
    public function __construct(
        protected TodoWebSocketService $webSocketService,
    ) {}

    public function generateForCompany(Company $company): int
    {
        $created = 0;

        $requirements = ComplianceRequirement::forCompany($company->id)
            ->autoCreateTasks()
            ->with(['operationalObject', 'project', 'company'])
            ->get();

        foreach ($requirements as $requirement) {
            $requirement->refreshStatus();

            if (! $requirement->shouldCreateTaskToday()) {
                continue;
            }

            if ($this->createTaskForRequirement($requirement)) {
                $created++;
            }
        }

        return $created;
    }

    public function generateForAllCompanies(): int
    {
        $created = 0;

        Company::query()
            ->whereNotNull('industry')
            ->chunkById(50, function ($companies) use (&$created) {
                foreach ($companies as $company) {
                    $created += $this->generateForCompany($company);
                }
            });

        return $created;
    }

    public function createTaskForRequirement(ComplianceRequirement $requirement): bool
    {
        $object = $requirement->operationalObject;
        if (! $object) {
            return false;
        }

        $project = $this->resolveProject($requirement);
        if (! $project) {
            return false;
        }

        $owner = $this->resolveTaskOwner($requirement, $project);
        if (! $owner) {
            return false;
        }

        $industry = $requirement->company?->industry;
        $taskType = ComplianceTemplates::taskTypeFor(
            Industries::resolve($industry),
            $requirement->requirement_type,
        );

        $dueDate = $requirement->next_due_date?->toDateString();
        $title = "{$requirement->label} — {$object->name}";

        $todo = Todo::create([
            'user_id' => $owner->id,
            'company_id' => $requirement->company_id,
            'project_id' => $project->id,
            'operational_object_id' => $object->id,
            'compliance_requirement_id' => $requirement->id,
            'source' => 'compliance_schedule',
            'title' => $title,
            'description' => "Scheduled compliance task for {$requirement->label}.",
            'priority' => $requirement->status === ComplianceRequirement::STATUS_OVERDUE ? 'High' : 'Medium',
            'type' => $taskType,
            'assignee' => $requirement->assignee,
            'due_date' => $dueDate,
            'location_name' => $object->name,
            'location_address' => $object->full_address ?: null,
            'latitude' => $object->latitude,
            'longitude' => $object->longitude,
            'status' => 'todo',
        ]);

        $this->webSocketService->todoCreated($todo);

        CacheService::invalidateUserCaches($owner->id, $requirement->company_id);
        CacheService::invalidateProjectCaches($project->id, $requirement->company_id);

        return (bool) $todo->id;
    }

    protected function resolveProject(ComplianceRequirement $requirement): ?Project
    {
        if ($requirement->project_id) {
            $project = Project::find($requirement->project_id);
            if ($project) {
                return $project;
            }
        }

        return Project::query()
            ->where('company_id', $requirement->company_id)
            ->where('is_active', true)
            ->orderBy('viewing_order')
            ->first();
    }

    protected function resolveTaskOwner(ComplianceRequirement $requirement, Project $project): ?User
    {
        if ($requirement->assignee) {
            $assignee = User::query()
                ->where('company_id', $requirement->company_id)
                ->where('name', $requirement->assignee)
                ->first();

            if ($assignee) {
                return $assignee;
            }
        }

        return $project->owner ?? User::query()
            ->where('company_id', $requirement->company_id)
            ->orderBy('created_at')
            ->first();
    }
}
