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
            ->map(fn ($item) => [
                'title' => $item['title'],
                'assignee' => $item['assignee'] ?? null,
                'priority' => $item['priority'] ?? 'Medium',
                'status' => $this->normalizeStatus($item['status'] ?? 'todo', $item['notes'] ?? null),
                'due_date' => $item['due_date'] ?? null,
                'project_name' => $item['project_name'] ?? null,
                'notes' => $item['notes'] ?? null,
            ])
            ->values()
            ->all();

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
                } elseif ($proposal->meeting_summary && ! $description) {
                    $description = 'From meeting notes: '.mb_substr($proposal->meeting_summary, 0, 500);
                }

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
}
