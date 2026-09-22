<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Project;
use App\Models\ProjectGroup;
use App\Models\Todo;
use App\Services\AiTaskCreationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AiController extends Controller
{
    public function __construct(
        protected AiTaskCreationService $aiTaskCreationService,
    ) {}

    /**
     * POST /api/ai — structured AI gateway.
     * Returns proposals only; does not mutate data unless confirm=true with a validated payload.
     */
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
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();

        if ($request->boolean('confirm')) {
            return $this->confirmCreate($request, $user);
        }

        $proposal = $this->aiTaskCreationService->propose(
            $user,
            (string) $request->input('message'),
            (string) $request->input('context', 'task_creation'),
        );

        return response()->json([
            'success' => true,
            ...$proposal,
        ]);
    }

    private function confirmCreate(Request $request, $user): JsonResponse
    {
        $task = $request->input('task', []);
        $projectId = $request->input('project_id') ?? ($task['project_id'] ?? null);

        $project = null;
        if ($projectId) {
            $project = Project::query()
                ->when($user->company_id, fn ($q) => $q->forCompany($user->company_id))
                ->where('id', (int) $projectId)
                ->first();
        }

        if (! $project) {
            $project = Project::query()
                ->when($user->company_id, fn ($q) => $q->forCompany($user->company_id))
                ->where('is_active', true)
                ->orderBy('viewing_order')
                ->first();
        }

        if (! $project) {
            return response()->json([
                'success' => false,
                'message' => 'No project available to create the task in.',
            ], 422);
        }

        if ($user->company_id && (int) $project->company_id !== (int) $user->company_id
            && (int) optional($project->owner)->company_id !== (int) $user->company_id) {
            // forCompany scope already applied when company_id set; double-check ownership
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
            'company_id' => $user->company_id,
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
                'original_message' => $request->input('message'),
            ],
        ]);

        Activity::createTodoActivity($todo, $user, 'todo_created');

        return response()->json([
            'success' => true,
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
            ],
        ], 201);
    }
}
