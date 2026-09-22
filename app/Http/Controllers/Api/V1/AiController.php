<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Activity;
use App\Models\Project;
use App\Models\ProjectGroup;
use App\Models\Todo;
use App\Services\AiTaskCreationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Platform AI gateway for specialised apps (API key / Sanctum / session).
 * Preview → confirm; never writes unless confirm=true.
 */
class AiController extends PlatformController
{
    public function __construct(
        protected AiTaskCreationService $aiTaskCreationService,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required_without:confirm|string|max:4000',
            'context' => 'nullable|string|max:100',
            'project_id' => 'nullable|integer|exists:taskit_projects,id',
            'confirm' => 'sometimes|boolean',
            'task' => 'required_if:confirm,true|array',
            'task.title' => 'required_if:confirm,true|string|max:255',
            'task.assigned_to' => 'nullable|string|max:255',
            'task.asset_id' => 'nullable|integer|exists:taskit_operational_objects,id',
            'task.due_date' => 'nullable|date',
            'task.category' => 'nullable|string|max:100',
            'task.priority' => 'nullable|in:low,normal,high,urgent,Low,Medium,High,Critical',
            'task.description' => 'nullable|string',
            'task.project_id' => 'nullable|integer|exists:taskit_projects,id',
        ]);

        if ($validator->fails()) {
            return $this->fail('Validation failed.', 422, $validator->errors());
        }

        $user = $this->platformUser($request);
        $companyId = $this->companyId($request);

        if ($request->boolean('confirm')) {
            return $this->confirmCreate($request, $user, $companyId);
        }

        $proposal = $this->aiTaskCreationService->propose(
            $user,
            (string) $request->input('message'),
            (string) $request->input('context', 'app_task_creation'),
        );

        return $this->ok($proposal);
    }

    private function confirmCreate(Request $request, $user, int $companyId): JsonResponse
    {
        $task = $request->input('task', []);
        $projectId = $request->input('project_id') ?? ($task['project_id'] ?? null);

        $project = null;
        if ($projectId) {
            $project = Project::query()
                ->forCompany($companyId)
                ->where('id', (int) $projectId)
                ->first();
        }

        if (! $project) {
            $project = Project::query()
                ->forCompany($companyId)
                ->where('is_active', true)
                ->orderBy('viewing_order')
                ->first();
        }

        if (! $project) {
            return $this->fail('No project available to create the task in.', 422);
        }

        $priority = match (strtolower((string) ($task['priority'] ?? 'normal'))) {
            'low' => 'Low',
            'high' => 'High',
            'urgent', 'critical' => 'Critical',
            default => 'Medium',
        };

        $groupId = ProjectGroup::resolveIdForProject($project->id, null);

        $todo = Todo::create([
            'user_id' => $user->id,
            'company_id' => $companyId,
            'project_id' => $project->id,
            'project_group_id' => $groupId,
            'workspace_id' => $project->workspace_id,
            'operational_object_id' => $task['asset_id'] ?? null,
            'title' => $task['title'],
            'description' => $task['description'] ?? null,
            'assignee' => $task['assigned_to'] ?? null,
            'due_date' => $task['due_date'] ?? null,
            'category' => $task['category'] ?? null,
            'priority' => $priority,
            'type' => 'Task',
            'status' => 'todo',
            'source' => 'ai',
            'metadata' => [
                'ai_created' => true,
                'via' => 'platform_api',
                'original_message' => $request->input('message'),
            ],
        ]);

        Activity::createTodoActivity($todo, $user, 'todo_created');

        return $this->ok([
            'intent' => 'create_task',
            'message' => 'Task created.',
            'task' => [
                'id' => $todo->id,
                'title' => $todo->title,
                'assigned_to' => $todo->assignee,
                'asset_id' => $todo->operational_object_id,
                'due_date' => optional($todo->due_date)?->toDateString(),
                'category' => $todo->category,
                'priority' => $todo->priority,
                'status' => $todo->status,
                'project_id' => $todo->project_id,
            ],
        ], 'Task created.', 201);
    }
}
