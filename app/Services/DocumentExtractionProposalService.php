<?php

namespace App\Services;

use App\Models\ComplianceRequirement;
use App\Models\DocumentExtractionProposal;
use App\Models\User;
use App\Services\CacheService;
use Carbon\Carbon;

class DocumentExtractionProposalService
{
    public function __construct(
        protected ComplianceTaskGeneratorService $taskGeneratorService,
    ) {}

    public function approve(User $user, DocumentExtractionProposal $proposal, ?int $projectId = null): array
    {
        if ($proposal->user_id !== $user->id) {
            abort(403);
        }

        if (! $proposal->isPending()) {
            abort(422, 'Proposal already reviewed.');
        }

        $document = $proposal->operationalDocument;
        $object = $proposal->operationalObject;
        $data = $proposal->extracted_data;

        $expiresAt = $this->parseDate($data['expiry_date'] ?? null) ?? $document->expires_at;
        $requirementType = $data['document_type'] ?? $data['requirement_type'] ?? 'other';
        $label = $data['label'] ?? $document->title;

        $document->update([
            'document_type' => $requirementType,
            'expires_at' => $expiresAt,
            'extracted_data' => $data,
            'title' => $label,
        ]);
        $document->refreshExpiryStatus();

        $requirement = ComplianceRequirement::firstOrCreate(
            [
                'operational_object_id' => $object->id,
                'requirement_type' => $requirementType,
            ],
            [
                'company_id' => $object->company_id,
                'project_id' => $projectId,
                'label' => $label,
                'frequency' => 'annual',
                'lead_time_days' => 30,
                'auto_create_tasks' => true,
            ],
        );

        if ($projectId) {
            $requirement->update(['project_id' => $projectId]);
        }

        if ($expiresAt) {
            $requirement->update(['next_due_date' => $expiresAt]);
            $requirement->refreshStatus();
        }

        $document->update(['compliance_requirement_id' => $requirement->id]);

        $createdTask = null;
        $shouldCreateTask = $requirement->shouldCreateTaskToday()
            || ($projectId && ! $requirement->hasOpenTask());

        if ($shouldCreateTask && $this->taskGeneratorService->createTaskForRequirement($requirement)) {
            $createdTask = $requirement->todos()->latest()->first();

            if ($createdTask) {
                CacheService::invalidateUserCaches($user->id, $user->company_id);
                CacheService::invalidateProjectCaches($createdTask->project_id, $user->company_id);
            }
        }

        $proposal->update([
            'status' => DocumentExtractionProposal::STATUS_APPROVED,
            'reviewed_at' => now(),
        ]);

        return [
            'document' => $document->fresh(),
            'requirement' => $requirement->fresh(),
            'task' => $createdTask,
        ];
    }

    public function dismiss(User $user, DocumentExtractionProposal $proposal): void
    {
        if ($proposal->user_id !== $user->id) {
            abort(403);
        }

        $proposal->update([
            'status' => DocumentExtractionProposal::STATUS_DISMISSED,
            'reviewed_at' => now(),
        ]);
    }

    protected function parseDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
