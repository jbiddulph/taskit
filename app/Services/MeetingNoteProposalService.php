<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\MeetingNoteProposal;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class MeetingNoteProposalService
{
    public function __construct(
        protected TodoWebSocketService $webSocketService,
        protected AssignmentNotificationService $assignmentNotificationService,
        protected MapboxService $mapboxService,
    ) {}

    public function createFromN8n(array $payload): MeetingNoteProposal
    {
        $validator = Validator::make($payload, [
            'user_id' => 'required|integer|exists:taskit_users,id',
            'company_id' => 'nullable|integer|exists:taskit_companies,id',
            'transcript' => 'nullable|string',
            'meeting_summary' => 'nullable|string',
            'suggested_project_name' => 'nullable|string|max:255',
            'action_items' => 'present|array',
            'action_items.*.title' => 'required|string|max:255',
            'action_items.*.priority' => 'nullable|in:Low,Medium,High,Critical',
            'action_items.*.status' => 'nullable|in:todo,in-progress,qa-testing,done',
            'action_items.*.due_date' => 'nullable|date_format:Y-m-d',
            'action_items.*.project_name' => 'nullable|string|max:255',
            'action_items.*.location_query' => 'nullable|string|max:255',
            'action_items.*.confidence' => 'nullable|numeric|min:0|max:1',
            'duration_seconds' => 'nullable|integer',
            'stopped_reason' => 'nullable|string',
            'recorded_at' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $user = User::findOrFail($payload['user_id']);
        $actionItems = collect($payload['action_items'])
            ->filter(fn ($item) => ! empty($item['title']))
            ->map(fn ($item) => $this->normalizeActionItem($item))
            ->values()
            ->all();

        $actionItems = $this->enrichActionItemsWithLocations($actionItems);

        $actionItems = $this->enforceRequestedActionItemCount(
            $actionItems,
            $payload['transcript'] ?? null
        );

        $proposal = MeetingNoteProposal::create([
            'user_id' => $user->id,
            'company_id' => $payload['company_id'] ?? $user->company_id,
            'status' => MeetingNoteProposal::STATUS_PENDING,
            'meeting_summary' => $payload['meeting_summary'] ?? null,
            'transcript' => $payload['transcript'] ?? null,
            'action_items' => $actionItems,
            'metadata' => [
                'duration_seconds' => $payload['duration_seconds'] ?? null,
                'stopped_reason' => $payload['stopped_reason'] ?? null,
                'recorded_at' => $payload['recorded_at'] ?? null,
                'key_decisions' => $payload['key_decisions'] ?? [],
                'follow_ups' => $payload['follow_ups'] ?? [],
                'suggested_project_name' => $payload['suggested_project_name'] ?? null,
                'recording_template' => $payload['recording_template'] ?? null,
            ],
        ]);

        $itemCount = count($actionItems);

        Notification::create([
            'user_id' => $user->id,
            'type' => 'meeting_notes',
            'title' => 'Meeting notes ready for review',
            'message' => $itemCount > 0
                ? "{$itemCount} proposed ".($itemCount === 1 ? 'item' : 'items').' from your recording.'
                : 'Your meeting recording was processed. Review the summary.',
            'data' => [
                'proposal_id' => $proposal->id,
            ],
        ]);

        return $proposal;
    }

    public function approve(User $user, MeetingNoteProposal $proposal, int $projectId, array $items): array
    {
        $this->assertUserOwnsPendingProposal($user, $proposal);

        $project = Project::query()
            ->where('id', $projectId)
            ->when($user->company_id, fn ($q) => $q->where('company_id', $user->company_id))
            ->firstOrFail();

        $validator = Validator::make(['items' => $items], [
            'items' => 'required|array|min:1',
            'items.*.index' => 'required|integer|min:0',
            'items.*.approved' => 'required|boolean',
            'items.*.title' => 'nullable|string|max:255',
            'items.*.priority' => 'nullable|in:Low,Medium,High,Critical',
            'items.*.status' => 'nullable|in:todo,in-progress,qa-testing,done',
            'items.*.due_date' => 'nullable|date_format:Y-m-d',
            'items.*.assignee' => 'nullable|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.location_query' => 'nullable|string|max:255',
            'items.*.location_name' => 'nullable|string|max:255',
            'items.*.location_address' => 'nullable|string|max:500',
            'items.*.latitude' => 'nullable|numeric|between:-90,90',
            'items.*.longitude' => 'nullable|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $hasApprovedItem = collect($items)->contains(fn ($item) => ! empty($item['approved']));
        if (! $hasApprovedItem) {
            throw ValidationException::withMessages([
                'items' => ['Select at least one action item to approve.'],
            ]);
        }

        $createdTodos = [];

        DB::transaction(function () use ($user, $proposal, $project, $items, &$createdTodos) {
            foreach ($items as $item) {
                if (empty($item['approved'])) {
                    continue;
                }

                $original = $proposal->action_items[$item['index']] ?? null;
                if (! $original) {
                    continue;
                }

                $title = trim($item['title'] ?? $original['title'] ?? '');
                if ($title === '') {
                    continue;
                }

                $description = $item['description']
                    ?? $original['notes']
                    ?? null;

                if ($proposal->meeting_summary && $description) {
                    $description = trim($description."\n\nMeeting summary: ".mb_substr($proposal->meeting_summary, 0, 500));
                } else                if ($proposal->meeting_summary && ! $description) {
                    $description = 'From meeting notes: '.mb_substr($proposal->meeting_summary, 0, 500);
                }

                $location = $this->resolveApprovedItemLocation($item, $original);

                $todo = Todo::create([
                    'user_id' => $user->id,
                    'project_id' => $project->id,
                    'company_id' => $user->company_id,
                    'title' => $title,
                    'description' => $description,
                    'priority' => $item['priority'] ?? $original['priority'] ?? 'Medium',
                    'assignee' => $item['assignee'] ?? $original['assignee'] ?? null,
                    'due_date' => $item['due_date'] ?? $original['due_date'] ?? null,
                    'status' => $this->normalizeStatus($item['status'] ?? $original['status'] ?? 'todo', $item['description'] ?? $original['notes'] ?? null),
                    'location_name' => $location['location_name'] ?? null,
                    'location_address' => $location['location_address'] ?? null,
                    'latitude' => $location['latitude'] ?? null,
                    'longitude' => $location['longitude'] ?? null,
                ]);

                $todo->load(['comments', 'attachments', 'project', 'subtasks.project', 'parentTask']);

                CacheService::invalidateUserCaches($user->id, $user->company_id);
                CacheService::invalidateProjectCaches($project->id, $user->company_id);

                $this->webSocketService->todoCreated($todo);
                $this->assignmentNotificationService->sendNewTodoAssignmentNotification($todo);
                Activity::createTodoActivity($todo, $user, 'todo_created');

                $createdTodos[] = $todo;
            }

            $proposal->update([
                'status' => MeetingNoteProposal::STATUS_APPROVED,
                'project_id' => $project->id,
                'reviewed_at' => now(),
            ]);
        });

        return $createdTodos;
    }

    public function dismiss(User $user, MeetingNoteProposal $proposal): void
    {
        $this->assertUserOwnsPendingProposal($user, $proposal);

        $proposal->update([
            'status' => MeetingNoteProposal::STATUS_DISMISSED,
            'reviewed_at' => now(),
        ]);
    }

    private function normalizeStatus(?string $status, ?string $context = null): string
    {
        $combined = strtolower(trim(implode(' ', array_filter([$status, $context]))));

        if ($combined === '') {
            return 'todo';
        }

        if (preg_match('/\b(in[\s-]?progress|progress\s+column|being\s+worked|working\s+on|under[\s-]?way|started|active|wip|doing|current\s+sprint)\b/', $combined)) {
            return 'in-progress';
        }

        if (preg_match('/\b(q\s*&?\s*a|qa[\s-]?testing|testing\s+column|in\s+review|for\s+review|needs?\s+review|quality\s+assurance|testing)\b/', $combined)) {
            return 'qa-testing';
        }

        if (preg_match('/\b(completed?|finished|shipped|closed|done\s+column)\b/', $combined)) {
            return 'done';
        }

        if (preg_match('/\b(to[\s-]?do|todo\s+column|backlog|not\s+started|new\s+column|pending|icebox)\b/', $combined)) {
            return 'todo';
        }

        $normalized = strtolower(trim((string) $status));

        return match ($normalized) {
            'in progress', 'in-progress', 'in_progress', 'doing', 'active', 'progress' => 'in-progress',
            'qa', 'qa-testing', 'qa testing', 'testing', 'review' => 'qa-testing',
            'done', 'complete', 'completed', 'finished' => 'done',
            default => 'todo',
        };
    }

    private function assertUserOwnsPendingProposal(User $user, MeetingNoteProposal $proposal): void
    {
        if ($proposal->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        if (! $proposal->isPending()) {
            abort(422, 'This meeting note proposal has already been reviewed.');
        }
    }

    private function enforceRequestedActionItemCount(array $actionItems, ?string $transcript): array
    {
        if (! $transcript) {
            return $actionItems;
        }

        $requestedCount = $this->inferRequestedItemCount($transcript);
        if ($requestedCount <= 0 || count($actionItems) >= $requestedCount) {
            return $actionItems;
        }

        $label = $this->inferItemTypeLabel($transcript);
        $defaultStatus = $this->normalizeStatus(null, $transcript);
        $defaultProject = $actionItems[0]['project_name'] ?? null;

        while (count($actionItems) < $requestedCount) {
            $actionItems[] = [
                'title' => $label.' '.(count($actionItems) + 1),
                'assignee' => null,
                'priority' => 'Medium',
                'status' => $actionItems[0]['status'] ?? $defaultStatus,
                'due_date' => null,
                'project_name' => $defaultProject,
                'notes' => 'Requested in recording — edit title before approving.',
            ];
        }

        return $actionItems;
    }

    private function inferRequestedItemCount(string $transcript): int
    {
        $text = strtolower($transcript);
        $max = 0;

        $itemWords = 'todos?|tasks?|notes?|action\s+items?|reminders?|items?|things\s+to\s+do';

        $patterns = [
            '/(?:create|add|make|need|want|generate|give\s+me|i\s+need|i\s+want|let\'?s\s+create|let\s+us\s+create)\s+(\d{1,2})\s+(?:'.$itemWords.')/i',
            '/(\d{1,2})\s+(?:'.$itemWords.')(?:\s+to)?(?:\s+create)?/i',
            '/(?:create|add|make)\s+(\d{1,2})(?:\s+(?:new|more))?/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                foreach ($matches[1] as $num) {
                    $count = (int) $num;
                    if ($count > $max && $count <= 20) {
                        $max = $count;
                    }
                }
            }
        }

        $wordMap = [
            'one' => 1, 'two' => 2, 'three' => 3, 'four' => 4, 'five' => 5,
            'six' => 6, 'seven' => 7, 'eight' => 8, 'nine' => 9, 'ten' => 10,
            'eleven' => 11, 'twelve' => 12,
        ];

        $wordPattern = '/(?:create|add|make|need|want|generate|give\s+me|i\s+need|i\s+want)\s+(one|two|three|four|five|six|seven|eight|nine|ten|eleven|twelve)\s+(?:'.$itemWords.')/i';
        if (preg_match_all($wordPattern, $text, $matches)) {
            foreach ($matches[1] as $word) {
                $count = $wordMap[$word] ?? 0;
                if ($count > $max) {
                    $max = $count;
                }
            }
        }

        return $max;
    }

    private function inferItemTypeLabel(string $transcript): string
    {
        $text = strtolower($transcript);

        if (preg_match('/\bnotes?\b/', $text)) {
            return 'Note';
        }

        if (preg_match('/\btasks?\b/', $text)) {
            return 'Task';
        }

        return 'Todo';
    }

    private function normalizeActionItem(array $item): array
    {
        $confidence = isset($item['confidence']) ? (float) $item['confidence'] : null;
        if ($confidence !== null) {
            $confidence = max(0, min(1, $confidence));
        }

        return [
            'title' => $item['title'],
            'assignee' => $item['assignee'] ?? null,
            'priority' => $item['priority'] ?? 'Medium',
            'status' => $this->normalizeStatus($item['status'] ?? 'todo', $item['notes'] ?? null),
            'due_date' => $item['due_date'] ?? null,
            'project_name' => $item['project_name'] ?? null,
            'notes' => $item['notes'] ?? null,
            'location_query' => trim((string) ($item['location_query'] ?? '')) ?: null,
            'location_name' => $item['location_name'] ?? null,
            'location_address' => $item['location_address'] ?? null,
            'latitude' => isset($item['latitude']) ? (float) $item['latitude'] : null,
            'longitude' => isset($item['longitude']) ? (float) $item['longitude'] : null,
            'confidence' => $confidence,
        ];
    }

    private function enrichActionItemsWithLocations(array $actionItems): array
    {
        if (! $this->mapboxService->isConfigured()) {
            return $actionItems;
        }

        return array_map(function (array $item) {
            if (empty($item['location_query']) || ($item['latitude'] && $item['longitude'])) {
                return $item;
            }

            $results = $this->mapboxService->geocode($item['location_query']);
            $match = $results[0] ?? null;
            if (! $match) {
                return $item;
            }

            return array_merge($item, [
                'location_name' => $match['location_name'] ?? $item['location_query'],
                'location_address' => $match['location_address'] ?? null,
                'latitude' => $match['latitude'] ?? null,
                'longitude' => $match['longitude'] ?? null,
            ]);
        }, $actionItems);
    }

    private function resolveApprovedItemLocation(array $item, ?array $original): array
    {
        $locationQuery = trim((string) ($item['location_query'] ?? $original['location_query'] ?? ''));
        $latitude = $item['latitude'] ?? $original['latitude'] ?? null;
        $longitude = $item['longitude'] ?? $original['longitude'] ?? null;
        $locationName = $item['location_name'] ?? $original['location_name'] ?? null;
        $locationAddress = $item['location_address'] ?? $original['location_address'] ?? null;

        if ($locationQuery !== '' && $this->mapboxService->isConfigured()) {
            $results = $this->mapboxService->geocode($locationQuery);
            $match = $results[0] ?? null;
            if ($match) {
                return [
                    'location_name' => $match['location_name'] ?? $locationName ?? $locationQuery,
                    'location_address' => $match['location_address'] ?? $locationAddress,
                    'latitude' => $match['latitude'] ?? null,
                    'longitude' => $match['longitude'] ?? null,
                ];
            }
        }

        if ($latitude && $longitude) {
            return [
                'location_name' => $locationName,
                'location_address' => $locationAddress,
                'latitude' => (float) $latitude,
                'longitude' => (float) $longitude,
            ];
        }

        return [
            'location_name' => null,
            'location_address' => null,
            'latitude' => null,
            'longitude' => null,
        ];
    }
}
