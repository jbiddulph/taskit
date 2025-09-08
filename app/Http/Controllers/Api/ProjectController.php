<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\TodoWebSocketService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProjectController extends Controller
{
    protected TodoWebSocketService $webSocketService;

    public function __construct(TodoWebSocketService $webSocketService)
    {
        $this->webSocketService = $webSocketService;
    }

    /**
     * Display a listing of projects for the authenticated user
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();
        
        if ($user->company_id) {
            // Show visible projects from the same company (respecting subscription limits)
            $projects = Project::visibleForCompany($user->company_id)
                ->withCount('todos')
                ->get();
        } else {
            // Fallback to user's own projects if no company
            $projects = Project::forUser($user->id)
                ->withCount('todos')
                ->orderBy('viewing_order')
                ->orderBy('name')
                ->get();
        }

        // Transform the data to include total_todos
        $projects->each(function ($project) {
            $project->total_todos = $project->todos_count;
            unset($project->todos_count);
        });

        return response()->json([
            'success' => true,
            'data' => $projects
        ]);
    }

    /**
     * Store a newly created project
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'key' => 'nullable|string|max:10|unique:taskit_projects,key',
            'color' => 'nullable|string|regex:/^#[0-9A-F]{6}$/i',
            'client_id' => 'nullable|integer|exists:taskit_clients,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        
        // Check project limits for company users
        if ($user->company_id) {
            $company = $user->company;
            if (!$company->canCreateNewProjects()) {
                $currentCount = $company->getCurrentProjectCount();
                $limit = $company->getProjectLimit();
                
                if ($company->subscription_type === 'FREE') {
                    return response()->json([
                        'success' => false,
                        'message' => "Project limit reached ({$limit} projects). Upgrade to MIDI (£6/month) or MAXI (£9/month) to create more projects."
                    ], 403);
                } elseif ($company->subscription_type === 'MIDI') {
                    return response()->json([
                        'success' => false,
                        'message' => "Project limit reached ({$limit} projects). Upgrade to MAXI (£9/month) to create more projects."
                    ], 403);
                }
            }
        }
        
        // Validate client ownership if client_id is provided
        if ($request->client_id && $user->company_id) {
            $client = \App\Models\Client::find($request->client_id);
            if (!$client || $client->company_id !== $user->company_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid client selected.'
                ], 403);
            }
        }
        
        $project = Project::create([
            'name' => $request->name,
            'description' => $request->description,
            'key' => $request->key ?? Project::generateUniqueKey($request->name),
            'color' => $request->color ?? '#3B82F6',
            'owner_id' => $user->id,
            'viewing_order' => Project::getNextViewingOrder($user->id),
            'client_id' => $request->client_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Project created successfully',
            'data' => $project
        ], 201);
    }

    /**
     * Display the specified project
     */
    public function show(Project $project): JsonResponse
    {
        $user = Auth::user();
        
        if (!$project->canAccess($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $project->load(['todos' => function ($query) {
            $query->orderBy('created_at', 'desc');
        }]);

        return response()->json([
            'success' => true,
            'data' => $project
        ]);
    }

    /**
     * Update the specified project
     */
    public function update(Request $request, Project $project): JsonResponse
    {
        $user = Auth::user();
        
        if (!$project->canAccess($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'key' => 'sometimes|required|string|max:10|unique:taskit_projects,key,' . $project->id,
            'color' => 'nullable|string|regex:/^#[0-9A-F]{6}$/i',
            'is_active' => 'sometimes|boolean',
            'client_id' => 'nullable|integer|exists:taskit_clients,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Validate client ownership if client_id is provided
        if ($request->has('client_id') && $request->client_id && $user->company_id) {
            $client = \App\Models\Client::find($request->client_id);
            if (!$client || $client->company_id !== $user->company_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid client selected.'
                ], 403);
            }
        }

        $project->update($request->only(['name', 'description', 'key', 'color', 'is_active', 'client_id']));

        return response()->json([
            'success' => true,
            'message' => 'Project updated successfully',
            'data' => $project
        ]);
    }

    /**
     * Remove the specified project
     */
    public function destroy(Project $project): JsonResponse
    {
        $user = Auth::user();
        
        if (!$project->canAccess($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Get todo count for better user feedback
        $todoCount = $project->todos()->count();
        
        if ($todoCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete project with {$todoCount} existing todos. Please delete all todos first.",
                'todo_count' => $todoCount
            ], 422);
        }

        $project->delete();

        return response()->json([
            'success' => true,
            'message' => 'Project deleted successfully'
        ]);
    }

    /**
     * Remove the specified project and all its todos
     */
    public function destroyWithTodos(Project $project): JsonResponse
    {
        $user = Auth::user();
        
        if (!$project->canAccess($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $todoCount = $project->todos()->count();
        
        // Delete all todos first (cascade should handle this, but being explicit)
        $project->todos()->delete();
        
        // Then delete the project
        $project->delete();

        return response()->json([
            'success' => true,
            'message' => "Project and {$todoCount} todos deleted successfully"
        ]);
    }

    /**
     * Get project statistics
     */
    public function stats(Project $project): JsonResponse
    {
        $user = Auth::user();
        
        if (!$project->canAccess($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $stats = $project->getStats();

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get all projects with their statistics
     */
    public function withStats(): JsonResponse
    {
        $user = Auth::user();
        
        if ($user->company_id) {
            // Show visible projects from the same company (respecting subscription limits)
            $projects = Project::visibleForCompany($user->company_id)
                ->with(['todos', 'client'])
                ->get()
                ->map(function ($project) {
                    $stats = $project->getStats();
                    $project->stats = $stats;
                    $project->total_todos = $stats['total'];
                    $project->client_name = $project->client ? $project->client->name : null;
                    return $project;
                });
        } else {
            // Fallback to user's own projects if no company
            $projects = Project::forUser($user->id)
                ->with(['todos', 'client'])
                ->orderBy('viewing_order')
                ->orderBy('name')
                ->get()
                ->map(function ($project) {
                    $stats = $project->getStats();
                    $project->stats = $stats;
                    $project->total_todos = $stats['total'];
                    $project->client_name = $project->client ? $project->client->name : null;
                    return $project;
                });
        }

        // Group projects by client for better organization
        $groupedProjects = [
            'no_client' => [],
            'clients' => []
        ];

        foreach ($projects as $project) {
            if ($project->client_name) {
                if (!isset($groupedProjects['clients'][$project->client_name])) {
                    $groupedProjects['clients'][$project->client_name] = [
                        'id' => $project->client_id,
                        'name' => $project->client_name,
                        'projects' => []
                    ];
                }
                $groupedProjects['clients'][$project->client_name]['projects'][] = $project;
            } else {
                $groupedProjects['no_client'][] = $project;
            }
        }

        return response()->json([
            'success' => true,
            'data' => $projects, // Keep original flat structure for backward compatibility
            'grouped' => $groupedProjects // Add grouped structure for new sidebar
        ]);
    }

    /**
     * Update project viewing order
     */
    public function updateOrder(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $request->validate([
            'project_orders' => 'required|array',
            'project_orders.*.id' => 'required|integer|exists:taskit_projects,id',
            'project_orders.*.viewing_order' => 'required|integer|min:1',
        ]);

        foreach ($request->project_orders as $projectOrder) {
            $project = Project::find($projectOrder['id']);
            
            // Check if user can access this project
            if (!$project->canAccess($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to reorder this project'
                ], 403);
            }
            
            $project->update(['viewing_order' => $projectOrder['viewing_order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Project order updated successfully'
        ]);
    }
}
