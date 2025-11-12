<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Todo;
use App\Models\Notification;
use App\Models\Activity;
use App\Services\TodoWebSocketService;
use App\Services\AssignmentNotificationService;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TodoController extends Controller
{
    protected TodoWebSocketService $webSocketService;
    protected AssignmentNotificationService $assignmentNotificationService;

    public function __construct(TodoWebSocketService $webSocketService, AssignmentNotificationService $assignmentNotificationService)
    {
        $this->webSocketService = $webSocketService;
        $this->assignmentNotificationService = $assignmentNotificationService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // Build cache key based on filters
        $filters = [
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'project_id' => $request->get('project_id'),
            'status' => $request->get('status'),
            'priority' => $request->get('priority'),
            'type' => $request->get('type'),
            'assignee' => $request->get('assignee'),
            'search' => $request->get('search'),
            'page' => $request->get('page', 1),
            'per_page' => $request->get('per_page', 20)
        ];

        // Temporarily disable caching to debug the issue
        // $todos = CacheService::cacheUserTodos($user->id, $filters, function () use ($user, $request) {
        $todos = (function () use ($user, $request) {
            if ($user->company_id) {
                // Show all todos from the same company
                $query = Todo::forCompany($user->company_id)
                    ->with(['comments', 'attachments', 'project', 'subtasks.project', 'parentTask'])
                    ->whereNull('parent_task_id') // Only load parent tasks, subtasks will be loaded via the 'subtasks' relationship
                    ->orderBy('created_at', 'desc');
            } else {
                // Fallback to user's own todos if no company
                $query = Todo::forUser($user)
                    ->with(['comments', 'attachments', 'project', 'subtasks.project', 'parentTask'])
                    ->whereNull('parent_task_id') // Only load parent tasks, subtasks will be loaded via the 'subtasks' relationship
                    ->orderBy('created_at', 'desc');
            }

            // Filter by project if specified
            if ($request->filled('project_id')) {
                $query->forProject($request->project_id);
            }

            // Apply filters
            if ($request->filled('search')) {
                $query->search($request->search);
            }

            if ($request->filled('priority')) {
                $query->byPriority($request->priority);
            }

            if ($request->filled('type')) {
                $query->byType($request->type);
            }

            if ($request->filled('assignee')) {
                $query->byAssignee($request->assignee);
            }

            if ($request->filled('status')) {
                $query->byStatus($request->status);
            }

            if ($request->boolean('overdue')) {
                $query->overdue();
            }

            if ($request->boolean('due_today')) {
                $query->dueToday();
            }

            $parents = $query->get();

            // Explicitly fetch subtasks as a fallback to ensure they are included in the payload
            $subtasksQuery = Todo::query()
                ->when($user->company_id, function ($q) use ($user) {
                    $q->where('company_id', $user->company_id);
                })
                ->whereNotNull('parent_task_id')
                ->with(['project', 'parentTask']);

            if ($request->filled('project_id')) {
                $subtasksQuery->where('project_id', (int) $request->project_id);
            }

            // Apply the same optional filters to subtasks
            if ($request->filled('search')) {
                $subtasksQuery->where(function ($q) use ($request) {
                    $term = '%' . $request->search . '%';
                    $q->where('title', 'ILIKE', $term)
                      ->orWhere('description', 'ILIKE', $term);
                });
            }
            if ($request->filled('priority')) {
                $subtasksQuery->where('priority', $request->priority);
            }
            if ($request->filled('type')) {
                $subtasksQuery->where('type', $request->type);
            }
            if ($request->filled('assignee')) {
                $subtasksQuery->where('assignee', $request->assignee);
            }
            if ($request->filled('status')) {
                $subtasksQuery->where('status', $request->status);
            }

            $explicitSubtasks = $subtasksQuery->get();

            return $parents->concat($explicitSubtasks);
        })();

        // Determine which todos are newly assigned to the current user (and not created by them)
        // Use Postgres JSON extraction to safely pluck todo IDs from notification data
        $unreadAssignmentTodoIds = Notification::query()
            ->where('user_id', $user->id)
            ->where('type', 'assignment')
            ->where('is_read', false)
            ->selectRaw("(data->>'todo_id')::int AS todo_id")
            ->pluck('todo_id')
            ->filter()
            ->map(fn ($id) => (int)$id)
            ->toArray();

        $todos->each(function ($todo) use ($unreadAssignmentTodoIds, $user) {
            $isNew = in_array((int)$todo->id, $unreadAssignmentTodoIds, true) && (int)$todo->user_id !== (int)$user->id;
            $todo->setAttribute('is_new_assigned', $isNew);
        });

        // Collect all subtasks from parent tasks (via eager load) and merge with explicit subtasks already included
        $allSubtasks = collect();
        $todos->each(function ($todo) use ($allSubtasks) {
            if ($todo->relationLoaded('subtasks') && $todo->subtasks) {
                $allSubtasks = $allSubtasks->merge($todo->subtasks);
            }
        });

        // Combine parent tasks and subtasks
        // Merge and ensure uniqueness by ID to avoid duplicates
        $allTodos = $todos->merge($allSubtasks)->unique('id')->values();

        // Group by status for Kanban board
        $grouped = [
            'todo' => $allTodos->where('status', 'todo')->values(),
            'in-progress' => $allTodos->where('status', 'in-progress')->values(),
            'qa-testing' => $allTodos->where('status', 'qa-testing')->values(),
            'done' => $allTodos->where('status', 'done')->values(),
        ];

        // Debug logging to see what data is being returned
        \Log::info('API returning todos', [
            'parent_todo_count' => $todos->whereNull('parent_task_id')->count(),
            'subtask_count' => $todos->whereNotNull('parent_task_id')->count() + $allSubtasks->count(),
            'total_todo_count' => $allTodos->count(),
            'todo_priorities' => $todos->take(5)->map(function($todo) {
                return ['id' => $todo->id, 'priority' => $todo->priority];
            })->toArray()
        ]);

        return response()->json([
            'success' => true,
            'data' => $grouped,
            'stats' => [
                'total' => $todos->count(),
                'todo' => $todos->where('status', 'todo')->count(),
                'in-progress' => $todos->where('status', 'in-progress')->count(),
                'qa-testing' => $todos->where('status', 'qa-testing')->count(),
                'done' => $todos->where('status', 'done')->count(),
                'overdue' => $todos->filter(fn($todo) => $todo->is_overdue)->count(),
                'due_today' => $todos->filter(fn($todo) => $todo->due_date?->isToday())->count(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|exists:taskit_projects,id',
            'parent_task_id' => 'nullable|exists:taskit_todos,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:Low,Medium,High,Critical',
            'type' => 'nullable|in:Bug,Feature,Task,Story,Epic',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'assignee' => 'nullable|string|max:255',
            'due_date' => 'nullable|date|after:today',
            'story_points' => 'nullable|integer|min:1|max:21',
            'status' => 'required|in:todo,in-progress,qa-testing,done',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $todo = Todo::create([
            'user_id' => Auth::id(),
            'project_id' => $request->project_id,
            'parent_task_id' => $request->parent_task_id,
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->priority,
            'type' => $request->type,
            'tags' => $request->tags,
            'assignee' => $request->assignee,
            'due_date' => $request->due_date,
            'company_id' => Auth::user()->company_id,
            'story_points' => $request->story_points,
            'status' => $request->status,
        ]);

        // Send to n8n webhook
        Http::post('https://n8n.neurohub.uk/webhook/new-task', [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'priority' => $task->priority,
            'created_at' => $task->created_at,
        ]);

        $todo->load(['comments', 'attachments', 'project', 'subtasks.project', 'parentTask']);

        // Invalidate caches
        CacheService::invalidateUserCaches(Auth::id());
        if ($todo->project_id) {
            CacheService::invalidateProjectCaches($todo->project_id);
        }

        // Send real-time notification
        $this->webSocketService->todoCreated($todo);

        // Send assignment notification if todo is assigned to someone
        $this->assignmentNotificationService->sendNewTodoAssignmentNotification($todo);

        // Log activity
        Activity::createTodoActivity($todo, Auth::user(), 'todo_created');

        return response()->json([
            'success' => true,
            'message' => 'Todo created successfully',
            'data' => $todo
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Todo $todo): JsonResponse
    {
        $user = Auth::user();
        
        if (!$todo->canAccess($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $todo->load(['comments.user', 'attachments', 'project', 'subtasks.project', 'parentTask']);

        return response()->json([
            'success' => true,
            'data' => $todo
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Todo $todo): JsonResponse
    {
        $user = Auth::user();
        
        if (!$todo->canAccess($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'sometimes|required|in:Low,Medium,High,Critical',
            'type' => 'nullable|in:Bug,Feature,Task,Story,Epic',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'assignee' => 'nullable|string|max:255',
            'due_date' => 'nullable|date',
            'story_points' => 'nullable|integer|min:1|max:21',
            'status' => 'sometimes|required|in:todo,in-progress,qa-testing,done',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Store old assignee before updating
        $oldAssignee = $todo->assignee;

        $todo->update($request->only([
            'title', 'description', 'priority', 'type', 'tags',
            'assignee', 'due_date', 'story_points', 'status'
        ]));

        $todo->load(['comments', 'attachments', 'project', 'subtasks.project', 'parentTask']);

        // Send real-time notification
        $this->webSocketService->todoUpdated($todo);

        // Send assignment notification if assignee has changed
        $this->assignmentNotificationService->sendAssignmentNotification($todo, $oldAssignee);

        // Log activity
        Activity::createTodoActivity($todo, Auth::user(), 'todo_updated');

        return response()->json([
            'success' => true,
            'message' => 'Todo updated successfully',
            'data' => $todo
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Todo $todo): JsonResponse
    {
        $user = Auth::user();
        
        if (!$todo->canAccess($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $todoTitle = $todo->title;
        $userId = $todo->user_id;
        
        // Log activity before deletion
        Activity::createTodoActivity($todo, Auth::user(), 'todo_deleted');
        
        $todo->delete();

        // Send real-time notification
        $this->webSocketService->todoDeleted($userId, $todoTitle);

        return response()->json([
            'success' => true,
            'message' => 'Todo deleted successfully'
        ]);
    }

    /**
     * Update todo order within a status column
     */
    public function updateOrder(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $request->validate([
            'todo_orders' => 'required|array',
            'todo_orders.*.id' => 'required|integer|exists:taskit_todos,id',
            'todo_orders.*.order' => 'required|integer|min:1',
        ]);

        foreach ($request->todo_orders as $todoOrder) {
            $todo = Todo::find($todoOrder['id']);
            
            // Check if user can access this todo
            if (!$todo->canAccess($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to reorder this todo'
                ], 403);
            }
            
            $todo->update(['order' => $todoOrder['order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Todo order updated successfully'
        ]);
    }

    /**
     * Mark assignment notifications for a todo as seen by the current user
     */
    public function markAssignmentSeen(Todo $todo): JsonResponse
    {
        $user = Auth::user();

        if (!$todo->canAccess($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Mark unread assignment notifications for this todo as read
        Notification::where('user_id', $user->id)
            ->where('type', 'assignment')
            ->where('is_read', false)
            ->whereRaw("(data->>'todo_id') = ?", [(string)$todo->id])
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Assignment marked as seen'
        ]);
    }

    /**
     * Update todo status (for drag & drop)
     */
    public function updateStatus(Request $request, Todo $todo): JsonResponse
    {
        $user = Auth::user();
        
        if (!$todo->canAccess($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:todo,in-progress,qa-testing,done',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $oldStatus = $todo->status;
        $todo->update(['status' => $request->status]);

        // Load relationships to ensure complete data is returned
        $todo->load(['comments', 'attachments', 'project', 'subtasks.project', 'parentTask']);

        // Send real-time notification
        $this->webSocketService->todoStatusChanged($todo, $oldStatus);

        return response()->json([
            'success' => true,
            'message' => 'Todo status updated successfully',
            'data' => $todo
        ]);
    }

    /**
     * Get unique assignees for filtering
     */
    public function assignees(): JsonResponse
    {
        $user = Auth::user();
        
        $assignees = Todo::forUser($user)
            ->whereNotNull('assignee')
            ->distinct()
            ->pluck('assignee')
            ->filter()
            ->values();

        return response()->json([
            'success' => true,
            'data' => $assignees
        ]);
    }

    /**
     * Create a subtask for an existing todo
     */
    public function createSubtask(Request $request, Todo $todo): JsonResponse
    {
        $user = Auth::user();
        
        if (!$todo->canAccess($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'nullable|in:Low,Medium,High,Critical',
            'type' => 'nullable|in:Bug,Feature,Task,Story,Epic',
            'assignee' => 'nullable|string|max:255',
            'due_date' => 'nullable|date|after:today',
            'story_points' => 'nullable|integer|min:1|max:21',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $subtask = Todo::create([
            'user_id' => Auth::id(),
            'project_id' => $todo->project_id,
            'parent_task_id' => $todo->id,
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->priority ?? $todo->priority, // Inherit parent priority if not specified
            'type' => $request->type,
            'tags' => [],
            'assignee' => $request->assignee ?? $todo->assignee, // Inherit parent assignee if not specified
            'due_date' => $request->due_date,
            'company_id' => Auth::user()->company_id,
            'story_points' => $request->story_points,
            'status' => 'todo', // Subtasks always start as 'todo'
        ]);

        $subtask->load(['comments', 'attachments', 'project', 'subtasks.project', 'parentTask']);

        // Send real-time notification
        $this->webSocketService->todoCreated($subtask);

        // Send assignment notification if subtask is assigned to someone
        $this->assignmentNotificationService->sendNewTodoAssignmentNotification($subtask);

        return response()->json([
            'success' => true,
            'message' => 'Subtask created successfully',
            'data' => $subtask
        ], 201);
    }

    /**
     * Bulk update status for multiple todos
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'todo_ids' => 'required|array|min:1',
            'todo_ids.*' => 'integer|exists:taskit_todos,id',
            'status' => 'required|string|in:todo,in-progress,qa-testing,done'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $todoIds = $request->input('todo_ids');
        $status = $request->input('status');

        // Verify all todos belong to user's company
        $todos = Todo::whereIn('id', $todoIds)
            ->where('company_id', $user->company_id)
            ->get();

        if ($todos->count() !== count($todoIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Some todos not found or not accessible'
            ], 403);
        }

        // Update all todos
        Todo::whereIn('id', $todoIds)->update(['status' => $status]);

        // Invalidate caches
        CacheService::invalidateUserCaches($user->id);
        foreach ($todos as $todo) {
            CacheService::invalidateProjectCaches($todo->project_id);
        }

        // Log bulk activity
        Activity::create([
            'type' => 'bulk_status_update',
            'actor_id' => $user->id,
            'actor_name' => $user->name,
            'actor_email' => $user->email,
            'target_id' => null,
            'target_name' => count($todoIds) . ' todos',
            'description' => "{$user->name} updated status to {$status} for " . count($todoIds) . ' todos',
            'metadata' => ['todo_ids' => $todoIds, 'status' => $status],
            'project_id' => null,
            'company_id' => $user->company_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully for ' . count($todoIds) . ' todos'
        ]);
    }

    /**
     * Bulk update priority for multiple todos
     */
    public function bulkUpdatePriority(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'todo_ids' => 'required|array|min:1',
            'todo_ids.*' => 'integer|exists:taskit_todos,id',
            'priority' => 'required|string|in:Low,Medium,High,Critical'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $todoIds = $request->input('todo_ids');
        $priority = $request->input('priority');

        // Verify all todos belong to user's company
        $todos = Todo::whereIn('id', $todoIds)
            ->where('company_id', $user->company_id)
            ->get();

        if ($todos->count() !== count($todoIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Some todos not found or not accessible'
            ], 403);
        }

        // Update all todos - use withoutGlobalScopes to ensure no interference
        Todo::withoutGlobalScopes()
            ->whereIn('id', $todoIds)
            ->update(['priority' => $priority]);

        // Invalidate caches
        CacheService::invalidateUserCaches($user->id);
        foreach ($todos as $todo) {
            CacheService::invalidateProjectCaches($todo->project_id);
        }

        // Log bulk activity
        Activity::create([
            'type' => 'bulk_priority_update',
            'actor_id' => $user->id,
            'actor_name' => $user->name,
            'actor_email' => $user->email,
            'target_id' => null,
            'target_name' => count($todoIds) . ' todos',
            'description' => "{$user->name} updated priority to {$priority} for " . count($todoIds) . ' todos',
            'metadata' => ['todo_ids' => $todoIds, 'priority' => $priority],
            'project_id' => null,
            'company_id' => $user->company_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Priority updated successfully for ' . count($todoIds) . ' todos'
        ]);
    }

    /**
     * Bulk update assignee for multiple todos
     */
    public function bulkUpdateAssignee(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'todo_ids' => 'required|array|min:1',
            'todo_ids.*' => 'integer|exists:taskit_todos,id',
            'assignee' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $todoIds = $request->input('todo_ids');
        $assignee = $request->input('assignee');

        // Verify all todos belong to user's company
        $todos = Todo::whereIn('id', $todoIds)
            ->where('company_id', $user->company_id)
            ->get();

        if ($todos->count() !== count($todoIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Some todos not found or not accessible'
            ], 403);
        }

        // Update all todos
        Todo::whereIn('id', $todoIds)->update(['assignee' => $assignee]);

        // Invalidate caches
        CacheService::invalidateUserCaches($user->id);
        foreach ($todos as $todo) {
            CacheService::invalidateProjectCaches($todo->project_id);
        }

        // Log bulk activity
        Activity::create([
            'type' => 'bulk_assignee_update',
            'actor_id' => $user->id,
            'actor_name' => $user->name,
            'actor_email' => $user->email,
            'target_id' => null,
            'target_name' => count($todoIds) . ' todos',
            'description' => "{$user->name} updated assignee to {$assignee} for " . count($todoIds) . ' todos',
            'metadata' => ['todo_ids' => $todoIds, 'assignee' => $assignee],
            'project_id' => null,
            'company_id' => $user->company_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Assignee updated successfully for ' . count($todoIds) . ' todos'
        ]);
    }

    /**
     * Bulk update type for multiple todos
     */
    public function bulkUpdateType(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'todo_ids' => 'required|array|min:1',
            'todo_ids.*' => 'integer|exists:taskit_todos,id',
            'type' => 'required|string|in:Bug,Feature,Task,Story,Epic'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $todoIds = $request->input('todo_ids');
        $type = $request->input('type');

        // Verify all todos belong to user's company
        $todos = Todo::whereIn('id', $todoIds)
            ->where('company_id', $user->company_id)
            ->get();

        if ($todos->count() !== count($todoIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Some todos not found or not accessible'
            ], 403);
        }

        // Update all todos
        Todo::whereIn('id', $todoIds)->update(['type' => $type]);

        // Invalidate caches
        CacheService::invalidateUserCaches($user->id);
        foreach ($todos as $todo) {
            CacheService::invalidateProjectCaches($todo->project_id);
        }

        // Log bulk activity
        Activity::create([
            'type' => 'bulk_type_update',
            'actor_id' => $user->id,
            'actor_name' => $user->name,
            'actor_email' => $user->email,
            'target_id' => null,
            'target_name' => count($todoIds) . ' todos',
            'description' => "{$user->name} updated type to {$type} for " . count($todoIds) . ' todos',
            'metadata' => ['todo_ids' => $todoIds, 'type' => $type],
            'project_id' => null,
            'company_id' => $user->company_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Type updated successfully for ' . count($todoIds) . ' todos'
        ]);
    }

    /**
     * Bulk update due date for multiple todos
     */
    public function bulkUpdateDueDate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'todo_ids' => 'required|array|min:1',
            'todo_ids.*' => 'integer|exists:taskit_todos,id',
            'due_date' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $todoIds = $request->input('todo_ids');
        $dueDate = $request->input('due_date');

        // Verify all todos belong to user's company
        $todos = Todo::whereIn('id', $todoIds)
            ->where('company_id', $user->company_id)
            ->get();

        if ($todos->count() !== count($todoIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Some todos not found or not accessible'
            ], 403);
        }

        // Update all todos
        Todo::whereIn('id', $todoIds)->update(['due_date' => $dueDate]);

        // Invalidate caches
        CacheService::invalidateUserCaches($user->id);
        foreach ($todos as $todo) {
            CacheService::invalidateProjectCaches($todo->project_id);
        }

        // Log bulk activity
        Activity::create([
            'type' => 'bulk_due_date_update',
            'actor_id' => $user->id,
            'actor_name' => $user->name,
            'actor_email' => $user->email,
            'target_id' => null,
            'target_name' => count($todoIds) . ' todos',
            'description' => "{$user->name} updated due date to {$dueDate} for " . count($todoIds) . ' todos',
            'metadata' => ['todo_ids' => $todoIds, 'due_date' => $dueDate],
            'project_id' => null,
            'company_id' => $user->company_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Due date updated successfully for ' . count($todoIds) . ' todos'
        ]);
    }

    /**
     * Bulk update tags for multiple todos
     */
    public function bulkUpdateTags(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'todo_ids' => 'required|array|min:1',
            'todo_ids.*' => 'integer|exists:taskit_todos,id',
            'tags' => 'required|array',
            'tags.*' => 'string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $todoIds = $request->input('todo_ids');
        $tags = $request->input('tags');

        // Verify all todos belong to user's company
        $todos = Todo::whereIn('id', $todoIds)
            ->where('company_id', $user->company_id)
            ->get();

        if ($todos->count() !== count($todoIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Some todos not found or not accessible'
            ], 403);
        }

        // Update all todos
        Todo::whereIn('id', $todoIds)->update(['tags' => json_encode($tags)]);

        // Invalidate caches
        CacheService::invalidateUserCaches($user->id);
        foreach ($todos as $todo) {
            CacheService::invalidateProjectCaches($todo->project_id);
        }

        // Log bulk activity
        Activity::create([
            'type' => 'bulk_tags_update',
            'actor_id' => $user->id,
            'actor_name' => $user->name,
            'actor_email' => $user->email,
            'target_id' => null,
            'target_name' => count($todoIds) . ' todos',
            'description' => "{$user->name} updated tags for " . count($todoIds) . ' todos',
            'metadata' => ['todo_ids' => $todoIds, 'tags' => $tags],
            'project_id' => null,
            'company_id' => $user->company_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tags updated successfully for ' . count($todoIds) . ' todos'
        ]);
    }

    /**
     * Bulk delete multiple todos
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'todo_ids' => 'required|array|min:1',
            'todo_ids.*' => 'integer|exists:taskit_todos,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $todoIds = $request->input('todo_ids');

        // Verify all todos belong to user's company
        $todos = Todo::whereIn('id', $todoIds)
            ->where('company_id', $user->company_id)
            ->get();

        if ($todos->count() !== count($todoIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Some todos not found or not accessible'
            ], 403);
        }

        // Log bulk activity before deletion
        Activity::create([
            'type' => 'bulk_delete',
            'actor_id' => $user->id,
            'actor_name' => $user->name,
            'actor_email' => $user->email,
            'target_id' => null,
            'target_name' => count($todoIds) . ' todos',
            'description' => "{$user->name} deleted " . count($todoIds) . ' todos',
            'metadata' => ['todo_ids' => $todoIds],
            'project_id' => null,
            'company_id' => $user->company_id,
        ]);

        // Delete all todos
        Todo::whereIn('id', $todoIds)->delete();

        // Invalidate caches
        CacheService::invalidateUserCaches($user->id);
        foreach ($todos as $todo) {
            CacheService::invalidateProjectCaches($todo->project_id);
        }

        return response()->json([
            'success' => true,
            'message' => 'Successfully deleted ' . count($todoIds) . ' todos'
        ]);
    }
}
