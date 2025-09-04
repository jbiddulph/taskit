<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Todo;
use App\Models\Notification;
use App\Services\TodoWebSocketService;
use App\Services\AssignmentNotificationService;
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
        
        if ($user->company_id) {
            // Show all todos from the same company
            $query = Todo::forCompany($user->company_id)
                ->with(['comments', 'attachments', 'project'])
                ->orderBy('created_at', 'desc');
        } else {
            // Fallback to user's own todos if no company
            $query = Todo::forUser($user)
                ->with(['comments', 'attachments', 'project'])
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

        $todos = $query->get();

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

        // Group by status for Kanban board
        $grouped = [
            'todo' => $todos->where('status', 'todo')->values(),
            'in-progress' => $todos->where('status', 'in-progress')->values(),
            'qa-testing' => $todos->where('status', 'qa-testing')->values(),
            'done' => $todos->where('status', 'done')->values(),
        ];

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
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->priority,
            'type' => $request->type,
            'tags' => $request->tags,
            'assignee' => $request->assignee,
            'due_date' => $request->due_date,
            'story_points' => $request->story_points,
            'status' => $request->status,
        ]);

        $todo->load(['comments', 'attachments']);

        // Send real-time notification
        $this->webSocketService->todoCreated($todo);

        // Send assignment notification if todo is assigned to someone
        $this->assignmentNotificationService->sendNewTodoAssignmentNotification($todo);

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

        $todo->load(['comments.user', 'attachments']);

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

        $todo->load(['comments', 'attachments']);

        // Send real-time notification
        $this->webSocketService->todoUpdated($todo);

        // Send assignment notification if assignee has changed
        $this->assignmentNotificationService->sendAssignmentNotification($todo, $oldAssignee);

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
        
        $todo->delete();

        // Send real-time notification
        $this->webSocketService->todoDeleted($userId, $todoTitle);

        return response()->json([
            'success' => true,
            'message' => 'Todo deleted successfully'
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
}
