<?php

use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\AiController;
use App\Http\Controllers\Api\ExtensionController;
use App\Http\Controllers\Api\MapboxController;
use App\Http\Controllers\Api\MeetingNoteProposalController;
use App\Http\Controllers\Api\MeetingNotesController;
use App\Http\Controllers\Api\DocumentExtractionProposalController;
use App\Http\Controllers\Api\N8nDocumentExtractionController;
use App\Http\Controllers\Api\N8nMeetingNotesController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ProjectGroupController;
use App\Http\Controllers\Api\TodoAttachmentController;
use App\Http\Controllers\Api\TodoCommentController;
use App\Http\Controllers\Api\TodoController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\TodayController;
use App\Http\Controllers\Api\VoiceCommandController;
use App\Http\Controllers\Api\V1\AiController as V1AiController;
use App\Http\Controllers\Api\V1\ApiKeyController as V1ApiKeyController;
use App\Http\Controllers\Api\V1\AssetController as V1AssetController;
use App\Http\Controllers\Api\V1\AssetPhotoController as V1AssetPhotoController;
use App\Http\Controllers\Api\V1\AutomationController as V1AutomationController;
use App\Http\Controllers\Api\V1\ChecklistController as V1ChecklistController;
use App\Http\Controllers\Api\V1\CompanyController as V1CompanyController;
use App\Http\Controllers\Api\V1\ProjectController as V1ProjectController;
use App\Http\Controllers\Api\V1\TaskController as V1TaskController;
use App\Http\Controllers\Api\V1\UserController as V1UserController;
use App\Http\Controllers\Api\V1\WorkspaceController as V1WorkspaceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

/*
|--------------------------------------------------------------------------
| ZapTask Platform API v1
|--------------------------------------------------------------------------
|
| Authenticated via session, Sanctum PAT, or company API key (zt_live_*).
| Company scope is derived from credentials — never from client-supplied IDs.
|
*/
Route::prefix('v1')
    ->middleware(['api.rate.limit:api,120,1'])
    ->group(function () {
        Route::get('companies/{id}', [V1CompanyController::class, 'show'])
            ->middleware('platform.auth:users.read');

        Route::get('users', [V1UserController::class, 'index'])
            ->middleware('platform.auth:users.read');

        Route::get('workspaces', [V1WorkspaceController::class, 'index'])
            ->middleware('platform.auth:workspaces.read');
        Route::post('workspaces', [V1WorkspaceController::class, 'store'])
            ->middleware('platform.auth:workspaces.write');
        Route::get('workspaces/{id}', [V1WorkspaceController::class, 'show'])
            ->middleware('platform.auth:workspaces.read');
        Route::patch('workspaces/{id}', [V1WorkspaceController::class, 'update'])
            ->middleware('platform.auth:workspaces.write');
        Route::delete('workspaces/{id}', [V1WorkspaceController::class, 'destroy'])
            ->middleware('platform.auth:workspaces.write');

        Route::get('projects', [V1ProjectController::class, 'index'])
            ->middleware('platform.auth:projects.read');

        Route::get('tasks', [V1TaskController::class, 'index'])
            ->middleware('platform.auth:tasks.read');
        Route::post('tasks', [V1TaskController::class, 'store'])
            ->middleware('platform.auth:tasks.write');
        Route::get('tasks/{id}', [V1TaskController::class, 'show'])
            ->middleware('platform.auth:tasks.read');
        Route::patch('tasks/{id}', [V1TaskController::class, 'update'])
            ->middleware('platform.auth:tasks.write');
        Route::delete('tasks/{id}', [V1TaskController::class, 'destroy'])
            ->middleware('platform.auth:tasks.write');

        Route::get('tasks/{taskId}/checklist', [V1ChecklistController::class, 'index'])
            ->middleware('platform.auth:tasks.read');
        Route::post('tasks/{taskId}/checklist', [V1ChecklistController::class, 'store'])
            ->middleware('platform.auth:tasks.write');
        Route::patch('tasks/{taskId}/checklist/{itemId}', [V1ChecklistController::class, 'update'])
            ->middleware('platform.auth:tasks.write');
        Route::delete('tasks/{taskId}/checklist/{itemId}', [V1ChecklistController::class, 'destroy'])
            ->middleware('platform.auth:tasks.write');

        Route::get('assets', [V1AssetController::class, 'index'])
            ->middleware('platform.auth:assets.read');
        Route::post('assets', [V1AssetController::class, 'store'])
            ->middleware('platform.auth:assets.write');
        Route::get('assets/{id}', [V1AssetController::class, 'show'])
            ->middleware('platform.auth:assets.read');
        Route::patch('assets/{id}', [V1AssetController::class, 'update'])
            ->middleware('platform.auth:assets.write');
        Route::delete('assets/{id}', [V1AssetController::class, 'destroy'])
            ->middleware('platform.auth:assets.write');

        Route::get('assets/{assetId}/photos', [V1AssetPhotoController::class, 'index'])
            ->middleware('platform.auth:assets.read');
        Route::post('assets/{assetId}/photos', [V1AssetPhotoController::class, 'store'])
            ->middleware('platform.auth:assets.write');
        Route::get('assets/{assetId}/photos/{photoId}', [V1AssetPhotoController::class, 'show'])
            ->middleware('platform.auth:assets.read');
        Route::patch('assets/{assetId}/photos/{photoId}', [V1AssetPhotoController::class, 'update'])
            ->middleware('platform.auth:assets.write');
        Route::delete('assets/{assetId}/photos/{photoId}', [V1AssetPhotoController::class, 'destroy'])
            ->middleware('platform.auth:assets.write');

        Route::get('api-keys', [V1ApiKeyController::class, 'index'])
            ->middleware('platform.auth');
        Route::post('api-keys', [V1ApiKeyController::class, 'store'])
            ->middleware('platform.auth');
        Route::delete('api-keys/{id}', [V1ApiKeyController::class, 'destroy'])
            ->middleware('platform.auth');

        Route::get('automations', [V1AutomationController::class, 'index'])
            ->middleware('platform.auth:automations.read');
        Route::post('automations', [V1AutomationController::class, 'store'])
            ->middleware('platform.auth:automations.write');
        Route::patch('automations/{id}', [V1AutomationController::class, 'update'])
            ->middleware('platform.auth:automations.write');
        Route::delete('automations/{id}', [V1AutomationController::class, 'destroy'])
            ->middleware('platform.auth:automations.write');

        // AI as a service for specialised apps (preview → confirm)
        Route::post('ai', [V1AiController::class, 'handle'])
            ->middleware('platform.auth:ai.write');
    });

