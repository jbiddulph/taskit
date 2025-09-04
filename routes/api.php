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
Route::middleware(['web', 'auth'])->group(function () {
    // Project routes
    Route::apiResource('projects', ProjectController::class);
Route::delete('projects/{project}/with-todos', [ProjectController::class, 'destroyWithTodos']);
    Route::get('projects/{project}/stats', [ProjectController::class, 'stats']);
    Route::get('projects-with-stats', [ProjectController::class, 'withStats']);
    
    // Todo routes
    Route::apiResource('todos', TodoController::class);
    Route::patch('todos/{todo}/status', [TodoController::class, 'updateStatus']);
    Route::get('todos/assignees', [TodoController::class, 'assignees']);
    
    // Todo comments
    Route::apiResource('todos.comments', TodoCommentController::class);
    
    // Todo attachments
    Route::apiResource('todos.attachments', TodoAttachmentController::class);
    Route::get('todos/{todo}/attachments/{attachment}/download', [TodoAttachmentController::class, 'download']);
    Route::get('todos/{todo}/attachments/{attachment}/preview', [TodoAttachmentController::class, 'preview']);
    
    // User routes
    Route::get('users', [UserController::class, 'index']);
    
    // Notification routes
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::delete('notifications/{notification}', [NotificationController::class, 'destroy']);
    Route::post('notifications', [NotificationController::class, 'store']); // For testing
});
