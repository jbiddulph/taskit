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
    ) {}

    public function process(
        User $user,
        string $transcript,
        ?int $projectId = null,
        bool $confirmDelete = false,
        ?int $deleteTodoId = null,
    ): array {
        if ($confirmDelete && $deleteTodoId) {
            return $this->executeConfirmedDelete($user, $deleteTodoId);
        }

        $project = $projectId ? $this->resolveAccessibleProject($user, $projectId) : null;
        $parsed = $this->parser->parse($transcript);
        $intent = $parsed['intent'] ?? 'unknown';

        return match ($intent) {
            'delete' => $this->handleDelete($user, $project, $parsed),
            'update' => $this->handleUpdate($user, $project, $parsed),
            'create' => $this->handleCreate($user, $project, $parsed),
            default => [
                'success' => false,
                'message' => $parsed['message'] ?? 'Sorry, I could not understand that command.',
                'transcript' => $transcript,
            ],
        };
    }

    private function handleDelete(User $user, ?Project $project, array $parsed): array
    {
        $todo = $this->resolveTodo($user, $project, $parsed['match'] ?? []);

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

    private function handleUpdate(User $user, ?Project $project, array $parsed): array
    {
        $todo = $this->resolveTodo($user, $project, $parsed['match'] ?? []);

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

    private function resolveTodo(User $user, ?Project $project, array $match): ?Todo
    {
        $accessible = $this->accessibleTodos($user);

        if (! empty($match['todo_id'])) {
            $todo = $accessible->firstWhere('id', (int) $match['todo_id']);
            if ($todo) {
                return $todo;
            }
        }

        if (! empty($match['reference_id']) && preg_match('/-(\d+)$/', $match['reference_id'], $m)) {
            $todo = $accessible->firstWhere('id', (int) $m[1]);
            if ($todo) {
                return $todo;
            }
        }

        $candidates = $project
            ? $accessible->where('project_id', $project->id)
            : $accessible;

        if (! empty($match['title'])) {
            $needle = strtolower($match['title']);
            $exact = $candidates->first(fn (Todo $todo) => strtolower($todo->title) === $needle);
            if ($exact) {
                return $exact;
            }

            $contains = $candidates->filter(fn (Todo $todo) => str_contains(strtolower($todo->title), $needle));
            if ($contains->count() === 1) {
                return $contains->first();
            }

            if ($contains->count() > 1) {
                return null;
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
                return $best;
            }
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
