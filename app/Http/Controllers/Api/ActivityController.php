<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityController extends Controller
{
    /**
     * Get activities for a company
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => true,
                'data' => [],
                'meta' => [
                    'total' => 0,
                    'page' => 1,
                    'per_page' => 20,
                    'has_more' => false
                ]
            ]);
        }

        // Filter activities to show only:
        // 1. Activities from the user's company (if user has company)
        // 2. Activities where the user is the actor
        if ($user->company_id) {
            $query = Activity::where(function($q) use ($user) {
                $q->where('company_id', $user->company_id)
                  ->orWhere('actor_id', $user->id);
            });
        } else {
            // If user has no company, only show their own activities
            $query = Activity::where('actor_id', $user->id);
        }
        
        $query = $query->with(['actor', 'project'])
            ->orderBy('created_at', 'desc');

        // Filter by project if specified
        if ($request->filled('project_id')) {
            $query->forProject($request->project_id);
        }

        // Filter by user if specified
        if ($request->filled('user_id')) {
            $query->forUser($request->user_id);
        }

        // Filter by type if specified
        if ($request->filled('type')) {
            $query->ofType($request->type);
        }

        // Filter by date range if specified
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        // Pagination
        $perPage = $request->get('per_page', 20);
        $page = $request->get('page', 1);
        
        $activities = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => $activities->items(),
            'meta' => [
                'total' => $activities->total(),
                'page' => $activities->currentPage(),
                'per_page' => $activities->perPage(),
                'has_more' => $activities->hasMorePages()
            ]
        ]);
    }

    /**
     * Get recent activities (last 24 hours)
     */
    public function recent(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        // Use the same filtering logic as the main index method
        if ($user->company_id) {
            $query = Activity::where(function($q) use ($user) {
                $q->where('company_id', $user->company_id)
                  ->orWhere('actor_id', $user->id);
            });
        } else {
            $query = Activity::where('actor_id', $user->id);
        }

        $activities = $query->with(['actor', 'project'])
            ->where('created_at', '>=', now()->subDay())
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $activities
        ]);
    }

    /**
     * Get activity statistics
     */
    public function stats(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        // Use the same filtering logic as the main index method
        if ($user->company_id) {
            $query = Activity::where(function($q) use ($user) {
                $q->where('company_id', $user->company_id)
                  ->orWhere('actor_id', $user->id);
            });
        } else {
            $query = Activity::where('actor_id', $user->id);
        }

        // Filter by date range if specified
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $stats = [
            'total_activities' => $query->count(),
            'by_type' => $query->selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type'),
            'by_user' => $query->selectRaw('actor_id, actor_name, COUNT(*) as count')
                ->groupBy('actor_id', 'actor_name')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->actor_name => $item->count];
                }),
            'by_date' => $query->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->limit(30)
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->date => $item->count];
                })
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get activity types
     */
    public function types()
    {
        $types = [
            'todo_created' => 'Todo Created',
            'todo_updated' => 'Todo Updated',
            'todo_deleted' => 'Todo Deleted',
            'todo_commented' => 'Todo Commented',
            'todo_assigned' => 'Todo Assigned',
            'todo_status_changed' => 'Status Changed',
            'project_created' => 'Project Created',
            'project_updated' => 'Project Updated',
            'project_deleted' => 'Project Deleted',
            'user_joined' => 'User Joined',
            'mention' => 'Mention'
        ];

        return response()->json([
            'success' => true,
            'data' => $types
        ]);
    }
}