<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Todo;
use App\Models\TodoComment;
use App\Models\Notification;
use App\Services\TodoWebSocketService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TodoCommentController extends Controller
{
    protected TodoWebSocketService $webSocketService;

    public function __construct(TodoWebSocketService $webSocketService)
    {
        $this->webSocketService = $webSocketService;
    }

    /**
     * Display a listing of comments for a todo.
     */
    public function index(Todo $todo): JsonResponse
    {
        $user = Auth::user();
        
        if (!$todo->canAccess($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $comments = $todo->comments()->with('user')->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $comments
        ]);
    }

    /**
     * Store a newly created comment.
     */
    public function store(Request $request, Todo $todo): JsonResponse
    {
        $user = Auth::user();
        
        if (!$todo->canAccess($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $comment = $todo->addComment($request->content, $user);
        $comment->load('user');

        // Send real-time notification
        $this->webSocketService->commentAdded($comment);

        // Push notification to the assigned user (if different from commenter)
        if ($todo->assignee) {
            $assignedUser = \App\Models\User::where('name', $todo->assignee)->first();
            if ($assignedUser && $assignedUser->id !== $user->id) {
                Notification::create([
                    'user_id' => $assignedUser->id,
                    'type' => 'comment',
                    'title' => 'New Comment on Your Task',
                    'message' => "A new comment was added to '{$todo->title}'.",
                    'data' => [
                        'todo_id' => $todo->id,
                        'todo_title' => $todo->title,
                        'project_id' => $todo->project_id,
                        'project_name' => optional($todo->project)->name,
                        'comment_id' => $comment->id,
                        'comment_preview' => mb_substr($request->content, 0, 120),
                        'commented_by' => $user->name,
                    ],
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Comment added successfully',
            'data' => $comment
        ], 201);
    }

    /**
     * Display the specified comment.
     */
    public function show(Todo $todo, TodoComment $comment): JsonResponse
    {
        $user = Auth::user();
        
        if (!$todo->canAccess($user) || $comment->todo_id !== $todo->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $comment->load('user');

        return response()->json([
            'success' => true,
            'data' => $comment
        ]);
    }

    /**
     * Update the specified comment.
     */
    public function update(Request $request, Todo $todo, TodoComment $comment): JsonResponse
    {
        $user = Auth::user();
        
        if (!$todo->canAccess($user) || $comment->todo_id !== $todo->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        if ($comment->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only edit your own comments'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $comment->update(['content' => $request->content]);
        $comment->load('user');

        // Send real-time notification
        $this->webSocketService->commentUpdated($comment);

        return response()->json([
            'success' => true,
            'message' => 'Comment updated successfully',
            'data' => $comment
        ]);
    }

    /**
     * Remove the specified comment.
     */
    public function destroy(Todo $todo, TodoComment $comment): JsonResponse
    {
        $user = Auth::user();
        
        if (!$todo->canAccess($user) || $comment->todo_id !== $todo->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        if ($comment->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only delete your own comments'
            ], 403);
        }

        $todoTitle = $comment->todo->title;
        $todoId = $comment->todo_id;
        $userId = $comment->todo->user_id;
        
        $comment->delete();

        // Send real-time notification
        $this->webSocketService->commentDeleted($userId, $todoId, $todoTitle);

        return response()->json([
            'success' => true,
            'message' => 'Comment deleted successfully'
        ]);
    }
}
