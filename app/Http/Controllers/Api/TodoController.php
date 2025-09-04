<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Todo;
use App\Services\TodoWebSocketService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TodoController extends Controller
{
    protected TodoWebSocketService $webSocketService;

    public function __construct(TodoWebSocketService $webSocketService)
    {
        $this->webSocketService = $webSocketService;
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

        // Group by status for Kanban board
        $grouped = [
            'todo' => $todos->where('status', 'todo')->values(),
            'in-progress' => $todos->where('status', 'in-progress')->values(),
            'done' => $todos->where('status', 'done')->values(),
        ];

        return response()->json([
            'success' => true,
            'data' => $grouped,
            'stats' => [
                'total' => $todos->count(),
                'todo' => $todos->where('status', 'todo')->count(),
                'in-progress' => $todos->where('status', 'in-progress')->count(),
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
            'status' => 'required|in:todo,in-progress,done',
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
        
        if ($todo->user_id !== $user->id) {
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
        
        if ($todo->user_id !== $user->id) {
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
            'status' => 'sometimes|required|in:todo,in-progress,done',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $todo->update($request->only([
            'title', 'description', 'priority', 'type', 'tags',
            'assignee', 'due_date', 'story_points', 'status'
        ]));

        $todo->load(['comments', 'attachments']);

        // Send real-time notification
        $this->webSocketService->todoUpdated($todo);

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
        
        if ($todo->user_id !== $user->id) {
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
     * Update todo status (for drag & drop)
     */
    public function updateStatus(Request $request, Todo $todo): JsonResponse
    {
        $user = Auth::user();
        
        if ($todo->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:todo,in-progress,done',
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
