<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TodoController;
use App\Http\Controllers\Api\TodoCommentController;
use App\Http\Controllers\Api\TodoAttachmentController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Todo routes
Route::middleware(['web', 'auth', 'subscription.access'])->group(function () {
    // Project routes (place specific routes BEFORE resource to avoid {project} capturing)
    Route::patch('projects/update-order', [ProjectController::class, 'updateOrder']);
    Route::apiResource('projects', ProjectController::class);
Route::delete('projects/{project}/with-todos', [ProjectController::class, 'destroyWithTodos']);
    Route::get('projects/{project}/stats', [ProjectController::class, 'stats']);
    Route::get('projects-with-stats', [ProjectController::class, 'withStats']);
    
    // Todo routes
    Route::apiResource('todos', TodoController::class);
    Route::patch('todos/{todo}/status', [TodoController::class, 'updateStatus']);
    Route::post('todos/{todo}/mark-assignment-seen', [TodoController::class, 'markAssignmentSeen']);
    Route::post('todos/{todo}/subtasks', [TodoController::class, 'createSubtask']);
    Route::get('todos/assignees', [TodoController::class, 'assignees']);
    
    // Todo comments
    Route::apiResource('todos.comments', TodoCommentController::class);
    
    // Todo attachments
    Route::apiResource('todos.attachments', TodoAttachmentController::class);
    Route::get('todos/{todo}/attachments/{attachment}/download', [TodoAttachmentController::class, 'download']);
    Route::get('todos/{todo}/attachments/{attachment}/preview', [TodoAttachmentController::class, 'preview']);
    
    // User routes
    Route::get('users', [UserController::class, 'index']);
    
    // Client routes
    Route::get('clients', [App\Http\Controllers\Api\ClientController::class, 'index']);
    
    // Notification routes
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::delete('notifications/{notification}', [NotificationController::class, 'destroy']);
    Route::post('notifications', [NotificationController::class, 'store']); // For testing
});
