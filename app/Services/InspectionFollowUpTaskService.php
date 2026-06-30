<?php

namespace App\Services;

use App\Models\Inspection;
use App\Models\Project;
use App\Models\ProjectGroup;
use App\Models\Todo;
use App\Support\InspectionTemplates;

class InspectionFollowUpTaskService
{
    public function createForFailedItems(Inspection $inspection, ?int $projectId = null): array
    {
        $inspection->load(['operationalObject', 'inspector', 'complianceRequirement']);

        $industry = $inspection->operationalObject?->company?->industry;
        $template = InspectionTemplates::get($industry, $inspection->template_key) ?? ['items' => []];
        $responses = $inspection->responses ?? [];

        $failedItems = collect($template['items'] ?? [])
            ->filter(fn (array $item) => ($item['type'] ?? '') === 'pass_fail_na')
            ->filter(fn (array $item) => ($responses[$item['key']] ?? null) === 'fail')
            ->values();

        if ($failedItems->isEmpty()) {
            return [];
        }

        $project = $this->resolveProject($inspection, $projectId);
        if (! $project) {
            return [];
        }

        $owner = $inspection->inspector ?? $project->owner;
        if (! $owner) {
            return [];
        }

        $object = $inspection->operationalObject;
        $created = [];
        $projectGroupId = ProjectGroup::resolveOrCreateForProject($project);

        foreach ($failedItems as $item) {
            $title = "Follow-up: {$item['label']} — {$object->name}";
            $inspectionUrl = route('inspections.show', $inspection);

            $description = "Failed during {$inspection->label}.";
            if ($inspection->summary) {
                $description .= "\n\nInspection notes: {$inspection->summary}";
            }
            $description .= "\n\nView inspection: {$inspectionUrl}";

            $todo = Todo::create([
                'user_id' => $owner->id,
                'company_id' => $inspection->company_id,
                'project_id' => $project->id,
                'project_group_id' => $projectGroupId,
                'operational_object_id' => $object->id,
                'inspection_id' => $inspection->id,
                'compliance_requirement_id' => $inspection->compliance_requirement_id,
                'source' => 'inspection_follow_up',
                'title' => $title,
                'description' => $description,
                'priority' => 'High',
                'type' => 'Inspection',
                'assignee' => $inspection->complianceRequirement?->assignee ?? $owner->name,
                'due_date' => now()->addDays(3)->toDateString(),
                'location_name' => $object->name,
                'location_address' => $object->full_address ?: null,
                'latitude' => $object->latitude,
                'longitude' => $object->longitude,
                'tags' => ['follow-up', 'inspection', $item['key']],
                'status' => 'todo',
            ]);

            $created[] = $todo;
        }

        if ($created !== []) {
            CacheService::invalidateUserCaches($owner->id, $inspection->company_id);
            CacheService::invalidateProjectCaches($project->id, $inspection->company_id);
        }

        return $created;
    }

    public function hasFailedItems(Inspection $inspection, ?string $industry = null): bool
    {
        $industry = $industry ?? $inspection->operationalObject?->company?->industry;
        $template = InspectionTemplates::get($industry, $inspection->template_key) ?? ['items' => []];
        $responses = $inspection->responses ?? [];

        foreach ($template['items'] ?? [] as $item) {
            if (($item['type'] ?? '') === 'pass_fail_na' && ($responses[$item['key']] ?? null) === 'fail') {
                return true;
            }
        }

        return false;
    }

    protected function resolveProject(Inspection $inspection, ?int $projectId): ?Project
    {
        if ($projectId) {
            $project = Project::find($projectId);
            if ($project && $project->company_id === $inspection->company_id) {
                return $project;
            }
        }

        if ($inspection->complianceRequirement?->project_id) {
            $project = Project::find($inspection->complianceRequirement->project_id);
            if ($project) {
                return $project;
            }
        }

        return Project::query()
            ->where('company_id', $inspection->company_id)
            ->where('is_active', true)
            ->orderBy('viewing_order')
            ->first();
    }
}
