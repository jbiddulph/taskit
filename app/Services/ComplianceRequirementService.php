<?php

namespace App\Services;

use App\Models\ComplianceRequirement;
use App\Models\OperationalObject;
use App\Support\ComplianceTemplates;
use Carbon\Carbon;

class ComplianceRequirementService
{
    public function applyIndustryTemplate(OperationalObject $object, ?int $projectId = null): int
    {
        $object->loadMissing('company');
        $company = $object->company;
        $templates = ComplianceTemplates::forIndustry($company->industry);
        $created = 0;

        foreach ($templates as $template) {
            $requirement = ComplianceRequirement::firstOrCreate(
                [
                    'operational_object_id' => $object->id,
                    'requirement_type' => $template['type'],
                ],
                [
                    'company_id' => $object->company_id,
                    'project_id' => $projectId,
                    'label' => $template['label'],
                    'frequency' => $template['frequency'] ?? 'annual',
                    'lead_time_days' => $template['lead_days'] ?? 30,
                    'status' => ComplianceRequirement::STATUS_MISSING,
                    'auto_create_tasks' => true,
                ],
            );

            $requirement->refreshStatus();
            $created++;
        }

        return $created;
    }

    public function markCompleted(ComplianceRequirement $requirement, ?Carbon $completedAt = null): ComplianceRequirement
    {
        $completedAt = ($completedAt ?? now())->startOfDay();
        $frequencyDays = ComplianceTemplates::frequencyDays($requirement->frequency);

        $requirement->update([
            'last_completed_at' => $completedAt,
            'next_due_date' => $completedAt->copy()->addDays($frequencyDays),
        ]);

        $requirement->refreshStatus();

        return $requirement->fresh();
    }
}
