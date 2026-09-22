<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Activity;
use App\Models\OperationalObject;
use App\Models\Project;
use App\Models\ProjectGroup;
use App\Models\Todo;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaskController extends PlatformController
{
    public function index(Request $request): JsonResponse
    {
        $companyId = $this->companyId($request);

        $query = Todo::query()
            ->forCompany($companyId)
            ->with(['comments', 'attachments', 'checklistItems', 'asset', 'workspace', 'project'])
            ->whereNull('parent_task_id')
            ->orderByDesc('created_at');

        if ($request->filled('workspace_id')) {
            $query->where('workspace_id', (int) $request->workspace_id);
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', (int) $request->project_id);
        }

        if ($request->filled('asset_id')) {
            $query->where('operational_object_id', (int) $request->asset_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assignee', $request->assigned_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        $perPage = min((int) $request->get('per_page', 25), 100);
        $paginator = $query->paginate($perPage);

        return $this->ok([
            'tasks' => collect($paginator->items())->map(fn (Todo $todo) => $this->transform($todo)),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $todo = $this->findCompanyTodo($request, $id);

        if (! $todo) {
            return $this->fail('Task not found.', 404);
        }

        $todo->load(['comments.user', 'attachments', 'checklistItems', 'asset', 'workspace', 'project', 'subtasks']);

        return $this->ok($this->transform($todo, true));
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->platformUser($request);
        $companyId = $this->companyId($request);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'project_id' => 'nullable|integer|exists:taskit_projects,id',
            'workspace_id' => 'nullable|integer|exists:taskit_workspaces,id',
            'asset_id' => 'nullable|integer|exists:taskit_operational_objects,id',
            'assigned_to' => 'nullable|string|max:255',
            'status' => 'nullable|in:todo,in-progress,qa-testing,done,waiting,blocked,cancelled,completed',
            'priority' => 'nullable|in:Low,Medium,High,Critical,low,normal,high,urgent',
            'category' => 'nullable|string|max:100',
            'due_date' => 'nullable|date',
            'start_date' => 'nullable|date',
            'source' => 'nullable|string|max:50',
            'source_reference' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
            'recurrence' => 'nullable|array',
            'checklist' => 'nullable|array',
            'checklist.*.title' => 'required_with:checklist|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->fail('Validation failed.', 422, $validator->errors());
        }

        $project = $this->resolveProject($request, $companyId, $user->id);
        if (! $project) {
            return $this->fail('A valid project in your company is required to create tasks.', 422);
        }

        $workspaceId = $this->resolveWorkspaceId($request, $companyId, $project);
        $assetId = $this->resolveAssetId($request, $companyId);

        if ($request->filled('asset_id') && $assetId === null) {
            return $this->fail('Asset not found in your company.', 404);
        }

        $status = $this->normalizeStatus($request->input('status', 'todo'));
        $priority = $this->normalizePriority($request->input('priority', 'Medium'));

        $description = $request->input('description');
        if ($request->filled('instructions')) {
            $description = trim(($description ? $description."\n\n" : '').'Instructions: '.$request->input('instructions'));
        }

        $metadata = $request->input('metadata', []);
        if ($request->filled('source_reference')) {
            $metadata['source_reference'] = $request->input('source_reference');
        }
        if ($request->filled('start_date')) {
            $metadata['start_date'] = $request->input('start_date');
        }

        $groupId = ProjectGroup::resolveIdForProject($project->id, null);

        $todo = Todo::create([
            'user_id' => $user->id,
            'company_id' => $companyId,
            'project_id' => $project->id,
            'project_group_id' => $groupId,
            'workspace_id' => $workspaceId,
            'operational_object_id' => $assetId,
            'title' => $request->input('title'),
            'description' => $description,
            'priority' => $priority,
            'type' => 'Task',
            'category' => $request->input('category'),
            'assignee' => $request->input('assigned_to'),
            'due_date' => $request->input('due_date'),
            'status' => $status,
            'source' => $request->input('source', 'api'),
            'metadata' => $metadata ?: null,
            'recurrence' => $request->input('recurrence'),
            'completed_at' => $status === 'done' ? now() : null,
        ]);

        if ($request->filled('checklist') && is_array($request->checklist)) {
            foreach (array_values($request->checklist) as $index => $item) {
                $todo->checklistItems()->create([
                    'title' => $item['title'],
                    'position' => $index,
                    'completed' => (bool) ($item['completed'] ?? false),
                ]);
            }
        }

        Activity::createTodoActivity($todo, $user, 'todo_created');

        $todo->load(['checklistItems', 'asset', 'workspace', 'project']);

        return $this->ok($this->transform($todo, true), 'Task created.', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $todo = $this->findCompanyTodo($request, $id);
        if (! $todo) {
            return $this->fail('Task not found.', 404);
        }

        $user = $this->platformUser($request);
        $companyId = $this->companyId($request);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'workspace_id' => 'nullable|integer|exists:taskit_workspaces,id',
            'asset_id' => 'nullable|integer|exists:taskit_operational_objects,id',
            'assigned_to' => 'nullable|string|max:255',
            'status' => 'sometimes|in:todo,in-progress,qa-testing,done,waiting,blocked,cancelled,completed',
            'priority' => 'sometimes|in:Low,Medium,High,Critical,low,normal,high,urgent',
            'category' => 'nullable|string|max:100',
            'due_date' => 'nullable|date',
            'metadata' => 'nullable|array',
            'recurrence' => 'nullable|array',
            'source' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return $this->fail('Validation failed.', 422, $validator->errors());
        }

        $data = [];

        if ($request->has('title')) {
            $data['title'] = $request->input('title');
        }
        if ($request->has('description')) {
            $data['description'] = $request->input('description');
        }
        if ($request->has('assigned_to')) {
            $data['assignee'] = $request->input('assigned_to');
        }
        if ($request->has('category')) {
            $data['category'] = $request->input('category');
        }
        if ($request->has('due_date')) {
            $data['due_date'] = $request->input('due_date');
        }
        if ($request->has('metadata')) {
            $data['metadata'] = $request->input('metadata');
        }
        if ($request->has('recurrence')) {
            $data['recurrence'] = $request->input('recurrence');
        }
        if ($request->has('source')) {
            $data['source'] = $request->input('source');
        }
        if ($request->has('priority')) {
            $data['priority'] = $this->normalizePriority($request->input('priority'));
        }
        if ($request->has('status')) {
            $status = $this->normalizeStatus($request->input('status'));
            $data['status'] = $status;
            $data['completed_at'] = $status === 'done' ? ($todo->completed_at ?? now()) : null;
        }
        if ($request->has('workspace_id')) {
            $workspaceId = $this->resolveWorkspaceId($request, $companyId, null);
            if ($request->filled('workspace_id') && $workspaceId === null) {
                return $this->fail('Workspace not found in your company.', 404);
            }
            $data['workspace_id'] = $workspaceId;
        }
        if ($request->has('asset_id')) {
            $assetId = $this->resolveAssetId($request, $companyId);
            if ($request->filled('asset_id') && $assetId === null) {
                return $this->fail('Asset not found in your company.', 404);
            }
            $data['operational_object_id'] = $assetId;
        }

        $todo->update($data);
        Activity::createTodoActivity($todo, $user, 'todo_updated', $data);
        $todo->load(['checklistItems', 'asset', 'workspace', 'project']);

        return $this->ok($this->transform($todo, true), 'Task updated.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $todo = $this->findCompanyTodo($request, $id);
        if (! $todo) {
            return $this->fail('Task not found.', 404);
        }

        $user = $this->platformUser($request);
        Activity::createTodoActivity($todo, $user, 'todo_deleted');
        $todo->delete();

        return $this->ok(null, 'Task deleted.');
    }

    private function findCompanyTodo(Request $request, int $id): ?Todo
    {
        return Todo::query()
            ->forCompany($this->companyId($request))
            ->where('id', $id)
            ->first();
    }

    private function resolveProject(Request $request, int $companyId, int $userId): ?Project
    {
        if ($request->filled('project_id')) {
            return Project::query()
                ->forCompany($companyId)
                ->where('id', (int) $request->project_id)
                ->where('is_active', true)
                ->first();
        }

        return Project::query()
            ->forCompany($companyId)
            ->where('is_active', true)
            ->orderBy('viewing_order')
            ->first()
            ?? Project::query()
                ->where('owner_id', $userId)
                ->where('is_active', true)
                ->orderBy('viewing_order')
                ->first();
    }

    private function resolveWorkspaceId(Request $request, int $companyId, ?Project $project): ?int
    {
        if ($request->filled('workspace_id')) {
            $workspace = Workspace::query()
                ->forCompany($companyId)
                ->where('id', (int) $request->workspace_id)
                ->first();

            return $workspace?->id;
        }

        if ($project?->workspace_id) {
            return $project->workspace_id;
        }

        return null;
    }

    private function resolveAssetId(Request $request, int $companyId): ?int
    {
        if (! $request->filled('asset_id')) {
            return null;
        }

        $asset = OperationalObject::query()
            ->forCompany($companyId)
            ->where('id', (int) $request->asset_id)
            ->first();

        return $asset?->id;
    }

    private function normalizeStatus(string $status): string
    {
        return match (strtolower($status)) {
            'completed', 'done' => 'done',
            'in_progress', 'in-progress' => 'in-progress',
            'qa-testing', 'waiting' => 'qa-testing',
            'blocked', 'cancelled' => 'todo',
            default => 'todo',
        };
    }

    private function normalizePriority(string $priority): string
    {
        return match (strtolower($priority)) {
            'low' => 'Low',
            'normal', 'medium' => 'Medium',
            'high' => 'High',
            'urgent', 'critical' => 'Critical',
            default => in_array($priority, ['Low', 'Medium', 'High', 'Critical'], true)
                ? $priority
                : 'Medium',
        };
    }

    private function transform(Todo $todo, bool $detailed = false): array
    {
        $data = [
            'id' => $todo->id,
            'company_id' => $todo->company_id,
            'workspace_id' => $todo->workspace_id,
            'project_id' => $todo->project_id,
            'asset_id' => $todo->operational_object_id,
            'created_by' => $todo->user_id,
            'assigned_to' => $todo->assignee,
            'title' => $todo->title,
            'description' => $todo->description,
            'status' => $todo->status,
            'priority' => $todo->priority,
            'category' => $todo->category,
            'due_date' => optional($todo->due_date)?->toDateString(),
            'completed_at' => optional($todo->completed_at)?->toIso8601String(),
            'source' => $todo->source,
            'metadata' => $todo->metadata,
            'recurrence' => $todo->recurrence,
            'created_at' => optional($todo->created_at)?->toIso8601String(),
            'updated_at' => optional($todo->updated_at)?->toIso8601String(),
        ];

        if ($detailed || $todo->relationLoaded('checklistItems')) {
            $data['checklist'] = ($todo->checklistItems ?? collect())->map(fn ($item) => [
                'id' => $item->id,
                'title' => $item->title,
                'completed' => $item->completed,
                'position' => $item->position,
                'completed_at' => optional($item->completed_at)?->toIso8601String(),
                'completed_by' => $item->completed_by,
            ])->values();
        }

        if ($todo->relationLoaded('asset') && $todo->asset) {
            $data['asset'] = [
                'id' => $todo->asset->id,
                'type' => $todo->asset->type,
                'name' => $todo->asset->name,
                'reference' => $todo->asset->reference,
            ];
        }

        if ($todo->relationLoaded('workspace') && $todo->workspace) {
            $data['workspace'] = [
                'id' => $todo->workspace->id,
                'name' => $todo->workspace->name,
                'type' => $todo->workspace->type,
            ];
        }

        return $data;
    }
}
