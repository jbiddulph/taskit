<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Todo;
use App\Models\TodoAttachment;
use App\Models\Activity;
use App\Services\TodoWebSocketService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TodoAttachmentController extends Controller
{
    protected TodoWebSocketService $webSocketService;

    public function __construct(TodoWebSocketService $webSocketService)
    {
        $this->webSocketService = $webSocketService;
    }

    /**
     * Display a listing of attachments for a todo.
     */
    public function index(Todo $todo): JsonResponse
    {
        $user = Auth::user();
        
        if ($todo->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $attachments = $todo->attachments()->with('user')->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $attachments
        ]);
    }

    /**
     * Store a newly created attachment.
     */
    public function store(Request $request, Todo $todo): JsonResponse
    {
        $user = Auth::user();
        
        if ($todo->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $file = $request->file('file');
        $originalFilename = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();
        
        // Generate unique filename
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        
        // Store file in private storage
        $filePath = $file->storeAs('todo-attachments', $filename, 'private');
        
        // Create attachment record
        $attachment = $todo->addAttachment($filePath, $originalFilename, $mimeType, $fileSize, $user);
        $attachment->load('user');

        // Send real-time notification
        $this->webSocketService->attachmentAdded($attachment);

        // Log activity
        Activity::create([
            'type' => 'attachment_uploaded',
            'actor_id' => $user->id,
            'actor_name' => $user->name,
            'actor_email' => $user->email,
            'target_id' => $todo->id,
            'target_name' => $todo->title,
            'description' => "{$user->name} uploaded attachment '{$attachment->filename}' to todo '{$todo->title}'",
            'metadata' => ['attachment_id' => $attachment->id, 'filename' => $attachment->filename],
            'project_id' => $todo->project_id,
            'company_id' => $user->company_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'File uploaded successfully',
            'data' => $attachment
        ], 201);
    }

    /**
     * Display the specified attachment.
     */
    public function show(Todo $todo, TodoAttachment $attachment): JsonResponse
    {
        $user = Auth::user();
        
        if ($todo->user_id !== $user->id || $attachment->todo_id !== $todo->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $attachment->load('user');

        return response()->json([
            'success' => true,
            'data' => $attachment
        ]);
    }

    /**
     * Download the specified attachment.
     */
    public function download(Todo $todo, TodoAttachment $attachment): JsonResponse
    {
        $user = Auth::user();
        
        if ($todo->user_id !== $user->id || $attachment->todo_id !== $todo->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        if (!Storage::disk('private')->exists($attachment->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found'
            ], 404);
        }

        return Storage::disk('private')->download(
            $attachment->file_path,
            $attachment->original_filename
        );
    }

    /**
     * Remove the specified attachment.
     */
    public function destroy(Todo $todo, TodoAttachment $attachment): JsonResponse
    {
        $user = Auth::user();
        
        if ($todo->user_id !== $user->id || $attachment->todo_id !== $todo->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        if ($attachment->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only delete your own attachments'
            ], 403);
        }

        // Delete file from storage
        if (Storage::disk('private')->exists($attachment->file_path)) {
            Storage::disk('private')->delete($attachment->file_path);
        }

        $todoTitle = $attachment->todo->title;
        $todoId = $attachment->todo_id;
        $userId = $attachment->todo->user_id;
        $filename = $attachment->original_filename;
        
        // Log activity before deletion
        Activity::create([
            'type' => 'attachment_deleted',
            'actor_id' => $user->id,
            'actor_name' => $user->name,
            'actor_email' => $user->email,
            'target_id' => $todoId,
            'target_name' => $todoTitle,
            'description' => "{$user->name} deleted attachment '{$filename}' from todo '{$todoTitle}'",
            'metadata' => ['attachment_id' => $attachment->id, 'filename' => $filename],
            'project_id' => $attachment->todo->project_id,
            'company_id' => $user->company_id,
        ]);
        
        // Delete attachment record
        $attachment->delete();

        // Send real-time notification
        $this->webSocketService->attachmentDeleted($userId, $todoId, $todoTitle, $filename);

        return response()->json([
            'success' => true,
            'message' => 'Attachment deleted successfully'
        ]);
    }

    /**
     * Get attachment preview (for images)
     */
    public function preview(Todo $todo, TodoAttachment $attachment): JsonResponse
    {
        $user = Auth::user();
        
        if ($todo->user_id !== $user->id || $attachment->todo_id !== $todo->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        if (!$attachment->is_image) {
            return response()->json([
                'success' => false,
                'message' => 'File is not an image'
            ], 400);
        }

        if (!Storage::disk('private')->exists($attachment->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found'
            ], 404);
        }

        $file = Storage::disk('private')->get($attachment->file_path);
        
        return response($file)
            ->header('Content-Type', $attachment->mime_type)
            ->header('Content-Disposition', 'inline; filename="' . $attachment->original_filename . '"');
    }
}
