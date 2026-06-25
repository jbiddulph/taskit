<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Project;
use App\Models\ProjectGroup;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VoiceCommandService
{
    public function __construct(
        protected VoiceCommandParser $parser,
        protected MapboxService $mapboxService,
        protected TodoWebSocketService $webSocketService,
        protected AssignmentNotificationService $assignmentNotificationService,
        protected TodaySummaryService $todaySummaryService,
    ) {}

    public function process(
        User $user,
        string $transcript,
        ?int $projectId = null,
        bool $confirmDelete = false,
        ?int $deleteTodoId = null,
        ?int $confirmTaskId = null,
        ?string $confirmedIntent = null,
        ?array $confirmedPayload = null,
    ): array {
        if ($confirmDelete && $deleteTodoId) {
            return $this->executeConfirmedDelete($user, $deleteTodoId);
        }

        if ($confirmTaskId && $confirmedIntent && is_array($confirmedPayload)) {
            return $this->executeConfirmedTask($user, $confirmTaskId, $confirmedIntent, $confirmedPayload, $projectId);
        }

        $project = $projectId ? $this->resolveAccessibleProject($user, $projectId) : null;
        $parsed = $this->parser->parse($transcript);
        $intent = $parsed['intent'] ?? 'unknown';

        return match ($intent) {
            'summary' => $this->handleSummary($user, $project),
            'delete' => $this->handleDelete($user, $project, $parsed),
            'move_board' => $this->handleMoveBoard($user, $project, $parsed),
            'update' => $this->handleUpdate($user, $project, $parsed),
            'create' => $this->handleCreate($user, $project, $parsed),
            default => [
                'success' => false,
                'message' => $parsed['message'] ?? 'Sorry, I could not understand that command.',
                'transcript' => $transcript,
            ],
        };
    }

    private function handleSummary(User $user, ?Project $project): array
    {
        $summary = $this->todaySummaryService->summarize($user, $project?->id);

        return [
            'success' => true,
            'action' => 'summary',
            'message' => $summary['spoken_summary'],
            'summary' => $summary,
        ];
    }

    private function handleDelete(User $user, ?Project $project, array $parsed): array
    {
        $resolution = $this->resolveTodoWithDisambiguation($user, $project, $parsed['match'] ?? []);

        if ($resolution['status'] === 'ambiguous') {
            return $this->disambiguationResponse('delete', $parsed, $resolution['candidates']);
        }

        $todo = $resolution['todo'] ?? null;
        if (! $todo) {
            return [
                'success' => false,
                'message' => 'I could not find a task with that ID or title. Try saying something like ZAPT-183 or the task name.',
            ];
        }

        return [
            'success' => true,
            'action' => 'confirm_delete',
            'pending_delete' => [
                'todo_id' => $todo->id,
                'title' => $todo->title,
                'reference_id' => $this->referenceIdForTodo($todo),
            ],
            'message' => 'Please confirm deletion of this task.',
        ];
    }

    private function handleMoveBoard(User $user, ?Project $project, array $parsed): array
    {
        $boardName = trim((string) ($parsed['board_name'] ?? ''));
        if ($boardName === '') {
            return [
                'success' => false,
                'message' => 'Which board should I move the task to? Try saying move to Sales board.',
            ];
        }

        $resolution = $this->resolveTodoWithDisambiguation($user, $project, $parsed['match'] ?? []);
        if ($resolution['status'] === 'ambiguous') {
            return $this->disambiguationResponse('move_board', $parsed, $resolution['candidates']);
        }

        $todo = $resolution['todo'] ?? null;
        if (! $todo) {
            return [
                'success' => false,
                'message' => 'I could not find a task to move. Say the task ID or title first.',
            ];
        }

        $targetProject = $project ?? $todo->project;
        if (! $targetProject) {
            return [
                'success' => false,
                'message' => 'I could not determine which project that task belongs to.',
            ];
        }

        $group = $this->resolveProjectGroup($targetProject, $boardName);
        if (! $group) {
            return [
                'success' => false,
                'message' => "I could not find a board called \"{$boardName}\" in {$targetProject->name}.",
            ];
        }

        return $this->executeMoveBoard($user, $todo, $group);
    }

    private function executeMoveBoard(User $user, Todo $todo, ProjectGroup $group): array
    {
        if ($todo->project_group_id === $group->id) {
            return [
                'success' => true,
                'action' => 'updated',
                'todo' => $todo->load(['comments', 'attachments', 'project', 'subtasks.project', 'parentTask']),
                'message' => "\"{$todo->title}\" is already on the {$group->name} board.",
            ];
        }

        $todo->update(['project_group_id' => $group->id]);
        $todo->load(['comments', 'attachments', 'project', 'subtasks.project', 'parentTask']);

        CacheService::invalidateUserCaches($user->id, $user->company_id);
        CacheService::invalidateProjectCaches($todo->project_id, $user->company_id);
        $this->webSocketService->todoUpdated($todo);
        Activity::createTodoActivity($todo, $user, 'todo_updated');

        return [
            'success' => true,
            'action' => 'updated',
            'todo' => $todo,
            'message' => "Moved \"{$todo->title}\" to the {$group->name} board.",
        ];
    }

    private function executeConfirmedDelete(User $user, int $todoId): array
    {
        $todo = Todo::with('project')->findOrFail($todoId);

        if (! $todo->canAccess($user)) {
            abort(403, 'Unauthorized');
        }

        $title = $todo->title;
        $userId = $todo->user_id;
        $projectId = $todo->project_id;

        Activity::createTodoActivity($todo, $user, 'todo_deleted');
        $todo->delete();

        $this->webSocketService->todoDeleted($userId, $title);
        CacheService::invalidateUserCaches($user->id, $user->company_id);
        CacheService::invalidateProjectCaches($projectId, $user->company_id);

        return [
            'success' => true,
            'action' => 'deleted',
            'deleted_todo_id' => $todoId,
            'message' => "Deleted task \"{$title}\".",
        ];
    }

    private function executeConfirmedTask(
        User $user,
        int $todoId,
        string $intent,
        array $payload,
        ?int $projectId,
    ): array {
        $todo = Todo::with('project')->findOrFail($todoId);

        if (! $todo->canAccess($user)) {
            abort(403, 'Unauthorized');
        }

        return match ($intent) {
            'delete' => [
                'success' => true,
                'action' => 'confirm_delete',
                'pending_delete' => [
                    'todo_id' => $todo->id,
                    'title' => $todo->title,
                    'reference_id' => $this->referenceIdForTodo($todo),
                ],
                'message' => 'Please confirm deletion of this task.',
            ],
            'update' => $this->executeUpdate($user, $todo, $payload['updates'] ?? []),
            'move_board' => $this->executeMoveBoardForConfirmed($user, $todo, $payload, $projectId),
            default => [
                'success' => false,
                'message' => 'That confirmed action is not supported.',
            ],
        };
    }

    private function executeMoveBoardForConfirmed(User $user, Todo $todo, array $payload, ?int $projectId): array
    {
        $boardName = trim((string) ($payload['board_name'] ?? ''));
        if ($boardName === '') {
            return [
                'success' => false,
                'message' => 'Which board should I move the task to?',
            ];
        }

        $project = $projectId
            ? $this->resolveAccessibleProject($user, $projectId)
            : $todo->project;

        $group = $this->resolveProjectGroup($project, $boardName);
        if (! $group) {
            return [
                'success' => false,
                'message' => "I could not find a board called \"{$boardName}\".",
            ];
        }

        return $this->executeMoveBoard($user, $todo, $group);
    }

    private function handleUpdate(User $user, ?Project $project, array $parsed): array
    {
        $resolution = $this->resolveTodoWithDisambiguation($user, $project, $parsed['match'] ?? []);

        if ($resolution['status'] === 'ambiguous') {
            return $this->disambiguationResponse('update', $parsed, $resolution['candidates']);
        }

        $todo = $resolution['todo'] ?? null;
        if (! $todo) {
            return [
                'success' => false,
                'message' => 'I could not find a task to update. Say the task ID (like ZAPT-183) or the task title.',
            ];
        }

        $updates = $parsed['updates'] ?? [];
        if (empty($updates)) {
            return [
                'success' => false,
                'message' => 'I found the task but did not detect what to change. Try mentioning status, priority, due date, or location.',
            ];
        }

        return $this->executeUpdate($user, $todo, $updates);
    }

    private function executeUpdate(User $user, Todo $todo, array $updates): array
    {
        $payload = $this->applyLocationGeocode($updates);
        $oldAssignee = $todo->assignee;

        $todo->update($payload);
        $todo->load(['comments', 'attachments', 'project', 'subtasks.project', 'parentTask']);

        CacheService::invalidateUserCaches($user->id, $user->company_id);
        CacheService::invalidateProjectCaches($todo->project_id, $user->company_id);

        $this->webSocketService->todoUpdated($todo);
        $this->assignmentNotificationService->sendAssignmentNotification($todo, $oldAssignee);
        Activity::createTodoActivity($todo, $user, 'todo_updated');

        return [
            'success' => true,
            'action' => 'updated',
            'todo' => $todo,
            'message' => $this->buildUpdateMessage($todo, $updates),
        ];
    }

    private function handleCreate(User $user, ?Project $project, array $parsed): array
    {
        $project ??= $this->resolveDefaultProject($user);

        $create = $parsed['create'] ?? [];
        $title = trim((string) ($create['title'] ?? ''));

        if ($title === '') {
            throw ValidationException::withMessages([
                'transcript' => ['Please say what the new task should be called.'],
            ]);
        }

        unset($create['title']);
        $payload = array_merge([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'project_group_id' => ProjectGroup::resolveDefaultIdForProject($project->id),
            'company_id' => $user->company_id,
            'title' => Str::limit($title, 255, ''),
            'priority' => 'Medium',
            'status' => 'todo',
        ], $this->applyLocationGeocode($create));

        $todo = Todo::create($payload);
        $todo->load(['comments', 'attachments', 'project', 'subtasks.project', 'parentTask']);

        CacheService::invalidateUserCaches($user->id, $user->company_id);
        CacheService::invalidateProjectCaches($project->id, $user->company_id);

        $this->webSocketService->todoCreated($todo);
        $this->assignmentNotificationService->sendNewTodoAssignmentNotification($todo);
        Activity::createTodoActivity($todo, $user, 'todo_created');

        return [
            'success' => true,
            'action' => 'created',
            'todo' => $todo,
            'message' => "Created task \"{$todo->title}\".",
        ];
    }

    /**
     * @return array{status: 'found'|'missing'|'ambiguous', todo?: Todo, candidates?: array<int, array<string, mixed>>}
     */
    private function resolveTodoWithDisambiguation(User $user, ?Project $project, array $match): array
    {
        $accessible = $this->accessibleTodos($user);

        if (! empty($match['todo_id'])) {
            $todo = $accessible->firstWhere('id', (int) $match['todo_id']);
            if ($todo) {
                return ['status' => 'found', 'todo' => $todo];
            }
        }

        if (! empty($match['reference_id']) && preg_match('/-(\d+)$/', $match['reference_id'], $m)) {
            $todo = $accessible->firstWhere('id', (int) $m[1]);
            if ($todo) {
                return ['status' => 'found', 'todo' => $todo];
            }
        }

        $candidates = $project
            ? $accessible->where('project_id', $project->id)
            : $accessible;

        if (! empty($match['title'])) {
            $needle = strtolower($match['title']);
            $exact = $candidates->first(fn (Todo $todo) => strtolower($todo->title) === $needle);
            if ($exact) {
                return ['status' => 'found', 'todo' => $exact];
            }

            $contains = $candidates->filter(fn (Todo $todo) => str_contains(strtolower($todo->title), $needle))->values();
            if ($contains->count() === 1) {
                return ['status' => 'found', 'todo' => $contains->first()];
            }

            if ($contains->count() > 1) {
                return [
                    'status' => 'ambiguous',
                    'candidates' => $this->serializeCandidates($contains),
                ];
            }

            $best = null;
            $bestScore = 0.0;
            foreach ($candidates as $todo) {
                similar_text($needle, strtolower($todo->title), $percent);
                if ($percent > $bestScore) {
                    $bestScore = $percent;
                    $best = $todo;
                }
            }

            if ($best && $bestScore >= 55) {
                return ['status' => 'found', 'todo' => $best];
            }
        }

        return ['status' => 'missing'];
    }

    private function disambiguationResponse(string $intent, array $payload, array $candidates): array
    {
        $top = $candidates[0] ?? null;
        $suggestion = $top['reference_id'] ?? null;

        return [
            'success' => true,
            'action' => 'confirm_task',
            'pending_task' => [
                'intent' => $intent,
                'payload' => $payload,
                'candidates' => $candidates,
            ],
            'message' => $suggestion
                ? "I found multiple tasks. Did you mean {$suggestion}?"
                : 'I found multiple tasks. Which one did you mean?',
        ];
    }

    private function serializeCandidates(Collection $todos): array
    {
        return $todos->take(5)->map(fn (Todo $todo) => [
            'todo_id' => $todo->id,
            'title' => $todo->title,
            'reference_id' => $this->referenceIdForTodo($todo),
            'project_name' => $todo->project?->name,
        ])->values()->all();
    }

    private function resolveProjectGroup(Project $project, string $boardName): ?ProjectGroup
    {
        $needle = strtolower(trim($boardName));
        $groups = ProjectGroup::query()->where('project_id', $project->id)->get();

        $exact = $groups->first(fn (ProjectGroup $group) => strtolower($group->name) === $needle);
        if ($exact) {
            return $exact;
        }

        $partial = $groups->filter(function (ProjectGroup $group) use ($needle) {
            $name = strtolower($group->name);

            return str_contains($name, $needle) || str_contains($needle, $name);
        });

        if ($partial->count() === 1) {
            return $partial->first();
        }

        return null;
    }

    private function accessibleTodos(User $user): Collection
    {
        return Todo::query()
            ->with('project')
            ->whereNull('parent_task_id')
            ->where(function ($query) use ($user) {
                if ($user->company_id) {
                    $query->whereHas('user', fn ($q) => $q->where('company_id', $user->company_id));
                } else {
                    $query->where('user_id', $user->id);
                }
            })
            ->get();
    }

    private function resolveDefaultProject(User $user): Project
    {
        if ($user->company_id) {
            $project = Project::visibleForCompany($user->company_id)->first();
        } else {
            $project = Project::query()
                ->where('owner_id', $user->id)
                ->orderBy('created_at')
                ->first();
        }

        if (! $project || ! $project->canAccess($user->id)) {
            throw ValidationException::withMessages([
                'project_id' => ['Select a project on your dashboard before creating tasks by voice.'],
            ]);
        }

        return $project;
    }

    private function applyLocationGeocode(array $fields): array
    {
        if (empty($fields['location_query']) || ! $this->mapboxService->isConfigured()) {
            unset($fields['location_query']);

            return $fields;
        }

        $results = $this->mapboxService->geocode($fields['location_query']);
        $match = $results[0] ?? null;
        unset($fields['location_query']);

        if ($match) {
            $fields['location_name'] = $match['location_name'] ?? null;
            $fields['location_address'] = $match['location_address'] ?? null;
            $fields['latitude'] = $match['latitude'] ?? null;
            $fields['longitude'] = $match['longitude'] ?? null;
        }

        return $fields;
    }

    private function referenceIdForTodo(Todo $todo): string
    {
        $prefix = strtoupper($todo->project?->key ?? substr($todo->project?->name ?? 'TASK', 0, 4));

        return "{$prefix}-{$todo->id}";
    }

    private function buildUpdateMessage(Todo $todo, array $updates): string
    {
        $parts = ["Updated \"{$todo->title}\""];

        if (isset($updates['status'])) {
            $parts[] = 'status changed';
        }
        if (isset($updates['priority'])) {
            $parts[] = 'priority set to '.$updates['priority'];
        }
        if (isset($updates['due_date'])) {
            $parts[] = 'due date set';
        }
        if (isset($updates['location_query'])) {
            $parts[] = 'location added';
        }

        return implode(', ', $parts).'.';
    }

    private function resolveAccessibleProject(User $user, int $projectId): Project
    {
        $project = Project::find($projectId);

        if (! $project || ! $project->canAccess($user->id)) {
            throw ValidationException::withMessages([
                'project_id' => ['That project was not found or you do not have access to it.'],
            ]);
        }

        return $project;
    }
}
