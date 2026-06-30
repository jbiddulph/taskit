<?php

namespace App\Services;

use App\Models\ComplianceRequirement;
use App\Models\DocumentExtractionProposal;
use App\Models\OperationalObject;
use App\Models\Project;
use App\Models\ProjectGroup;
use App\Models\Todo;
use App\Models\User;
use App\Support\ComplianceTemplates;
use App\Support\Industries;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DocumentExtractionProposalService
{
    public function __construct(
        protected ComplianceTaskGeneratorService $taskGeneratorService,
        protected MapboxService $mapboxService,
        protected TodoWebSocketService $webSocketService,
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
        $data = $proposal->extracted_data ?? [];

        $projectId = $projectId
            ?? $proposal->metadata['project_id'] ?? null;

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

        $this->applyExtractedAddressToSite($object, $data);

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

        $createdTasks = [];
        if ($projectId) {
            $createdTasks = $this->createTasksFromExtraction(
                $user,
                $object,
                $requirement,
                $data,
                (int) $projectId,
                $label,
                $proposal->summary,
            );
        } elseif ($requirement->shouldCreateTaskToday() && $this->taskGeneratorService->createTaskForRequirement($requirement)) {
            $task = $requirement->todos()->latest()->first();
            if ($task) {
                $createdTasks[] = $task;
            }
        }

        if ($createdTasks !== []) {
            CacheService::invalidateUserCaches($user->id, $user->company_id);
            $firstProjectId = $createdTasks[0]->project_id;
            if ($firstProjectId) {
                CacheService::invalidateProjectCaches($firstProjectId, $user->company_id);
            }
        }

        $proposal->update([
            'status' => DocumentExtractionProposal::STATUS_APPROVED,
            'reviewed_at' => now(),
        ]);

        return [
            'document' => $document->fresh(),
            'requirement' => $requirement->fresh(),
            'task' => $createdTasks[0] ?? null,
            'tasks' => $createdTasks,
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

    /**
     * @return array<int, Todo>
     */
    protected function createTasksFromExtraction(
        User $user,
        OperationalObject $object,
        ComplianceRequirement $requirement,
        array $data,
        int $projectId,
        string $label,
        ?string $summary,
    ): array {
        $project = Project::query()
            ->where('company_id', $user->company_id)
            ->where('id', $projectId)
            ->first();

        if (! $project) {
            return [];
        }

        $projectGroupId = ProjectGroup::resolveOrCreateForProject($project);
        $location = $this->resolveLocation($object, $data);
        $industry = $user->company?->industry;
        $taskType = ComplianceTemplates::taskTypeFor(
            Industries::resolve($industry),
            $requirement->requirement_type,
        );

        $definitions = $this->buildTaskDefinitions($object, $label, $data, $summary);
        $created = [];

        foreach ($definitions as $definition) {
            if ($requirement->todos()->where('title', $definition['title'])->where('status', '!=', 'done')->exists()) {
                continue;
            }

            $todo = Todo::create([
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'project_id' => $project->id,
                'project_group_id' => $projectGroupId,
                'operational_object_id' => $object->id,
                'compliance_requirement_id' => $requirement->id,
                'source' => 'document_extraction',
                'title' => $definition['title'],
                'description' => $definition['description'],
                'priority' => $definition['priority'],
                'type' => $taskType,
                'assignee' => $requirement->assignee,
                'due_date' => $definition['due_date'],
                'location_name' => $location['location_name'],
                'location_address' => $location['location_address'],
                'latitude' => $location['latitude'],
                'longitude' => $location['longitude'],
                'tags' => $definition['tags'],
                'status' => 'todo',
            ]);

            $this->webSocketService->todoCreated($todo);
            $created[] = $todo;
        }

        return $created;
    }

    protected function buildTaskDefinitions(
        OperationalObject $object,
        string $label,
        array $data,
        ?string $summary,
    ): array {
        $definitions = [];
        $expiry = $this->parseDate($data['expiry_date'] ?? null);
        $issue = $this->parseDate($data['issue_date'] ?? null);
        $reminderLeadDays = 30;

        if ($expiry) {
            $definitions[] = [
                'title' => "Renew {$label} — {$object->name}",
                'description' => $this->taskDescription($data, $summary, 'Certificate renewal due.'),
                'due_date' => $expiry->copy()->subDays($reminderLeadDays)->toDateString(),
                'priority' => $expiry->isPast() ? 'High' : 'Medium',
                'tags' => ['compliance', 'renewal', $data['document_type'] ?? 'certificate'],
            ];

            if ($expiry->isFuture()) {
                $definitions[] = [
                    'title' => "{$label} expires — {$object->name}",
                    'description' => $this->taskDescription($data, $summary, 'Certificate expiry date.'),
                    'due_date' => $expiry->toDateString(),
                    'priority' => 'High',
                    'tags' => ['compliance', 'expiry', $data['document_type'] ?? 'certificate'],
                ];
            }
        }

        if ($issue && $issue->isFuture()) {
            $definitions[] = [
                'title' => "Certificate issued — {$label}",
                'description' => $this->taskDescription($data, $summary, 'Issue date noted on uploaded certificate.'),
                'due_date' => $issue->toDateString(),
                'priority' => 'Low',
                'tags' => ['compliance', 'issue-date'],
            ];
        }

        foreach ($data['suggested_tasks'] ?? [] as $suggested) {
            if (! is_array($suggested) || empty($suggested['title'])) {
                continue;
            }

            $definitions[] = [
                'title' => (string) $suggested['title'],
                'description' => $suggested['notes'] ?? $summary,
                'due_date' => $this->parseDate($suggested['due_date'] ?? null)?->toDateString(),
                'priority' => in_array($suggested['priority'] ?? '', ['Low', 'Medium', 'High', 'Critical'], true)
                    ? $suggested['priority']
                    : 'Medium',
                'tags' => ['document-extraction'],
            ];
        }

        if ($definitions === []) {
            $definitions[] = [
                'title' => "{$label} — {$object->name}",
                'description' => $this->taskDescription($data, $summary, 'Follow up on uploaded certificate.'),
                'due_date' => now()->addDays(7)->toDateString(),
                'priority' => 'Medium',
                'tags' => ['compliance', 'document'],
            ];
        }

        return $definitions;
    }

    protected function taskDescription(array $data, ?string $summary, string $prefix): string
    {
        $parts = array_filter([
            $prefix,
            $summary,
            ! empty($data['certificate_number']) ? 'Certificate #: '.$data['certificate_number'] : null,
            ! empty($data['engineer_name']) ? 'Engineer: '.$data['engineer_name'] : null,
            ! empty($data['address']) ? 'Address: '.$data['address'] : null,
        ]);

        return implode("\n", $parts);
    }

    protected function applyExtractedAddressToSite(OperationalObject $object, array $data): void
    {
        $address = trim((string) ($data['address'] ?? ''));
        if ($address === '' || $object->address_line_1) {
            return;
        }

        $object->update(['address_line_1' => $address]);
        $object->refresh();

        $location = $this->resolveLocation($object, $data);
        if ($location['latitude'] && $location['longitude']) {
            $object->update([
                'latitude' => $location['latitude'],
                'longitude' => $location['longitude'],
            ]);
        }
    }

    protected function resolveLocation(OperationalObject $object, array $data): array
    {
        if ($object->latitude && $object->longitude) {
            return [
                'location_name' => $object->name,
                'location_address' => $object->full_address ?: null,
                'latitude' => $object->latitude,
                'longitude' => $object->longitude,
            ];
        }

        $query = trim((string) ($data['address'] ?? $object->full_address));
        if ($query === '' || ! $this->mapboxService->isConfigured()) {
            return [
                'location_name' => $object->name,
                'location_address' => $query ?: $object->full_address ?: null,
                'latitude' => $object->latitude,
                'longitude' => $object->longitude,
            ];
        }

        $results = $this->mapboxService->geocode($query);
        $best = $results[0] ?? null;

        return [
            'location_name' => $best['location_name'] ?? $object->name,
            'location_address' => $best['location_address'] ?? $query,
            'latitude' => $best['latitude'] ?? $object->latitude,
            'longitude' => $best['longitude'] ?? $object->longitude,
        ];
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