/*
|--------------------------------------------------------------------------
| Chrome extension / PAT clients (Bearer token)
|--------------------------------------------------------------------------
|
| Additive only — existing /api/todos and /api/projects session routes are unchanged.
| CSRF is skipped for api/extension/* (see bootstrap/app.php).
|
*/
Route::middleware(['auth:sanctum', 'subscription.access', 'api.rate.limit:api,60,1'])
    ->prefix('extension')
    ->group(function () {
        Route::get('me', [ExtensionController::class, 'me']);
        Route::get('projects', [ProjectController::class, 'index']);
        Route::post('todos', [TodoController::class, 'store']);
    });

Route::middleware(['n8n.webhook', 'api.rate.limit:api,120,1'])->group(function () {
    Route::post('n8n/meeting-notes/proposals', [N8nMeetingNotesController::class, 'store']);
    Route::post('n8n/document-extraction/proposals', [N8nDocumentExtractionController::class, 'store']);
});

// Todo routes
Route::middleware(['web', 'auth', 'subscription.access', 'api.rate.limit:api,60,1'])->group(function () {
    Route::get('projects/{project}/groups', [ProjectGroupController::class, 'index']);
    Route::post('projects/{project}/groups', [ProjectGroupController::class, 'store']);
    Route::put('project-groups/{projectGroup}', [ProjectGroupController::class, 'update']);
    Route::delete('project-groups/{projectGroup}', [ProjectGroupController::class, 'destroy']);

    // Project routes (place specific routes BEFORE resource to avoid {project} capturing)
    Route::patch('projects/update-order', [ProjectController::class, 'updateOrder']);
    Route::apiResource('projects', ProjectController::class);
    Route::delete('projects/{project}/with-todos', [ProjectController::class, 'destroyWithTodos']);
    Route::get('projects/{project}/stats', [ProjectController::class, 'stats']);
    Route::get('projects-with-stats', [ProjectController::class, 'withStats']);

    // Todo routes - Bulk operations MUST come before resource routes
    Route::patch('todos/update-order', [TodoController::class, 'updateOrder']);
    Route::get('todos/assignees', [TodoController::class, 'assignees']);

    // Bulk operations
    Route::patch('todos/bulk/status', [TodoController::class, 'bulkUpdateStatus']);
    Route::patch('todos/bulk/priority', [TodoController::class, 'bulkUpdatePriority']);
    Route::patch('todos/bulk/assignee', [TodoController::class, 'bulkUpdateAssignee']);
    Route::patch('todos/bulk/type', [TodoController::class, 'bulkUpdateType']);
    Route::patch('todos/bulk/due-date', [TodoController::class, 'bulkUpdateDueDate']);
    Route::patch('todos/bulk/tags', [TodoController::class, 'bulkUpdateTags']);
    Route::delete('todos/bulk', [TodoController::class, 'bulkDelete']);

    // Resource routes (these must come after specific routes)
    Route::apiResource('todos', TodoController::class);
    Route::patch('todos/{todo}/status', [TodoController::class, 'updateStatus']);
    Route::post('todos/{todo}/mark-assignment-seen', [TodoController::class, 'markAssignmentSeen']);
    Route::post('todos/{todo}/check-in', [TodoController::class, 'checkIn']);
    Route::post('todos/{todo}/subtasks', [TodoController::class, 'createSubtask']);
    Route::post('todos/{todo}/copy', [TodoController::class, 'copy']);

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

    // Sites & compliance routes (MAXI, LTD Agency, LTD Business only)
    Route::middleware('sites.access')->group(function () {
        Route::get('sites', [App\Http\Controllers\Api\OperationalObjectController::class, 'index']);
        Route::get('compliance/summary', [App\Http\Controllers\Api\OperationalObjectController::class, 'complianceSummary']);
        Route::post('sites/{site}/documents', [App\Http\Controllers\Api\OperationalDocumentController::class, 'store']);
        Route::get('sites/{site}/documents/{document}/download', [App\Http\Controllers\Api\OperationalDocumentController::class, 'download']);
        Route::get('document-extraction/proposals/pending', [DocumentExtractionProposalController::class, 'pending']);
        Route::post('document-extraction/proposals/{proposal}/approve', [DocumentExtractionProposalController::class, 'approve']);
        Route::post('document-extraction/proposals/{proposal}/dismiss', [DocumentExtractionProposalController::class, 'dismiss']);

        // Inspections
        Route::patch('inspections/{inspection}', [App\Http\Controllers\Api\InspectionController::class, 'update']);
        Route::post('inspections/{inspection}/photos', [App\Http\Controllers\Api\InspectionController::class, 'storePhoto']);
        Route::post('inspections/{inspection}/complete', [App\Http\Controllers\Api\InspectionController::class, 'complete']);
    });

    // Company users and messaging routes
    Route::get('company-users', [App\Http\Controllers\Api\CompanyUsersController::class, 'index']);
    Route::get('company-users/{user}/messages', [App\Http\Controllers\Api\CompanyUsersController::class, 'getMessages']);
    Route::post('company-messages', [App\Http\Controllers\Api\CompanyUsersController::class, 'sendMessage']);
    Route::get('company-messages/unread-count', [App\Http\Controllers\Api\CompanyUsersController::class, 'getUnreadCount']);

    // Notification routes
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);

    // Meeting notes
    Route::post('meeting-notes', [MeetingNotesController::class, 'store']);
    Route::post('voice-commands/process', [VoiceCommandController::class, 'process']);

    // AI gateway — structured proposals only; confirm required to mutate
    Route::post('ai', [AiController::class, 'handle']);

    Route::get('today/summary', [TodayController::class, 'summary']);
    Route::get('meeting-notes/proposals/pending', [MeetingNoteProposalController::class, 'pending']);
    Route::get('meeting-notes/proposals/{proposal}', [MeetingNoteProposalController::class, 'show']);
    Route::post('meeting-notes/proposals/{proposal}/approve', [MeetingNoteProposalController::class, 'approve']);
    Route::post('meeting-notes/proposals/{proposal}/dismiss', [MeetingNoteProposalController::class, 'dismiss']);

    // Mapbox — geocoding & route planning for location-aware tasks
    Route::get('mapbox/status', [MapboxController::class, 'status']);
    Route::get('mapbox/geocode', [MapboxController::class, 'geocode']);
    Route::get('mapbox/reverse-geocode', [MapboxController::class, 'reverseGeocode']);
    Route::post('mapbox/directions', [MapboxController::class, 'directions']);

    // Activity routes
    Route::get('activities', [ActivityController::class, 'index']);
    Route::get('activities/recent', [ActivityController::class, 'recent']);
    Route::get('activities/stats', [ActivityController::class, 'stats']);
    Route::get('activities/types', [ActivityController::class, 'types']);
    Route::delete('notifications/{notification}', [NotificationController::class, 'destroy']);
    Route::post('notifications', [NotificationController::class, 'store']); // For testing
});
